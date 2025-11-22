<?php
/**
 * API Endpoints for AJAX requests
 * HeroHabits - JSON API
 */

require_once 'db_config.php';
require_once 'session.php';

// Set JSON header
header('Content-Type: application/json');

// Require authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$conn = getDBConnection();
$user_id = getCurrentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$response = ['success' => false];

switch ($action) {
    case 'complete_quest':
        // Complete a quest for today
        $quest_id = (int)($_POST['quest_id'] ?? 0);
        $child_id = (int)($_POST['child_id'] ?? 0);
        $completion_date = date('Y-m-d');

        // Verify quest belongs to user's child
        $sql = "SELECT q.gold_reward, c.user_id
                FROM quests q
                JOIN children c ON q.child_id = c.id
                WHERE q.id = ? AND c.id = ? AND c.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $quest_id, $child_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $quest = $result->fetch_assoc();
        $stmt->close();

        if ($quest) {
            // Check if already completed today
            $sql = "SELECT id FROM quest_completions WHERE quest_id = ? AND completion_date = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $quest_id, $completion_date);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Add completion
                $sql = "INSERT INTO quest_completions (quest_id, child_id, completion_date, gold_earned)
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iisi", $quest_id, $child_id, $completion_date, $quest['gold_reward']);
                $stmt->execute();
                $stmt->close();

                // Update gold balance
                $sql = "UPDATE children SET gold_balance = gold_balance + ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $quest['gold_reward'], $child_id);
                $stmt->execute();
                $stmt->close();

                // Get updated gold balance
                $sql = "SELECT gold_balance FROM children WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $child_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $child = $result->fetch_assoc();
                $stmt->close();

                $response = [
                    'success' => true,
                    'message' => 'Quest completed! +' . $quest['gold_reward'] . ' gold earned!',
                    'gold_earned' => $quest['gold_reward'],
                    'new_balance' => $child['gold_balance']
                ];
            } else {
                $response = ['success' => false, 'error' => 'Quest already completed today'];
            }
        } else {
            $response = ['success' => false, 'error' => 'Quest not found'];
        }
        break;

    case 'get_child_gold':
        // Get current gold balance for a child
        $child_id = (int)($_GET['child_id'] ?? 0);

        $sql = "SELECT gold_balance FROM children WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $child_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $child = $result->fetch_assoc();
        $stmt->close();

        if ($child) {
            $response = [
                'success' => true,
                'gold_balance' => $child['gold_balance']
            ];
        } else {
            $response = ['success' => false, 'error' => 'Child not found'];
        }
        break;

    case 'get_quest_status':
        // Get completion status for quests on a specific date
        $child_id = (int)($_GET['child_id'] ?? 0);
        $date = $_GET['date'] ?? date('Y-m-d');

        $sql = "SELECT qc.quest_id
                FROM quest_completions qc
                JOIN quests q ON qc.quest_id = q.id
                JOIN children c ON q.child_id = c.id
                WHERE c.id = ? AND c.user_id = ? AND qc.completion_date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $child_id, $user_id, $date);
        $stmt->execute();
        $result = $stmt->get_result();

        $completed_quests = [];
        while ($row = $result->fetch_assoc()) {
            $completed_quests[] = $row['quest_id'];
        }
        $stmt->close();

        $response = [
            'success' => true,
            'completed_quests' => $completed_quests
        ];
        break;

    case 'add_child':
        // Add a new child profile
        $name = sanitize($conn, $_POST['name'] ?? '');
        $age = (int)($_POST['age'] ?? 0);
        $avatar = sanitize($conn, $_POST['avatar'] ?? 'princess_3tr.png');

        if (strlen($name) > 0) {
            $sql = "INSERT INTO children (user_id, name, age, avatar_image) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isis", $user_id, $name, $age, $avatar);

            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                $stmt->close();

                $response = [
                    'success' => true,
                    'message' => 'Child profile created!',
                    'child_id' => $new_id
                ];
            } else {
                $response = ['success' => false, 'error' => 'Error creating profile'];
            }
        } else {
            $response = ['success' => false, 'error' => 'Name is required'];
        }
        break;

    case 'get_daily_summary':
        // Get summary of quest completions for a specific date
        $child_id = (int)($_GET['child_id'] ?? 0);
        $date = $_GET['date'] ?? date('Y-m-d');

        $sql = "SELECT COUNT(*) as quest_count, SUM(gold_earned) as total_gold
                FROM quest_completions qc
                JOIN quests q ON qc.quest_id = q.id
                JOIN children c ON q.child_id = c.id
                WHERE c.id = ? AND c.user_id = ? AND qc.completion_date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $child_id, $user_id, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $summary = $result->fetch_assoc();
        $stmt->close();

        $response = [
            'success' => true,
            'quest_count' => (int)$summary['quest_count'],
            'total_gold' => (int)$summary['total_gold']
        ];
        break;

    default:
        $response = ['success' => false, 'error' => 'Invalid action'];
        break;
}

$conn->close();
echo json_encode($response);
?>
