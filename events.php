<?php
session_start();
$con = new mysqli("localhost", "root", "", "fithub");
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['userId'];
$id = $userId['userId'];
$firstName = $_SESSION['FirstName'];


// מחיקת אירועים שעבר זמנם
$con->query("DELETE FROM events WHERE Date < NOW()");

// טיפול בהרשמה
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register'])) {
    $eventId = $_POST['eventId'];

    // בדיקה אם המשתמש כבר נרשם
    $checkSql = "SELECT * FROM eventregisteration WHERE userId = ? AND eventId = ?";
    $stmt = $con->prepare($checkSql);
    $stmt->bind_param("ii", $id, $eventId);
    $stmt->execute();
    $checkResult = $stmt->get_result();

    if ($checkResult->num_rows === 0) {
        // הכנסת הרשמה חדשה
        $insertSql = "INSERT INTO eventregisteration (userId, eventId, date) VALUES (?, ?, NOW())";
        $stmt = $con->prepare($insertSql);
        $stmt->bind_param("ii", $id, $eventId);
        $stmt->execute();
    }
}

// שליפת כל האירועים והאם המשתמש כבר נרשם
$sql = "SELECT 
    events.*, 
    (SELECT COUNT(*) 
    FROM eventregisteration 
    WHERE eventregisteration.userId = $id 
    AND eventregisteration.eventId = events.eventId) AS is_registered 
    FROM events 
    ORDER BY events.Date ASC";
$result = $con->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>אירועים</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: url('images/gym.jpeg') no-repeat center center fixed;
            background-size: cover;
            font-family:Times New Roman;
            color: #fff;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        .event {
            display: flex;
            flex-direction: row;
            align-items: center;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 15px;
            overflow: hidden;
            margin: 15px auto;
            width: 900px;
            padding: 10px;
        }

        .event img {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border: solid black 0.5px;
            border-radius:10px
        }

        .event-details {
            flex: 1;
            padding: 20px;
        }

        .event-details h2 {
            margin-top: 0;
            margin-bottom: 10px;
        }

        .info-item {
            margin: 5px 0;
        }

        .event form {
            margin-left: 20px;
        }

        .register-btn {
            background: none;
            border: 2px solid #fff;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease;
            margin: 20px;
        }

        .register-btn:hover {
            background-color: #fff;
            color: #1a1a1a;
        }

        .register-btn[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .header {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 27px;
            color: rgb(116, 146, 115);
            margin: 0;
        }
        .btn {
            font-size: 18px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
            border-radius: 8px;
            transition: 0.3s ease;
            color:white;
            }
        .btn:hover {
            border-radius: 8px;
            background-color: rgba(167, 178, 139, 0.86);
        }
        @keyframes fadeSlideIn {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.EventWel {
    max-width: 600px;
    text-align: center;
    margin-left: auto;
    margin-right: auto;
    font-size: 23px;
    color: black;
    margin-bottom: 20px;
    margin-top: 20px;
    padding: 20px;
    border-radius: 15px;
    animation: fadeSlideIn 1.2s ease-out;
    /*font-family:cooper*/
}
 
    </style>
</head>
<body>
    <div class="header">
        <h1>Events</h1>
        <div class="header-content">
            <button class="btn" onclick="location.href='home.php'"><i class="fas fa-home"></i></button>
        </div>
    </div>
    <div class="EventWel">
        Welcome <?php echo htmlspecialchars($firstName); ?>
        <br>to our event space, where inspiration, energy, and community come together.<br>
        Discover what’s happening next!<br>
        Don’t miss out on special workouts, challenges, and fitness events designed to push your limits and bring us together.
    </div>

    <?php while ($row = $result->fetch_assoc()) { ?>
        <div class="event">
            <img src="<?php echo $row['eventImg']; ?>" alt="img">
            <div class="event-details">
                <h2><?php echo $row['eventName']; ?></h2>
                <div class='info-item'><span class='info-icon'>📝</span> <?php echo $row['Description']; ?></div>
                <div class='info-item'><span class='info-icon'>📍</span> <?php echo $row['Location']; ?></div>
                <div class='info-item'><span class='info-icon'>📅</span> <?php echo date('d/m/Y H:i', strtotime($row['Date'])); ?></div>
            </div>
            <form method="POST">
                <input type="hidden" name="eventId" value="<?php echo $row['eventId']; ?>">
                <button class="register-btn" type="submit" name="register"
                    <?php echo ($row['is_registered'] > 0) ? 'disabled' : ''; ?>>
                    <?php echo ($row['is_registered'] > 0) ? 'already register' : 'register'; ?>
                </button>
            </form>
        </div>
    <?php } ?>
</body>
</html>
