<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mystical Missions: Activity Tracker</title>
    <style>
        /* *** STYLISTIC NOTES FOR DEVELOPMENT ***

        1. Color Scheme: Use bright, light, and slightly muted fantasy colors.
           - Background: Very light parchment or cream (#F5F0E6)
           - Primary Accent (Gold/Points): Yellow/Gold (#FFC72C)
           - Secondary Accent (Quest/Mission): Greens, Blues, Purples
           - Font: A fun, readable "fantasy" font (like 'Fredoka One' or 'Crumble' from Google Fonts)

        2. Imagery: Replace text placeholders (like

[Image of Princess]
) with actual fun, cartoon fantasy character images (Knights, Elves, Princesses, Wizards).

        3. Borders/Containers: Use rounded corners and slight drop shadows on all major boxes (Calendar, Quests) to give them a "game card" feel.

        4. Gold Coin Counter: Should be highly visible in the header, possibly with a gold coin icon.
        */

        body {
            font-family: sans-serif; /* Placeholder, use a fun font! */
            background-color: #f5f0e6; /* Light Parchment Background */
            color: #333;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background-color: #C0E3A2; /* Light, cheerful header background */
            border-bottom: 5px solid #A2C889;
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo-text {
            font-size: 2.5em;
            font-weight: bold;
            color: #4B0082; /* Deep Purple/Fantasy Color */
            margin-left: 10px;
            text-shadow: 2px 2px 0px #FFD700; /* Gold Shadow */
        }

        .gold-counter {
            font-size: 1.5em;
            font-weight: bold;
            color: #FFC72C; /* Gold */
            background-color: #333;
            padding: 5px 15px;
            border-radius: 20px;
            border: 3px solid #FFF;
        }

        .nav-bar {
            background-color: #f0f0f0; /* Lighter background for the tabs */
            padding: 10px 0;
            text-align: center;
            border-bottom: 2px solid #ccc;
        }

        .nav-bar button {
            padding: 10px 20px;
            margin: 0 5px;
            background-color: #D3D3D3;
            border: none;
            cursor: pointer;
            font-weight: bold;
            border-radius: 8px;
        }

        .nav-bar button.active {
            background-color: #FFC72C; /* Gold/Active tab color */
            color: #4B0082;
        }

        .main-content {
            display: flex;
            gap: 20px;
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .calendar-section {
            flex: 2; /* Takes up more space */
            background-color: #fff;
            border: 3px solid #D3D3D3;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .sidebar {
            flex: 1; /* Takes up less space */
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .quest-box, .journey-box {
            background-color: #fff;
            border: 3px solid #FFC72C; /* Gold Border */
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .quest-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px dashed #ccc;
            padding: 10px 0;
        }

        .quest-gold {
            font-weight: bold;
            color: #FFC72C;
        }

        /* Calendar Grid Styles */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            text-align: center;
            font-weight: bold;
        }

        .calendar-day-header {
            background-color: #E0E0E0;
            padding: 5px 0;
            border-radius: 5px;
        }

        .calendar-date {
            background-color: #f8f8f8;
            height: 80px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            font-size: 1.1em;
            cursor: pointer;
        }

        .completed-quest-icon {
            margin-top: 5px;
            font-size: 1.5em;
            /* Use a small icon image for quest/journey completion, e.g., a shield or a treasure chest */
        }

        /* Journey Specific Styling (Different Look) */
        .journey-item {
            background-color: #e6f7ff; /* Light Blue/Water for Journey/Epic */
            border: 2px solid #1E90FF;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
        }

        .journey-progress {
            font-size: 0.9em;
            color: #555;
        }

        .treasure-redeem {
            text-align: center;
            padding: 20px;
            background-color: #FFDAB9; /* Light Peach/Treasure Chest base */
            border-top: 5px solid #FFC72C;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo-container">
            <span class="logo-icon"></span>
            <h1 class="logo-text">MYSTICAL MISSIONS</h1>
        </div>
        <div class="gold-counter">
            <span class="gold-icon">💰</span> GOLD COIN: 1,550
        </div>
    </div>

    <nav class="nav-bar">
        <button class="active">MY CALENDAR</button>
        <button>ALL QUESTS</button>
        <button>JOURNEYS</button>
        <button>TREASURE SHOP</button>
    </nav>

    <main class="main-content">

        <section class="calendar-section">
            <h2>Daily Adventures - November 2025</h2>

            <div class="calendar-grid">
                <div class="calendar-day-header">SUN</div>
                <div class="calendar-day-header">MON</div>
                <div class="calendar-day-header">TUE</div>
                <div class="calendar-day-header">WED</div>
                <div class="calendar-day-header">THU</div>
                <div class="calendar-day-header">FRI</div>
                <div class="calendar-day-header">SAT</div>

                <div class="calendar-date">1
                    <span class="completed-quest-icon">⚔️</span>
                </div>
                <div class="calendar-date">2
                    <span class="completed-quest-icon">📚</span>
                </div>
                <div class="calendar-date">3
                    <span class="completed-quest-icon">🛡️</span>
                    <span class="completed-quest-icon">⚔️</span>
                </div>
                <div class="calendar-date">20
                    <span class="completed-quest-icon">📚</span>
                    <span class="completed-quest-icon">🛡️</span>
                </div>
                </div>

        </section>

        <aside class="sidebar">

            <div class="quest-box">
                <h3>Today's Quests (Stand-Alone)</h3>

                <div class="quest-item">
                    <span>⚔️ Defeat the Laundry Pile (15 min.)</span>
                    <span class="quest-gold">20 GOLD</span>
                </div>

                <div class="quest-item">
                    <span>📚 Read for 30 minutes</span>
                    <span class="quest-gold">15 GOLD</span>
                </div>
            </div>

            <div class="journey-box">
                <h3>Current Journeys</h3>

                <div class="journey-item">
                    <h4>The Royal Grimoire Journey</h4>
                    <p>Complete 5 Quest to Earn: **50 BONUS GOLD**</p>
                    <p class="journey-progress">3/5 Quests Completed</p>

                    <small>Next Quest: Tidy the Castle Dining Hall</small>
                </div>

                <div class="journey-item">
                    <h4>Dragon's Tooth Peak Climb</h4>
                    <p>Complete All Steps for: **100 BONUS GOLD**</p>
                    <p class="journey-progress">1/4 Quests Completed</p>

                    <small>Next Quest: Conquer the Messy Bedroom</small>
                </div>

            </div>

        </aside>

    </main>

    <footer class="treasure-redeem">
        <h2>REDEEM YOUR GOLD FOR AWESOME TREASURE!</h2>
    </footer>

</body>
</html>