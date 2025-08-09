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

// טיפול בביטול הרשמה לאימון
// טיפול בביטול הרשמה לאימון
if ($_SERVER["REQUEST_METHOD"] === "POST" && 
    isset($_POST['cancel_training']) && 
    $_POST['cancel_training'] === '1' &&
    isset($_POST['registration_num']) && 
    !empty($_POST['registration_num']) &&
    isset($_POST['training_num']) && 
    !empty($_POST['training_num'])) {
    
    $registerationNum = $_POST['registration_num'];
    $trainingNum = $_POST['training_num'];
    
    // בדיקה שההרשמה שייכת למשתמש הנוכחי
    $checkQuery = "SELECT r.*, t.Date, t.Time FROM registeration r JOIN training t ON r.trainingNum = t.trainingNum WHERE registerationNum = ? AND userId = ?";
    $checkStmt = $con->prepare($checkQuery);
    $checkStmt->bind_param("is", $registerationNum, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $regData = $checkResult->fetch_assoc();
        
        // בדיקה שיש יותר מ-4 שעות לאימון
        $trainingDateTime = $regData['Date'] . ' ' . $regData['Time'];
        $trainingTimestamp = strtotime($trainingDateTime);
        $currentTimestamp = time();
        $hoursUntilTraining = ($trainingTimestamp - $currentTimestamp) / 3600;
        
        if ($hoursUntilTraining < 4) {
            echo "<script>alert('Cannot cancel training. Must cancel at least 4 hours before the training starts.');</script>";
        } else {
            try {
                $con->begin_transaction();
                
                // חישוב החזר כספי
                $refundAmount = 0;
                if ($hoursUntilTraining >= 4) {
                    $refundAmount = $regData['final_price'];
                }

                // הוספה לטבלת ביטולים
                $cancellationStmt = $con->prepare("INSERT INTO cancellations (userId, trainingNum, cancellation_date, refund_amount) VALUES (?, ?, NOW(), ?)");
                $cancellationStmt->bind_param("sid", $id, $trainingNum, $refundAmount);
                $cancellationStmt->execute();
                
                // מחיקת ההרשמה מטבלת registeration
                $deleteQuery = "DELETE FROM registeration WHERE registerationNum = ? AND userId = ?";
                $deleteStmt = $con->prepare($deleteQuery);
                $deleteStmt->bind_param("is", $registerationNum, $id);
                $deleteStmt->execute();
                
                // עדכון מספר המשתתפים בטבלת training (הפחתה ב-1)
                $updateQuery = "UPDATE training SET Participants = Participants - 1 WHERE trainingNum = ?";
                $updateStmt = $con->prepare($updateQuery);
                $updateStmt->bind_param("i", $trainingNum);
                $updateStmt->execute();
                
                // בדיקת מספר הנקודות הנוכחי לפני הפחתה
                $checkPointsQuery = $con->prepare("SELECT points FROM users WHERE userId = ?");
                $checkPointsQuery->bind_param("s", $id);
                $checkPointsQuery->execute();
                $pointsResult = $checkPointsQuery->get_result();
                $currentPoints = $pointsResult->fetch_assoc()['points'];
                
                // הפחת 10 נקודות רק אם יש לפחות 10 נקודות
                if ($currentPoints >= 10) {
                    $deductPoints = $con->prepare("UPDATE users SET points = points - 10 WHERE userId = ?");
                    $deductPoints->bind_param("s", $id);
                    $deductPoints->execute();
                    
                    // הוסף להיסטוריית נקודות - הפחתה
                    $historyStmt = $con->prepare("INSERT INTO points_history (userId, points_change, points_type, description, registration_num) VALUES (?, -10, 'penalty', ?, ?)");
                    $description = "הפחתת נקודות עקב ביטול הרשמה לאימון";
                    $historyStmt->bind_param("ssi", $id, $description, $registerationNum);
                    $historyStmt->execute();
                    
                    $pointsMessage = "10 points have been deducted for cancellation.";
                } else {
                    // אם אין מספיק נקודות, הורד את הנקודות לאפס
                    $deductPoints = $con->prepare("UPDATE users SET points = 0 WHERE userId = ?");
                    $deductPoints->bind_param("s", $id);
                    $deductPoints->execute();
                    
                    // רשום את ההפחתה בהיסטוריה (רק את מה שהופחת בפועל)
                    if ($currentPoints > 0) {
                        $historyStmt = $con->prepare("INSERT INTO points_history (userId, points_change, points_type, description, registration_num) VALUES (?, ?, 'penalty', ?, ?)");
                        $description = "הפחתת נקודות עקב ביטול הרשמה לאימון (הופחתו {$currentPoints} נקודות)";
                        $historyStmt->bind_param("sisi", $id, -$currentPoints, $description, $registerationNum);
                        $historyStmt->execute();
                    }
                    
                    $pointsMessage = "All your {$currentPoints} points have been deducted for cancellation (you didn't have enough for the full 10-point penalty).";
                }
                
                $con->commit();
                
                echo "<script>alert('Training cancellation completed successfully. {$pointsMessage}'); location.href='registerations.php';</script>";
                
            } catch (Exception $e) {
                $con->rollback();
                echo "<script>alert('Error canceling registration: " . $e->getMessage() . "');</script>";
            }
        }
    } else {
        echo "<script>alert('Registration not found');</script>";
    }
}

