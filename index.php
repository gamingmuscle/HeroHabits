<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming Soon - Hero Habits</title>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Baloo 2', cursive;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            text-align: center;
			border-radius: 16px;
			background-color: white;
			box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4);
			padding: 20px 30px;
        }

        .logo {
            width: 300px;
            height: auto;
            margin-bottom: 40px;
        }

        h1 {
            font-size: 5rem;
            color: #5D3FD3;
            font-weight: 700;
            text-shadow: 0 4px 8px rgba(93, 63, 211, 0.9);

        }

        @media (max-width: 768px) {
            .logo {
                width: 200px;
            }

            h1 {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="logo.png" alt="Hero Habits Logo" class="logo">
        <h1>Coming Soon</h1>
    </div>
</body>
</html>
