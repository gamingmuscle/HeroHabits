<?php
require_once 'db_config.php';
require_once 'session_enhanced.php';

requireChildLogin();

$child_id = getCurrentChildId();
$child_name = getCurrentChildName();

$conn = getDBConnection();

// Get current month and year
$current_year = date('Y');
$current_month = date('m');
$month_name = date('F Y');

// Get accepted completions for current month
$completions = [];
$sql = "SELECT completion_date, COUNT(*) as quest_count, SUM(gold_earned) as total_gold
        FROM quest_completions
        WHERE child_id = ? AND status = 'Accepted'
        AND YEAR(completion_date) = ? AND MONTH(completion_date) = ?
        GROUP BY completion_date";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $child_id, $current_year, $current_month);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $day = (int)date('j', strtotime($row['completion_date']));
    $completions[$day] = $row;
}
$stmt->close();

// Get pending count
$sql = "SELECT COUNT(*) as pending_count FROM quest_completions
        WHERE child_id = ? AND status = 'Pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $child_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_data = $result->fetch_assoc();
$pending_count = $pending_data['pending_count'];
$stmt->close();

$page_title = 'My Calendar';
$current_page = 'calendar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Calendar - Hero Habits</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/modern-theme.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      background: var(--gray-50);
    }

    .app-container {
      display: flex;
      min-height: calc(100vh - 73px);
    }

    .main-content {
      flex: 1;
      padding: 2rem;
      max-width: 1400px;
      margin: 0 auto;
      width: 100%;
    }

    .pending-notice {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      border: 1px solid #fbbf24;
      padding: 1rem 1.5rem;
      border-radius: var(--radius-xl);
      margin-bottom: 1.5rem;
      text-align: center;
      font-weight: 600;
      color: #92400e;
      box-shadow: var(--shadow-sm);
    }

    .calendar-container {
      background: rgba(255, 255, 255, 0.8);
      border-radius: var(--radius-xl);
      padding: 2rem;
      box-shadow: var(--shadow-lg);
    }

    .calendar-header {
      text-align: center;
      font-size: 2rem;
      font-weight: 700;
      color: var(--purple);
      margin-bottom: 2rem;
    }

    .weekdays, .days {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 0.75rem;
    }

    .weekdays div {
      text-align: center;
      padding: 1rem;
      background: linear-gradient(135deg, var(--purple) 0%, #8b5cf6 100%);
      color: white;
      font-weight: 600;
      font-size: 0.875rem;
      border-radius: var(--radius-lg);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .days div {
      min-height: 120px;
      padding: 0.75rem;
      background: var(--gray-50);
      border-radius: var(--radius-lg);
      position: relative;
      border: 2px solid var(--gray-200);
      transition: all 0.2s ease;
    }

    .days div:hover {
      border-color: var(--purple);
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .cal_date {
      position: absolute;
      top: 0.5rem;
      left: 0.75rem;
      font-size: 1.125rem;
      font-weight: 700;
      color: var(--gray-600);
    }

    .completed_quests {
      margin-top: 2rem;
      padding: 0.75rem;
      border-radius: var(--radius-md);
      background: linear-gradient(135deg, var(--purple) 0%, #8b5cf6 100%);
      color: white;
      font-weight: 600;
      text-align: center;
      font-size: 0.875rem;
      line-height: 1.5;
    }

    .gold {
      color: var(--gold);
      font-weight: 700;
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 1rem;
      }

      .calendar-container {
        padding: 1rem;
      }

      .calendar-header {
        font-size: 1.5rem;
      }

      .days div {
        min-height: 80px;
        padding: 0.5rem;
      }

      .cal_date {
        font-size: 0.875rem;
      }

      .completed_quests {
        font-size: 0.75rem;
        margin-top: 1.5rem;
        padding: 0.5rem;
      }
    }
  </style>
</head>
<body>
  <?php include 'child_header.php'; ?>

  <div class="app-container">
    <?php include 'child_nav.php'; ?>

    <main class="main-content">
      <?php if ($pending_count > 0): ?>
        <div class="pending-notice">
          ⏳ You have <?php echo $pending_count; ?> quest<?php echo $pending_count > 1 ? 's' : ''; ?> waiting for parent approval!
        </div>
      <?php endif; ?>

      <div class="calendar-container">
        <div class="calendar-header"><?php echo $month_name; ?></div>
        <div class="weekdays">
          <div>Sun</div>
          <div>Mon</div>
          <div>Tue</div>
          <div>Wed</div>
          <div>Thu</div>
          <div>Fri</div>
          <div>Sat</div>
        </div>
        <div class="days">
          <?php
          // Calculate first day of month and total days
          $first_day_of_month = date('w', strtotime("$current_year-$current_month-01"));
          $total_days = date('t', strtotime("$current_year-$current_month-01"));

          // Add empty cells for days before month starts
          for ($i = 0; $i < $first_day_of_month; $i++) {
              echo '<div></div>';
          }

          // Generate days of the month
          for ($day = 1; $day <= $total_days; $day++) {
              echo '<div><span class="cal_date">' . $day . '</span>';

              // Show quest completion info if exists
              if (isset($completions[$day])) {
                  $quest_count = $completions[$day]['quest_count'];
                  $gold = $completions[$day]['total_gold'];
                  echo '<div class="completed_quests">';
                  echo '🎉 ' . $quest_count . ' Quest' . ($quest_count > 1 ? 's' : '') . '<br>';
                  echo '<span class="gold">+' . $gold . ' Gold ⭐</span>';
                  echo '</div>';
              }

              echo '</div>';
          }
          ?>
        </div>
      </div>
    </main>
  </div>
</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    logoutChild();
}

//$conn->close();
?>
