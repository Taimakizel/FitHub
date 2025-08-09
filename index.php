<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitHub</title>
    <style>
        body {
            display: flex;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            background: url('images/logo.jpg') no-repeat center/cover;
            text-align: center;
            position: relative;
            font-family:Times New Roman;
        }
        .fithub{
            background: url('images/gym-background.jpg') center center / cover no-repeat;
            height: 300px;
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            margin-top:150px;
            max-width: 700px;
        }

        .overlay {
            background-color: rgba(0, 0, 0, 0.5); /* רקע מושהה */
            color: white;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
        }

        .fithub h1 {
            font-size: 40px;
            margin: 0;
            letter-spacing: 2px;
        }

        .fithub p {
            font-size: 23px;
            margin: 10px 0 20px;
            color: #eee;
        }

        .start-btn {
            background-color: #ffffff;
            color: #111;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 15px;
            font-weight: bold;
            transition: background-color 0.3s ease;
            font-size: 18px;
        }

        .start-btn:hover {
            background-color: #ddd;
        }
        </style>
</head>
<body>
<div class="fithub">
  <div class="overlay">
    <h1>FitHub</h1>
    <p>
        Transform your fitness journey with personalized workouts and expert coaching
        where every step brings you closer to your goals.
    </p>
    <a href="login.php" class="start-btn">JOIN US NOW</a>
  </div>
</div>
</body>
</html>
