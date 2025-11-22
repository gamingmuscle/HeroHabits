<?php
require_once 'db_config.php';
require_once 'session.php';

requireLogin(); // Redirect to login if not authenticated

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Handle child selection
$selected_child_id = $_GET['child_id'] ?? getSelectedChildId();

// Get all children for this user
$children = [];
$sql = "SELECT * FROM children WHERE user_id = ? ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}
$stmt->close();

// If no child selected, select the first one
if (!$selected_child_id && count($children) > 0) {
    $selected_child_id = $children[0]['id'];
}

// Save selected child to session
if ($selected_child_id) {
    setSelectedChild($selected_child_id);
}

// Get current child details
$child = null;
if ($selected_child_id) {
    $sql = "SELECT * FROM children WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $selected_child_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $child = $result->fetch_assoc();
    $stmt->close();
}

// Get current month and year
$current_year = $_GET['year'] ?? date('Y');
$current_month = $_GET['month'] ?? date('m');
$month_name = date('F Y', strtotime("$current_year-$current_month-01"));

// Get quest completions for the current month
$completions = [];
if ($selected_child_id) {
    $sql = "SELECT completion_date, COUNT(*) as quest_count, SUM(gold_earned) as total_gold
            FROM quest_completions
            WHERE child_id = ? AND YEAR(completion_date) = ? AND MONTH(completion_date) = ?
            GROUP BY completion_date";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $selected_child_id, $current_year, $current_month);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $day = (int)date('j', strtotime($row['completion_date']));
        $completions[$day] = $row;
    }
    $stmt->close();
}

// Handle logout
if (isset($_GET['logout'])) {
    logout();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hero Habits Page</title>
  <!-- Google Font for bubbly kid-friendly style -->
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2&display=swap" rel="stylesheet">
  <style>
	:root {
      --nav-width: 240px;
      --bg: #F5F5F5;      /* slate-900 */
      --item: #1f2937;    /* slate-800 */
      --text: #e5e7eb;    /* slate-200 */
      --accent: #22c55e;  /* green-500 */
    }
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    header {
		top: 0px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #B0BEC5;
		padding: 15px 20px;
		font-family: 'Luminari', fantasy;
    }
	@font-face {
		font-family: Luminari; /* set name */
		src: url('http://127.0.0.1/HeroHabits/Assets/Fonts/Luminari-Regular.ttf') format('ttf'); /* url of the font */
	}
    header h1 {
      margin: 0;
      font-size: 2REM;
	  font-weight: bold;
      color: #7E57C2;
    }
    header .center-btn {
      flex: 1;
      display: flex;
      justify-content: center;
    }
    button {
	  padding: 4px 6px;
		margin-bottom: 6px;
      border: none;
      border-radius: 8px;
      color: white;
	  font-size: 1.25REM;
      cursor: pointer;
      font-family: 'Quicksand', cursive;
    }
	button.quest {
		background-color: #7E57C2;
	}
	button.treasure {
		background-color: #FFCA28;
	}
    header .right-text {
      font-size: 20px;
      color: #333;
    }
	header .right-text img, span
	{
		vertical-align: middle;
	}
	div.main_container
	{
		display: flex;
		flex: 1;
	}
	nav 
	{
		width:240px;
		height: 100%;
		position: sticky;
		display: block;
	}
    main {
      flex: 1;
      padding: 30px;
      text-align: center;
		background-image: url('http://127.0.0.1/HeroHabits/Assets/hero_habits_bg_1.png');
		background-size: cover;
    }
    footer {
      background: #ffccf9;
      text-align: center;
      padding: 15px;
      font-family: 'Baloo 2', cursive;
    }
    
    .calendar {
      width: 100%; /* full width of container */
      max-width: 100%; /* no restriction */
      background-color: rgba(255, 255, 255, 0.1)
    }
    .month {
      text-align: center;
      background: #ffccf9;
      padding: 20px;
      font-size: 32px;
      font-weight: bold;
		font-family: 'MedievalSharp';
    }
    .weekdays, .days {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
    }
    .weekdays div {
      text-align: center;
      padding: 12px 0;
      background: #ffe6f9;
      font-weight: bold;
    }
    .days div {
      min-height: 100px;
      padding: 5px;
      position: relative;
      background: #fafafa;
background-color: rgba(255,255, 255, 0.25)

    }
    .days div span.cal_date {
      position: absolute;
      top: 8px;
      left: 8px;
      font-size: 16px;
      font-weight: bold;
    }
	.child_list
	{
		list-style-type: none;
	}
	.gold
	{
		color: #FFCA28;
	}
	.purple
	{
		//color: #7E57C2;
		color: white;
	}
img.profile_pic
{
	height: 50px;
	width: 50px;
}
.bold
{
	font-weight: bold;
}
div.completed_quests
{
	width: 100%;
	padding: 6px 6px;
	border-radius: 6px;
	//background-color: #b0bec5;
	background-color: #7E57C2;
	color: #2C2C2C;
	font-family: Quicksand;
}

  </style>
</head>
	<body>
			<header>
				<h1>Hero Habits</h1>
				<div class="center-btn">
					<?php if (count($children) > 0): ?>
						<select onchange="window.location.href='?child_id=' + this.value" style="padding: 8px; border-radius: 5px; font-size: 14px;">
							<?php foreach ($children as $c): ?>
								<option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $selected_child_id) ? 'selected' : ''; ?>>
									<?php echo htmlspecialchars($c['name']); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
				</div>
				<div class="right-text">
					<?php if ($child): ?>
						<img class="profile_pic" src="http://127.0.0.1/HeroHabits/Assets/<?php echo htmlspecialchars($child['avatar_image']); ?>">
						<span><?php echo htmlspecialchars($child['name']); ?></span>
						<span class="gold"><?php echo $child['gold_balance']; ?> Gold</span>
					<?php else: ?>
						<span>No child selected</span>
					<?php endif; ?>
					<a href="?logout=1" style="margin-left: 15px; color: #333; text-decoration: none;">Logout</a>
				</div>
			</header>	 
			<div class="main_container">
				<nav>
					<ul class="child_list">
						<li>
							<button class="quest" onclick="window.location.href='quests.php'">Quests</button>
						</li>
						<li>
							<button class="treasure" onclick="window.location.href='treasures.php'">Treasures</button>
						</li>
						<li>
							<button class="quest" onclick="window.location.href='index.php'">Calendar</button>
						</li>
						<li>
							<button class="treasure" onclick="window.location.href='profiles.php'">Profiles</button>
						</li>
					</ul>
				</nav>
				<main>
						  <div class="calendar">
							<div class="month"><?php echo $month_name; ?></div>
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
							          echo $quest_count . ' <span class="bold purple">Quest' . ($quest_count > 1 ? 's' : '') . '</span> for ';
							          echo $gold . ' <span class="bold gold">Gold</span>';
							          echo '</div>';
							      }

							      echo '</div>';
							  }
							  ?>
							</div>
						  </div>
				</main>
			</div>
			<footer>
			</footer>
	</body>
</html>