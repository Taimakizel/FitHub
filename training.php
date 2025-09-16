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
/////////////////////////////////////////////////
// אלגוריתם למציאת סוג האימון המועדף על הלקוח
$preferredTypeQuery = "
    SELECT t.Type, COUNT(*) as count
    FROM registeration r
    JOIN training t ON r.trainingNum = t.trainingNum
    WHERE r.userId = $id
    GROUP BY t.Type
    ORDER BY count DESC
    LIMIT 1
";
$preferredTypeResult = $con->query($preferredTypeQuery);
$preferredType = null;
if ($preferredTypeResult && $preferredTypeResult->num_rows > 0) {
    $preferredTypeRow = $preferredTypeResult->fetch_assoc();
    $preferredType = $preferredTypeRow['Type'];
}

$typeResult = $con->query("SELECT * FROM types");

// שינוי החלק הזה - הוספת תנאי תאריך ושעה
$filter = "WHERE CONCAT(Date, ' ', Time) >= NOW()"; // רק אימונים שטרם התחילו

if (!empty($_POST['level']) && is_array($_POST['level'])) {
    $levels = array_map([$con, 'real_escape_string'], $_POST['level']);
    $levelList = "'" . implode("','", $levels) . "'";
    $filter .= " AND Level IN ($levelList)";
}

if (!empty($_POST['goal']) && is_array($_POST['goal'])) {
    $goals = array_map([$con, 'real_escape_string'], $_POST['goal']);
    $goalList = "'" . implode("','", $goals) . "'";
    $filter .= " AND Goal IN ($goalList)";
}

if (!empty($_POST['type']) && is_array($_POST['type'])) {
    $types = array_map('intval', $_POST['type']);
    $typeList = implode(",", $types);
    $filter .= " AND Type IN ($typeList)";
}

// הוספת מיון לפי העדפת הלקוח ואז לפי תאריך וזמן
$orderBy = "ORDER BY ";
if ($preferredType !== null) {
    $orderBy .= "(CASE WHEN Type = $preferredType THEN 0 ELSE 1 END), ";
}
$orderBy .= "Date ASC, Time ASC";

