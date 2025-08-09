<?php
$con = new mysqli("localhost", "root", "", "fithub");
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

session_start();
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['userId'];
$id = $userId['userId'];
$firstName = $_SESSION['FirstName'];
$lastName = $_SESSION['LastName'];

// שליפת הנקודות של המשתמש
$query = $con->prepare("SELECT points FROM users WHERE userId = ?");
$query->bind_param("s", $id);
$query->execute();
$result = $query->get_result();
$user_data = $result->fetch_assoc();
$total_points = $user_data['points'] ?? 0;

// חישוב ערך הנקודות בשקלים (כל 5 נקודות = 1 שקל)
$shekel_value = floor($total_points / 5);
$remaining_points = $total_points % 5;

// שליפת היסטוריית ההרשמות מהטבלה הקיימת עם פרטי האימונים
$registration_query = $con->prepare("
    SELECT r.registerationNum, r.date, r.price, r.final_price, r.points_earned, r.points_used, r.discount_amount, r.trainingNum,
           COALESCE(t.trainingName, CONCAT('אימון מספר ', r.trainingNum)) as trainingName
    FROM registeration r
    LEFT JOIN training t ON r.trainingNum = t.trainingNum
    WHERE r.userId = ? 
    ORDER BY r.date DESC 
    LIMIT 10
");
$registration_query->bind_param("s", $id);
$registration_query->execute();
$registrations = $registration_query->get_result();
?>

<!DOCTYPE html>
<html lang="en" >
<head>
    <meta charset="UTF-8">
    <title>Rewards System - FitHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            line-height: 1.6;
            margin: 0;
            background: url('images/gym.jpeg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            font-family:Times New Roman;
            color: #333;
           
        }

        .header {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 1rem 2rem;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 300;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            font-size: 18px;
            background: none;
            border: none;
            cursor: pointer;
            transition: 0.3s ease;
            color:white;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.3s;
        }

        .nav-btn:hover {
            background-color: rgba(167, 178, 139, 0.7);
            color: white;
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .welcome-section h2 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 400;
        }

        .welcome-section p {
            color: #7f8c8d;
            font-size: 16px;
        }

        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .points-card , .value-card  {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 4px solid rgb(150, 153, 155);
        }

        .points-number {
            font-size: 38px;
            font-weight: 300;
            color: #3498db;
            margin: 20px 0;
        }

        .points-label {
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

       

        .shekel-value {
            font-size: 38px;
            font-weight: 300;
            color: #27ae60;
            margin: 20px 0;
        }

        .progress-section {
            margin-top: 20px;
        }

        .progress-bar {
            background: #ecf0f1;
            border-radius: 10px;
            height: 8px;
            margin: 15px 0;
            overflow: hidden;
        }

        .progress-fill {
            background: #27ae60;
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .progress-text {
            font-size: 14px;
            color: #7f8c8d;
        }

        .info-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }

        .info-title {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 400;
        }

        .info-list {
            list-style: none;
        }

        .info-list li {
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            align-items: center;
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-list i {
            color: #3498db;
            margin-right: 15px;
            width: 20px;
        }

        .history-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .history-title {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 25px;
            font-weight: 400;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }

        .registration-item {
            padding: 20px 0;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .registration-item:last-child {
            border-bottom: none;
        }

        .registration-info {
            flex: 1;
        }

        .registration-name {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .registration-date {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .price-info {
            font-size: 14px;
            color: #34495e;
        }

        .discount-text {
            color: #e74c3c;
            font-weight: 500;
        }

        .points-badges {
            display: flex;
            flex-direction: column;
            gap: 5px;
            align-items: flex-end;
        }

        .points-earned-badge {
            background: #27ae60;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .points-used-badge {
            background: #e74c3c;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .congratulations-box {
            background: rgba(200, 207, 195, 0.89);
            color: black;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }

        .congratulations-box h3 {
            margin-bottom: 10px;
            font-weight: 400;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #bdc3c7;
        }

        @media (max-width: 768px) {
            .rewards-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .registration-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .points-badges {
                align-items: flex-start;
                flex-direction: row;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-gift"></i> Rewards System</h1>
            <div class="nav-buttons">
                <a href="profile.php" class="nav-btn">
                    <i class="fas fa-user"></i>
                </a>
                <a href="registerations.php" class="nav-btn">
                    <i class="fas fa-list"></i>
                </a>
                <a href="home.php" class="nav-btn">
                    <i class="fas fa-home"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h2>Hello, <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?>!</h2>
            <p>Track your points and rewards here</p>
        </div>

        <div class="rewards-grid">
            <div class="points-card">
                <i class="fas fa-star" style="font-size: 24px; color: #3498db; margin-bottom: 10px;"></i>
                <div class="points-number" id="points-counter"><?php echo number_format($total_points); ?></div>
                <div class="points-label">Total Points</div>
            </div>

            <div class="value-card">
                <i class="fas fa-shekel-sign" style="font-size: 24px; color: #27ae60; margin-bottom: 10px;"></i>
                <div class="shekel-value"><?php echo $shekel_value; ?></div>
                <div class="points-label">Available to Use</div>
                
                <?php if ($remaining_points > 0): ?>
                    <div class="progress-section">
                        <div class="progress-text">
                            <?php echo 5 - $remaining_points; ?> more points for another ₪
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo ($remaining_points / 5) * 100; ?>%"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($shekel_value > 0): ?>
            <div class="congratulations-box">
                <h3><i class="fas fa-trophy"></i> Congratulations!</h3>
                <p>You have <?php echo $shekel_value; ?> ₪ available to use for your next training sessions!</p>
            </div>
        <?php endif; ?>

        <div class="info-section">
            <div class="info-title">How It Works</div>
            <ul class="info-list">
                <li>
                    <i class="fas fa-plus-circle"></i>
                    Earn 10 points for every training registration
                </li>
                <li>
                    <i class="fas fa-calculator"></i>
                    Every 5 points equals 1 ₪ discount
                </li>
                <li>
                    <i class="fas fa-shopping-cart"></i>
                    Use points when registering for new training sessions
                </li>
                <li>
                    <i class="fas fa-infinity"></i>
                    Points accumulate and never expire
                </li>
                <li>
                    <i class="fas fa-shield-alt"></i>
                    Maximum discount per session cannot exceed the session price
                </li>
            </ul>
        </div>

        <?php if ($registrations && $registrations->num_rows > 0): ?>
        <div class="history-section">
            <div class="history-title">
                <i class="fas fa-history"></i> Recent Registrations
            </div>
            
            <?php while($registration = $registrations->fetch_assoc()): ?>
                <div class="registration-item">
                    <div class="registration-info">
                        <div class="registration-name">
                            <?php echo htmlspecialchars($registration['trainingName']); ?>
                        </div>
                        <div class="registration-date">
                            <i class="fas fa-calendar"></i> 
                            <?php echo date('M d, Y', strtotime($registration['date'])); ?>
                        </div>
                        <div class="price-info">
                            Original Price: ₪<?php echo number_format($registration['price'], 2); ?>
                            <?php if ($registration['discount_amount'] > 0): ?>
                                <span class="discount-text">
                                    (Discount: ₪<?php echo number_format($registration['discount_amount'], 2); ?>)
                                </span>
                            <?php endif; ?>
                            <br>
                            <strong>Paid: ₪<?php echo number_format($registration['final_price'] ?: $registration['price'], 2); ?></strong>
                        </div>
                    </div>
                    <div class="points-badges">
                        <span class="points-earned-badge">
                            +<?php echo $registration['points_earned']; ?> points
                        </span>
                        <?php if ($registration['points_used'] > 0): ?>
                            <span class="points-used-badge">
                                -<?php echo $registration['points_used']; ?> points
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="history-section">
            <div class="empty-state">
                <i class="fas fa-dumbbell"></i>
                <h3>No Training Registrations Yet</h3>
                <p>Start training to earn points and unlock rewards!</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // אנימציה לטעינת הנקודות
        window.addEventListener('load', function() {
            const pointsCounter = document.getElementById('points-counter');
            const originalPoints = <?php echo $total_points; ?>;
            let currentPoints = 0;
            
            if (originalPoints > 0) {
                const increment = Math.ceil(originalPoints / 50);
                const timer = setInterval(function() {
                    currentPoints += increment;
                    if (currentPoints >= originalPoints) {
                        currentPoints = originalPoints;
                        clearInterval(timer);
                    }
                    pointsCounter.textContent = currentPoints.toLocaleString();
                }, 30);
            }
        });
    </script>
</body>
</html>