// שליפת הרשמות לאימונים
$trainingQuery = "
    SELECT r.registerationNum, r.date as reg_date, r.price, r.trainingNum,
           t.trainingName, t.Date, t.Time, t.Duration,
           CONCAT(u.FirstName, ' ', u.LastName) as trainer_name
    FROM registeration r
    JOIN training t ON r.trainingNum = t.trainingNum
    JOIN users u ON t.trainerId = u.userId
    WHERE r.userId = ?
    ORDER BY r.date DESC
";

$stmt = $con->prepare($trainingQuery);
$stmt->bind_param("s", $id);
$stmt->execute();
$trainingResults = $stmt->get_result();

// שליפת הרשמות לאירועים
$eventQuery = "
    SELECT er.registerationNum, er.date as reg_date,
           e.eventName, e.Date, e.Location
    FROM eventregisteration er
    JOIN events e ON er.eventId = e.eventId
    WHERE er.userId = ?
    ORDER BY er.date DESC
";

$stmt = $con->prepare($eventQuery);
$stmt->bind_param("s", $id);
$stmt->execute();
$eventResults = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en" >
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitHub - Registerations</title>
    <style>
        body {
            margin: 0;
            background: url('images/profile.jpg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            font-family:Times New Roman;
            color: #333;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 50px 20px;
        }

        .main-box {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(14px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 1000px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .header .user-info {
            color: #7f8c8d;
            font-size: 1.2em;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
           
            color:rgb(60, 79, 51);
            padding: 15px 25px;
            border-radius: 10px;
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }

        .registrations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .registration-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid rgb(108, 142, 90);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .registration-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .event-card {
            border-left-color:rgb(174, 213, 155);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1.3em;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }

        .registration-number {
            background: #ecf0f1;
            color: #7f8c8d;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .card-content {
            line-height: 1.6;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
            align-items: center;
        }

        .info-label {
            font-weight: bold;
            color: #34495e;
            margin-left: 8px;
            min-width: 100px;
        }

        .info-value {
            color: #2c3e50;
        }

        .price {
            background-color: rgb(139, 155, 138);
            color: black;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }

        .date-badge {
            background:rgb(136, 161, 133);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .no-registrations {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 1.2em;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 15px;
            border: 2px dashed #bdc3c7;
        }

        .back-button {
            display: inline-block;
            background: linear-gradient(135deg,rgb(207, 229, 191) 0%,rgb(122, 159, 119) 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }

        .back-button:hover {
            transform: scale(1.05);
            text-decoration: none;
            color: white;
        }

        .stats-box {
            display: flex;
            justify-content: space-around;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            font-size:15px;
        }

        .stat-item {
            text-align: center;
            cursor: pointer;
            padding: 10px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            background: rgba(207, 229, 191, 0.3);
        }

        .stat-item.active {
            background: linear-gradient(135deg,rgb(207, 229, 191) 0%,rgb(122, 159, 119) 100%);
            color: white;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color:rgb(60, 79, 51);
            display: block;
        }

        .stat-item.active .stat-number {
            color: white;
        }

        .stat-label {
            color: #7f8c8d;
            font-weight: bold;
            margin-top: 5px;
        }

        .stat-item.active .stat-label {
            color: white;
        }

        .icon {
            margin-left: 8px;
            font-size: 1.1em;
        }

        /* כפתור ביטול */
        .cancel-button {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            margin-left: 10px;
            transition: all 0.3s ease;
            font-size: 0.9em;
        }

        .cancel-button:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
            transform: scale(1.05);
        }

        .cancel-button:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .cancel-button:disabled:hover {
            transform: none;
        }

        .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .status-upcoming {
            background-color: #f39c12;
            color: white;
        }

        .status-past {
            background-color: #7f8c8d;
            color: white;
        }

        .status-today {
            background-color: #e74c3c;
            color: white;
        }

        /* סגנון למקטעים שמוסתרים */
        .section.hidden {
            display: none;
        }

        .points-badge {
            background-color: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="main-box">
        <a href="profile.php" class="back-button">←Back To Profile</a>
        
        <div class="header">
            <h1>My Registerations</h1>
            <div class="user-info">Hello <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></div>
        </div>

        <!-- סטטיסטיקות עם כפתורי סינון -->
        <div class="stats-box">
            <div class="stat-item active" onclick="filterRegistrations('all')" id="allFilter">
                <span class="stat-number"><?php echo $trainingResults->num_rows + $eventResults->num_rows; ?></span>
                <div class="stat-label">All</div>
            </div>
            <div class="stat-item" onclick="filterRegistrations('trainings')" id="trainingsFilter">
                <span class="stat-number"><?php echo $trainingResults->num_rows; ?></span>
                <div class="stat-label">Trainings</div>
            </div>
            <div class="stat-item" onclick="filterRegistrations('events')" id="eventsFilter">
                <span class="stat-number"><?php echo $eventResults->num_rows; ?></span>
                <div class="stat-label">Events</div>
            </div>
        </div>

        <!-- הרשמות לאימונים -->
        <div class="section" id="trainingsSection">
            <div class="section-title">
                Training Registerations
                <span class="icon">🏋️</span>
            </div>
            
            <?php if ($trainingResults->num_rows > 0): ?>
                <div class="registrations-grid">
                    <?php while ($training = $trainingResults->fetch_assoc()): 
                        // בדיקה אם האימון עדיין בעתיד ויש יותר מ-4 שעות
                        $trainingDateTime = $training['Date'] . ' ' . $training['Time'];
                        $trainingTimestamp = strtotime($trainingDateTime);
                        $currentTimestamp = time();
                        $hoursUntilTraining = ($trainingTimestamp - $currentTimestamp) / 3600;
                        $canCancel = $hoursUntilTraining > 4; // רק אם יש יותר מ-4 שעות
                        
                        // סטטוס האימון
                        $status = '';
                        $statusClass = '';
                        if (date('Y-m-d') == $training['Date']) {
                            $status = 'Today';
                            $statusClass = 'status-today';
                        } elseif ($trainingTimestamp > $currentTimestamp) {
                            $status = 'Upcoming';
                            $statusClass = 'status-upcoming';
                        } else {
                            $status = 'Completed';
                            $statusClass = 'status-past';
                        }
                    ?>
                        <div class="registration-card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo htmlspecialchars($training['trainingName']); ?></h3>
                                <div>
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="info-row">
                                    <span class="info-label">Trainer:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($training['trainer_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Date:</span>
                                    <span class="date-badge"><?php echo date('d/m/Y', strtotime($training['Date'])); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Time:</span>
                                    <span class="info-value"><?php echo date('H:i', strtotime($training['Time'])); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Duration:</span>
                                    <span class="info-value"><?php echo $training['Duration']; ?> Min</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Registration Date:</span>
                                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($training['reg_date'])); ?></span>
                                </div>
                                
                                <div class="card-actions">
                                    <div class="price">₪<?php echo number_format($training['price'], 2); ?></div>
                                    
                                    <?php if ($canCancel): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirmCancel()">
                                            <input type="hidden" name="cancel_training" value="1">
                                            <input type="hidden" name="registration_num" value="<?php echo $training['registerationNum']; ?>">
                                            <input type="hidden" name="training_num" value="<?php echo $training['trainingNum']; ?>">
                                            <button type="submit" class="cancel-button">Cancel Registration</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-registrations">
                    <h3>No Training Registrations</h3>
                    <p>You haven't signed up for any training sessions yet. Let's get started!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- הרשמות לאירועים -->
        <div class="section" id="eventsSection">
            <div class="section-title">
                Event Registerations
                <span class="icon">🎉</span>
            </div>
            
            <?php if ($eventResults->num_rows > 0): ?>
                <div class="registrations-grid">
                    <?php while ($event = $eventResults->fetch_assoc()): ?>
                        <div class="registration-card event-card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo htmlspecialchars($event['eventName']); ?></h3>
                            </div>
                            <div class="card-content">
                                <div class="info-row">
                                    <span class="info-label">Date:</span>
                                    <span class="date-badge"><?php echo date('d/m/Y', strtotime($event['Date'])); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Location:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($event['Location']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Registration Date:</span>
                                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($event['reg_date'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-registrations">
                    <h3>No Event Registrations</h3>
                    <p>You haven't signed up for any events yet. Let's join some exciting ones!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmCancel() {
    return confirm('Are you sure you want to cancel this training registration?\n\nNote:\n• You can only cancel if there are more than 4 hours until the training\n• 10 points will be deducted as cancellation penalty\n• Points used for this registration will NOT be returned\n\nThis action cannot be undone.');
}

function filterRegistrations(type) {
    const trainingsSection = document.getElementById('trainingsSection');
    const eventsSection = document.getElementById('eventsSection');
    const allFilter = document.getElementById('allFilter');
    const trainingsFilter = document.getElementById('trainingsFilter');
    const eventsFilter = document.getElementById('eventsFilter');
    
    // הסרת המחלקה active מכל הכפתורים
    allFilter.classList.remove('active');
    trainingsFilter.classList.remove('active');
    eventsFilter.classList.remove('active');
    
    switch(type) {
        case 'all':
            // הצגת הכל
            trainingsSection.classList.remove('hidden');
            eventsSection.classList.remove('hidden');
            allFilter.classList.add('active');
            break;
        case 'trainings':
            // הצגת אימונים בלבד
            trainingsSection.classList.remove('hidden');
            eventsSection.classList.add('hidden');
            trainingsFilter.classList.add('active');
            break;
        case 'events':
            // הצגת אירועים בלבד
            trainingsSection.classList.add('hidden');
            eventsSection.classList.remove('hidden');
            eventsFilter.classList.add('active');
            break;
    }
}
</script>

</body>
</html>