$query = "SELECT * FROM training $filter $orderBy";
$result = $con->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitHub - Trainings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family:Times New Roman;
            margin: 0;
            padding: 0;
            background: url('images/gym.jpeg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            color: #333;
            min-height: 100vh;
        }
        
        .header {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .header h1 {
            font-size: 28px;
            color: #fff;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }
        
        .header h1 span {
            color:rgb(186, 213, 170);
        }
        
       .btn {
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
.btn:hover {
    background-color: rgba(167, 178, 139, 0.7);
}
        
        .content-wrapper {
            display: flex;
            padding: 30px;
            max-width: 1600px;
            margin: 0 auto;
            gap: 30px;
        }
        
        .sidebar {
            width: 280px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 30px;
            height: fit-content;
        }
        
        .sidebar h2 {
            color: #333;
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 25px;
            text-align: center;
            position: relative;
            padding-bottom: 10px;
        }
        
        .sidebar h2:after {
            content: '';
            position: absolute;
            width: 70px;
            height: 3px;
            background-color: #4CAF50;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 3px;
        }
        
        .filter-section {
            margin-bottom: 25px;
        }
        
        .filter-section > label {
            font-weight: 600;
            color: #333;
            font-size: 20px;
            display: block;
            margin-bottom: 12px;
            position: relative;
            padding-left: 24px;
            
        }
        
        .filter-section > label:before {
            content: '•';
            color: #4CAF50;
            font-size: 24px;
            position: absolute;
            left: 0;
            top: -5px;
        }
        
        .checkbox-container {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .checkbox-container input[type="checkbox"] {
            margin-right: 8px;
            accent-color: #4CAF50;
            width: 16px;
            height: 16px;
        }
        
        .checkbox-container label {
            font-size: 17px;
            color: #555;
        }
        
        .sidebar button {
            background-color: rgba(167, 178, 139, 0.7);
            border: none;
            width: 100%;
            padding: 14px 20px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            margin-top: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .sidebar button:hover {
            background-color:rgb(196, 223, 177);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(76, 175, 80, 0.4);
        }
        
        .main {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding:20px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            height: 400px;
            position: relative;
        }
        
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
        }
        
        .card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-bottom: 3px solid rgba(155, 219, 157, 0.83);
        }
        
        .card-content {
            padding: 15px;
            flex-grow: 1;
        }
        
        .card h3 {
            font-size: 18px;
            color: #333;
            margin-top: 0;
            margin-bottom: 10px;
            position: relative;
            padding-bottom: 8px;
        }
        
        .card h3:after {
            content: '';
            position: absolute;
            width: 40px;
            height: 2px;
            background-color:rgb(186, 213, 170);
            bottom: 0;
            left: 0;
        }
        
        .card-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 10px;
            margin-bottom: 10px;
            font-size: 13px;
        }
        
        .info-item {
            font-size: 13px;
            color: #555;
        }
        
        .info-item strong {
            color: #333;
            font-weight: 600;
            margin-right: 5px;
        }
        
        .price-tag {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color:rgb(186, 213, 170);
            padding: 8px 15px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }
        
        .card-footer {
            padding: 15px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: auto;
        }
        
        .select {
            background-color:rgb(186, 213, 170);
            border: none;
            padding: 12px 20px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
        }
        
        .select:hover {
            background-color:rgb(196, 223, 177);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(76, 175, 80, 0.4);
        }
        
        .select[disabled] {
            background-color: #bdbdbd;
            cursor: not-allowed;
            box-shadow: none;
        }
        
        .availability {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .availability-count {
            font-weight: 600;
            margin-right: 10px;
            font-size: 13px;
            color: #333;
        }
        
        .availability-bar {
            flex: 1;
            height: 6px;
            background-color: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .availability-progress {
            height: 100%;
            background-color: #4CAF50;
        }
        
        .availability-low .availability-progress {
            background-color: #f44336;
        }
        
        .empty-state {
            grid-column: 1 / -1;
            background-color: rgba(255, 255, 255, 0.92);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .empty-state h3 {
            color: #333;
            margin-top: 0;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 0;
        }
          .info-icon {
      margin-left: 8px;
      font-size: 1rem;
    }
    </style>
</head>
<body>

<div class="header">
    <h1>FitHub <span>Trainings</span></h1>
    <button class="btn" onclick="location.href='home.php'"><i class="fas fa-home"></i></button>
</div>

<div class="content-wrapper">
    <div class="sidebar">
        <h2>Filter Trainings</h2>
        <form method="post">
            <div class="filter-section">
                <label>Level</label>
                <?php
                $levelOptions = ['beginners' => 'Beginners','intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'All' => 'All'];
                foreach ($levelOptions as $value => $label):
                ?>
                    <div class="checkbox-container">
                        <input type="checkbox" id="level-<?php echo $value; ?>" name="level[]" value="<?php echo $value; ?>"
                            <?php if (!empty($_POST['level']) && in_array($value, $_POST['level'])) echo 'checked'; ?>>
                        <label for="level-<?php echo $value; ?>"><?php echo $label; ?></label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="filter-section">
                <label>Goal</label>
                <?php
                $goalOptions = ['Fat Burn' => 'Fat Burn', 'muscle gain' => 'Muscle Gain', 'weight gain' => 'Weight Gain','stretching'=>'Stretching'];
                foreach ($goalOptions as $value => $label):
                ?>
                    <div class="checkbox-container">
                        <input type="checkbox" id="goal-<?php echo str_replace(' ', '-', $value); ?>" name="goal[]" value="<?php echo $value; ?>"
                            <?php if (!empty($_POST['goal']) && in_array($value, $_POST['goal'])) echo 'checked'; ?>>
                        <label for="goal-<?php echo str_replace(' ', '-', $value); ?>"><?php echo $label; ?></label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="filter-section">
                <label>Type</label>
                <?php 
                $typeResult->data_seek(0); // Reset the result pointer
                while($typeRow = $typeResult->fetch_assoc()): 
                ?>
                    <div class="checkbox-container">
                        <input type="checkbox" id="type-<?php echo $typeRow['typeId']; ?>" name="type[]" value="<?php echo $typeRow['typeId']; ?>"
                            <?php if (!empty($_POST['type']) && in_array($typeRow['typeId'], $_POST['type'])) echo 'checked'; ?>>
                        <label for="type-<?php echo $typeRow['typeId']; ?>"><?php echo htmlspecialchars($typeRow['typeName']); ?></label>
                    </div>
                <?php endwhile; ?>
            </div>

            <button type="submit">Find Your Training</button>
        </form>
    </div>

    <div class="main">
    <?php
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $maxParticipants = $row['maxParticipants'];
            $participants = $row['Participants'];
            $availableSpots = $maxParticipants - $participants;
            $availabilityPercent = ($participants / $maxParticipants) * 100;
            $lowAvailability = $availableSpots <= 2 ? 'availability-low' : '';

            echo "<div class='card'>";
            echo "<div class='price-tag'>" . number_format($row['Price'], 2) . " ₪</div>";
            echo "<img src='" . htmlspecialchars($row['img']) . "' alt='training'>";
            echo "<div class='card-content'>";
            echo "<h3>" . htmlspecialchars($row['trainingName']) . "</h3>";
            
            echo "<div class='card-info'>";
            echo "<div class='info-item'><span class='info-icon'>📅</span> " . htmlspecialchars($row['Date']) . "</div>";
            echo "<div class='info-item'><span class='info-icon'>⏰</span> " . htmlspecialchars($row['Time']) . "</div>";
            echo "<div class='info-item'><span class='info-icon'>⏱️</span> " . htmlspecialchars($row['Duration']) . "</div>";
            echo "<div class='info-item'><span class='info-icon'>📍</span> " . htmlspecialchars($row['Location']) . "</div>";
            echo "<div class='info-item'><strong><span class='info-icon'>🏅</span></strong> " . htmlspecialchars($row['Level']) . "</div>";
            echo "<div class='info-item'><span class='info-icon'>🏋️‍♂️</span> " . htmlspecialchars($row['Goal']) . "</div>";
            echo "</div>";
            
            echo "<div class='availability {$lowAvailability}'>";
            echo "<div class='availability-count'>{$availableSpots} spots left</div>";
            echo "<div class='availability-bar'>";
            echo "<div class='availability-progress' style='width: {$availabilityPercent}%'></div>";
            echo "</div>";
            echo "</div>";
            
            echo "</div>"; // End card-content

            echo "<div class='card-footer'>";
            echo "<form method='post' action='selectedTraining.php' style='width: 100%;'>";
            echo "<input type='hidden' name='trainingNum' value='" . $row['trainingNum'] . "'>";

            if ($availableSpots > 0) {
                echo "<button type='submit' class='select'>Select Training</button>";
            } else {
                echo "<button type='submit' class='select' disabled>Fully Booked</button>";
            }
            

            echo "</form>";
            echo "</div>"; // End card-footer
            echo "</div>"; // End card
        }
    } else {
        echo "<div class='empty-state'>";
        echo "<h3>No Trainings Found</h3>";
        echo "<p>Try adjusting your filters to see more training options.</p>";
        echo "</div>";
    }
    $con->close();
    ?>
    </div>
</div>

</body>
</html>