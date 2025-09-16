<?php
$con = new mysqli("localhost", "root", "", "fithub");
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
session_start();
if (!isset($_SESSION['FirstName']) || !isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION['userId'];
$trainerName = $_SESSION['FirstName'];

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// פונקציה לשליחת הודעת עדכון אימון
function sendTrainingUpdateEmail($email, $firstName, $trainingName, $updateDetails) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'taimakizel18@gmail.com';
        $mail->Password = 'ihiw lpel zlzh ucya';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('noreply@fithub.com', 'FitHub');
        $mail->addAddress($email, $firstName);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'update you training - ' . $trainingName;
        $mail->Body = "
        <!DOCTYPE html>
        <html dir='rtl'>
        <head><meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; }
            .container { max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
            .header { background: #4e684f; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; margin: -30px -30px 30px -30px; }
            .update-box { background: #e3f2fd; border: 2px solid #2196f3; padding: 20px; margin: 20px 0; border-radius: 8px; }
        </style></head>
        <body>
            <div class='container'>
                <div class='header'><h1>🏋️ Update Training - FitHub</h1></div>
                <p>Hello $firstName,</p>
                <p>We would like to inform you that there have been changes to the training <strong>$trainingName</strong> you are registered for.</p>
                <div class='update-box'><h3>📝 Update Details:</h3>$updateDetails</div>
                <p>Please review the new details and make sure they are suitable for you.</p>
            </div>
        </body></html>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// פונקציה לשליחת הודעת ביטול אימון
function sendTrainingCancellationEmail($email, $firstName, $trainingName, $refundAmount) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'taimakizel18@gmail.com';
        $mail->Password = 'ihiw lpel zlzh ucya';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('noreply@fithub.com', 'FitHub');
        $mail->addAddress($email, $firstName);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Cancel Training - ' . $trainingName;
        $mail->Body = "
        <!DOCTYPE html>
        <html dir='rtl'>
        <head><meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; }
            .container { max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
            .header { background: #d32f2f; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; margin: -30px -30px 30px -30px; }
            .cancellation-box { background: #ffebee; border: 2px solid #f44336; padding: 20px; margin: 20px 0; border-radius: 8px; }
            .refund-box { background: #e8f5e8; border: 2px solid #4caf50; padding: 15px; margin: 20px 0; border-radius: 8px; text-align: center; }
        </style></head>
        <body>
            <div class='container'>
                <div class='header'><h1>❌ Training Cancellation - FitHub</h1></div>
                <p>Hello $firstName,</p>
                <div class='cancellation-box'>
                    <h3>🚫 Cancellation Notice</h3>
                    <p>Unfortunately, the training <strong>$trainingName</strong> you registered for has been cancelled by the trainer.</p>
                </div>
                <div class='refund-box'>
                    <h3>💰 Refund</h3>
                    <p><strong>Refund Amount: ₪$refundAmount</strong></p>
                    <p>The amount will be refunded to you shortly.</p>
                </div>
                <p>We apologize for any inconvenience caused.</p>
             </div>

        </body></html>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// פונקציה לשליחת הודעות לכל הנרשמים לאימון - תוקנה
function notifyRegisteredUsers($trainingNum, $notificationType, $con, $updateDetails = null) {
    $trainingQuery = "SELECT trainingName FROM training WHERE trainingNum = ?";
    $trainingStmt = $con->prepare($trainingQuery);
    $trainingStmt->bind_param("i", $trainingNum);
    $trainingStmt->execute();
    $trainingResult = $trainingStmt->get_result();
    $training = $trainingResult->fetch_assoc();
    
    if (!$training) return 0;
    
    $usersQuery = "SELECT u.FirstName, u.Email, r.final_price 
                   FROM registeration r 
                   JOIN users u ON r.userId = u.userId 
                   WHERE r.trainingNum = ?";
    $usersStmt = $con->prepare($usersQuery);
    $usersStmt->bind_param("i", $trainingNum);
    $usersStmt->execute();
    $usersResult = $usersStmt->get_result();
    
    $successCount = 0;
    while ($user = $usersResult->fetch_assoc()) {
        $emailSent = false;
        if ($notificationType === 'update') {
            $emailSent = sendTrainingUpdateEmail($user['Email'], $user['FirstName'], $training['trainingName'], $updateDetails);
        } elseif ($notificationType === 'cancellation') {
            $emailSent = sendTrainingCancellationEmail($user['Email'], $user['FirstName'], $training['trainingName'], $user['final_price']);
        }
        if ($emailSent) $successCount++;
    }
    return $successCount;
}

// פונקציה ליצירת תיאור שינויים
function generateUpdateDescription($oldData, $newData) {
    $changes = [];
    $fieldsToCheck = [
        'trainingName' => 'Training Name',
        'Description' => 'Description',
        'Duration' => 'Duration (minutes)',
        'Location' => 'Location',
        'Date' => 'Date',
        'Time' => 'Time',
        'Level' => 'Level',
        'Goal' => 'Goal',
        'maxParticipants' => 'Max Participants',
        'Price' => 'Price'
    ];

    foreach ($fieldsToCheck as $field => $label) {
        if (isset($oldData[$field], $newData[$field]) && $oldData[$field] != $newData[$field]) {
            $changes[] = "<p><strong>$label:</strong> from <em>{$oldData[$field]}</em> to <em>{$newData[$field]}</em></p>";
        }
    }

    if (empty($changes)) {
        return "<p>No changes were made.</p>";
    }

    return "<ul><li>" . implode("</li><li>", $changes) . "</li></ul>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitHuB - Trainer Managment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family:Times New Roman;
            color: #fff;
            margin: 0;
            padding: 0;
            background: url('images/gym.jpeg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
        }
        .header {
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 24px;
            color:rgb(255, 255, 255);
            margin: 0;
        }
        .home-btn {
            font-size: 18px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
            border-radius: 8px;
            transition: 0.3s ease;
            color:white;
        }
        .home-btn:hover {
            border-radius: 8px;
            background-color: rgba(167, 178, 139, 0.86);
        }
        .container {
            display: flex;
            justify-content: center;
            padding: 40px 20px;
            width: 800px;
        }
        .container form {
            background-color:rgba(255, 255, 255, 0.91);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            margin-bottom: 20px;
        }
        .container form h2 {
            color:rgb(0, 0, 0);
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
        }
        label {
            font-weight: bold;
            color:black;
            margin-bottom: 5px;
        }
        input, textarea, select {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: none;
            background-color:rgba(202, 201, 201, 0.45);
            color: rgba(0, 0, 0, 0.5);
            font-size: 16px;
        }
        button {
            background-color:rgb(205, 232, 191);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            margin-right: 10px;
            color:black;
        }
        button:hover {
            background-color:rgb(229, 241, 220);
        }
        table {
            background-color:rgba(197, 197, 197, 0.48);
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            text-align: center;
            color:black;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #444;
        }
        th {
            background-color:rgba(202, 223, 186, 0.63);
        }
        tr:nth-child(even) {
            background-color:rgb(213, 213, 210);
        }
        td img {
            max-width: 100px;
            height: auto;
            border-radius: 8px;
        }
        .con{
            display: flex;
            justify-content: center;
        }
        .p{
            color:black;
        }
        .edit-btn, .delete-btn {
            background-color:rgb(186, 213, 170);
            border: none;
            color: #000;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .edit-btn:hover {
            background-color:rgb(196, 223, 177);
        }
        
        .delete-btn:hover {
            background-color:rgb(196, 223, 177);
        }
        .butt{
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Welcome <?php echo $trainerName; ?> ✔️</h1>
    <div class="butt">
        <button class="home-btn" onclick="location.href='home.php'"><i class="fas fa-home"></i></button>
        <button class="home-btn" onclick="location.href='profile.php'"><i class="fas fa-user"></i></button>
    </div>
</div>
<table>
    <thead>
        <tr>
            <th>Training Name</th>
            <th>Description</th>
            <th>Duration</th>
            <th>Location</th>
            <th>Date</th>
            <th>Time</th>
            <th>Level</th>
            <th>Goal</th>
            <th>Participants</th>
            <th>Max Participants</th>
            <th>Type</th>
            <th>Image</th>
            <th>Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $id=$userId['userId'];
    $sql = "SELECT * FROM training WHERE TrainerId=$id";
    $result = $con->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["trainingName"] . "</td>";
            echo "<td>" . $row["Description"] . "</td>";
            echo "<td>" . $row["Duration"] . "</td>";
            echo "<td>" . $row["Location"] . "</td>";
            echo "<td>" . $row["Date"] . "</td>";
            echo "<td>" . $row["Time"] . "</td>";
            echo "<td>" . $row["Level"] . "</td>";
            echo "<td>" . $row["Goal"] . "</td>";
            echo "<td>" . $row["Participants"] . "</td>";
            echo "<td>" . $row["maxParticipants"] . "</td>";
            echo "<td>" . $row["Type"] . "</td>";
            echo "<td><img src='" . $row["img"] . "' alt='Training Image'></td>";
            echo "<td>" . $row["Price"] . "</td>";
            echo "<td>
                        <form method='POST'>
                            <input type='hidden' name='edit_training' value='" . htmlspecialchars($row['trainingNum']) . "'>
                            <button type='submit' class='edit-btn' name='find_training'>Edit</button>
                        </form>
                        <br>
                        <form method='POST' onsubmit=\"return confirm('Confirm Deleting?');\">
                            <input type='hidden' name='delete_training' value='" . htmlspecialchars($row['trainingNum']) . "'>
                            <button type='submit' class='delete-btn' >Delete</button>
                        </form>
                      </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='13'>No records found</td></tr>";
    }
    
    // מחיקת אימון עם הודעות מייל
    if (isset($_POST['delete_training'])) {
        $trainingNum = $_POST['delete_training'];
        
        $con->begin_transaction();
        
        // שליפת פרטי האימון
        $trainingInfoStmt = $con->prepare("SELECT Price FROM training WHERE trainingNum = ? AND TrainerId = ?");
        $trainingInfoStmt->bind_param("is", $trainingNum, $userId['userId']);
        $trainingInfoStmt->execute();
        $trainingInfo = $trainingInfoStmt->get_result()->fetch_assoc();
        
        if ($trainingInfo) {
            // שליחת הודעות לנרשמים לפני המחיקה
            $notifiedUsers = notifyRegisteredUsers($trainingNum, 'cancellation', $con);
            
            // שליפת כל ההרשמות לאימון זה
            $registrationsStmt = $con->prepare("SELECT userId, final_price FROM registeration WHERE trainingNum = ?");
            $registrationsStmt->bind_param("i", $trainingNum);
            $registrationsStmt->execute();
            $registrations = $registrationsStmt->get_result();
            
            // הוספת ביטולים לכל הנרשמים עם החזר מלא
            while ($registration = $registrations->fetch_assoc()) {
                $cancellationStmt = $con->prepare("INSERT INTO cancellations (userId, trainingNum, cancellation_date, refund_amount) VALUES (?, ?, NOW(), ?)");
                $cancellationStmt->bind_param("sid", $registration['userId'], $trainingNum, $registration['final_price']);
                $cancellationStmt->execute();
            }
            
            // מחיקת ההרשמות
            $deleteRegistrationsStmt = $con->prepare("DELETE FROM registeration WHERE trainingNum = ?");
            $deleteRegistrationsStmt->bind_param("i", $trainingNum);
            $deleteRegistrationsStmt->execute();
            
            // מחיקת האימון
            $stmt = $con->prepare("DELETE FROM training WHERE trainingNum = ? AND TrainerId = ?");
            $stmt->bind_param("is", $trainingNum, $userId['userId']);
            
            if ($stmt->execute()) {
                $con->commit();
                echo "<script>alert('Training deleted successfully. All participants have been refunded and $notifiedUsers users were notified.'); window.location.href='trainer.php';</script>";
            } else {
                $con->rollback();
                echo "<p style='color:red'>Error: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            $con->rollback();
            echo "<script>alert('Training not found or not authorized');</script>";
        }
    }
    ?>
    </tbody>
</table>

<div class="con">
    <div class="container">
        <form method="post"  enctype="multipart/form-data">
            <h2>Add Training</h2>

            <label>Training Name:</label>
            <input type="text" name="trainingName" required>

            <label>Description:</label>
            <textarea name="description" required></textarea>

            <label>Duration (minutes):</label>
            <input type="number" name="duration" required>

            <label>Location:</label>
            <input type="text" name="location" required>

            <label>Date:</label>
            <input type="date" name="date" required>

            <label>Time:</label>
            <input type="time" name="time" required>

            <label>Level:</label>
            <select name="level" required>
                <option value="">-- Select Level --</option>
                <option value="Beginners">Beginners</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
                <option value="All">All</option>
            </select>

            <label>Goal:</label>
            <select name="goal" required>
                <option value="">-- Select Goal --</option>
                <option value="Fat Burn">Fat Burn</option>
                <option value="Muscle Gain">Muscle Gain</option>
                <option value="Weight Gain">Weight Gain</option>
                <option value="Stretching">Stretching</option>
            </select>

            <label>Max Participants:</label>
            <input type="number" name="maxParticipants" required>

            <label>Type:</label>
            <select name="type" required>
                <option value="">-- Select Type --</option>
                <?php
                $typesResult = $con->query("SELECT * FROM types");
                while ($row = $typesResult->fetch_assoc()) {
                    echo "<option value='" . $row['typeId'] . "'>" . htmlspecialchars($row['typeName']) . "</option>";
                }
                ?>
            </select>

            <label>Image:</label>
            <input type="file" name="img" required>

            <label>Price:</label>
            <input type="number" step="0.01" name="price" required>

            <button type="submit" name="addTraining">Add Training</button>
        </form>
        <?php
    if (isset($_POST['addTraining'])) { 
        // קבלת הנתונים מהטופס
        $trainingName = $_POST['trainingName'];
        $description = $_POST['description'];
        $duration = $_POST['duration'];
        $location = $_POST['location'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $level = $_POST['level'];
        $goal = $_POST['goal'];
        $maxParticipants = $_POST['maxParticipants'];
        $type = $_POST['type'];
        if (!empty($_FILES['img']['name'])) {
            $img_name = $_FILES['img']['name'];
            $img_tmp = $_FILES['img']['tmp_name'];
            $img_path = "uploads/" . basename($img_name);
            move_uploaded_file($img_tmp, $img_path);
        } else {
            $img_path = ""; // אם אין תמונה
        }
        $price = $_POST['price'];
        $trainerId = $userId['userId']; 
        if (empty($trainingName) || empty($description) || empty($duration) || 
            empty($location) || empty($date) || empty($time) || 
            empty($level) || empty($goal) || empty($type) || empty($img_name) || empty($price)) {
                    
            echo "<script>alert('Fill all inputs');</script>";
            exit;
        }


        // בדיקה שהתאריך והשעה לא בעבר
        $selectedDateTime = $date . ' ' . $time;
        $currentDateTime = date('Y-m-d H:i');
        
        if ($selectedDateTime <= $currentDateTime) {
            echo "<script>alert('You cannot schedule a training for a past date or time');</script>";
            exit;
        }

    // הכנת השאילתה
    $stmt = $con->prepare("INSERT INTO training (trainingName, Description, Duration, Location, Date, Time, Level, Goal, maxParticipants, Type, img, Price, TrainerId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        // קישור הפרמטרים
        $stmt->bind_param("ssisssssiisdi", 
            $trainingName,    // s - string
            $description,     // s - string  
            $duration,        // i - integer
            $location,        // s - string
            $date,           // s - string
            $time,           // s - string
            $level,          // s - string
            $goal,           // s - string
            $maxParticipants, // i - integer
            $type,           // i - integer
            $img_path,       // s - string
            $price,          // d - double/decimal
            $trainerId       // i - integer
        );

        // ביצוע השאילתה
        if ($stmt->execute()) {
            echo "<script>
                alert('Training added successfully!');
                window.location.href = 'trainer.php';
            </script>";
        } else {
            echo "<script>alert('Error adding the training: " . $stmt->error . "');</script>";
        }

        $stmt->close();
        } else {
            echo "<script>alert('Error preparing the query: " . $con->error . "');</script>";
        }
}
?>
    </div>
    <?php
if (isset($_POST['find_training'])) {
    $editTrainingId = $_POST['edit_training'];
    $stmt = $con->prepare("SELECT * FROM training WHERE trainingNum = ?");
    $stmt->bind_param("i", $editTrainingId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $editRow = $result->fetch_assoc();
        ?>
        <div class="con">
            <div class="container">
                <form method="post" enctype="multipart/form-data">
                    <h2>Edit Training</h2>
                    <input type="hidden" name="update_training_id" value="<?php echo $editRow['trainingNum']; ?>">

                    <label>Training Name:</label>
                    <input type="text" name="trainingName" value="<?php echo htmlspecialchars($editRow['trainingName']); ?>" required>

                    <label>Description:</label>
                    <textarea name="description" required><?php echo htmlspecialchars($editRow['Description']); ?></textarea>

                    <label>Duration:</label>
                    <input type="number" name="duration" value="<?php echo $editRow['Duration']; ?>" required>

                    <label>Location:</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($editRow['Location']); ?>" required>

                    <label>Date:</label>
                    <input type="date" name="date" value="<?php echo $editRow['Date']; ?>" required>

                    <label>Time:</label>
                    <input type="time" name="time" value="<?php echo $editRow['Time']; ?>" required>

                    <label>Level:</label>
                    <select name="level" required>
                        <option value="">-- Select Level --</option>
                        <?php
                        $levelOptions = ['Beginners' => 'Beginners','Intermediate' => 'Intermediate', 'Advanced' => 'Advanced', 'All' => 'All'];
                        foreach ($levelOptions as $value => $display) {
                            $selected = ($editRow['Level'] == $value) ? "selected" : "";
                            echo "<option value='$value' $selected>$display</option>";
                        }
                        ?>
                    </select>

                    <label>Goal:</label>
                    <select name="goal" required>
                        <option value="">-- Select Goal --</option>
                        <?php
                        $goalOptions = ['Fat Burn' => 'Fat Burn', 'Muscle Gain' => 'Muscle Gain', 'Weight Gain' => 'Weight Gain', 'Stretching' => 'Stretching'];
                        foreach ($goalOptions as $value => $display) {
                            $selected = ($editRow['Goal'] == $value) ? "selected" : "";
                            echo "<option value='$value' $selected>$display</option>";
                        }
                        ?>
                    </select>

                    <label>Participants:</label>
                    <input type="number" name="participants" value="<?php echo $editRow['Participants']; ?>" required>

                    <label>Max Participants:</label>
                    <input type="number" name="maxParticipants" value="<?php echo $editRow['maxParticipants']; ?>" required>

                    <label>Type:</label>
                    <select name="type" required>
                        <?php
                        $typesResult = $con->query("SELECT * FROM types");
                        while ($typeRow = $typesResult->fetch_assoc()) {
                            $selected = ($typeRow['typeId'] == $editRow['Type']) ? "selected" : "";
                            echo "<option value='" . $typeRow['typeId'] . "' $selected>" . htmlspecialchars($typeRow['typeName']) . "</option>";
                        }
                        ?>
                    </select>

                    <label>Image (leave empty to keep current):</label>
                    <input type="file" name="img">

                    <label>Price:</label>
                    <input type="number" step="0.01" name="price" value="<?php echo $editRow['Price']; ?>" required>

                    <button type="submit" name="updateTraining">Update Training</button>
                </form>
            </div>
        </div>
        <?php
    }
    $stmt->close();
}
?>

</div>
<?php
// עדכון אימון עם הודעות מייל - תוקן
if (isset($_POST['updateTraining'])) {
    $id = $_POST['update_training_id'];
    
    // שליפת הנתונים הישנים
    $oldDataQuery = "SELECT * FROM training WHERE trainingNum = ?";
    $oldDataStmt = $con->prepare($oldDataQuery);
    $oldDataStmt->bind_param("i", $id);
    $oldDataStmt->execute();
    $oldDataResult = $oldDataStmt->get_result();
    $oldData = $oldDataResult->fetch_assoc();
    
    $name = $_POST['trainingName'];
    $desc = $_POST['description'];
    $duration = $_POST['duration'];
    $location = $_POST['location'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $level = $_POST['level'];
    $goal = $_POST['goal'];
    $participants = $_POST['participants'];
    $maxParticipants = $_POST['maxParticipants'];
    $type = $_POST['type'];
    $price = $_POST['price'];

    if (!empty($_FILES['img']['name'])) {
        $img_name = $_FILES['img']['name'];
        $img_tmp = $_FILES['img']['tmp_name'];
        $img_path = "uploads/" . basename($img_name);
        move_uploaded_file($img_tmp, $img_path);
    } else {
        $img_path = $oldData['img'];
    }

    $stmt = $con->prepare("UPDATE training SET trainingName=?, Description=?, Duration=?, Location=?, Date=?, Time=?, Level=?, Goal=?, Participants=?, maxParticipants=?, Type=?, img=?, Price=? WHERE trainingNum=?");
    $stmt->bind_param("ssisssssssisdi", $name, $desc, $duration, $location, $date, $time, $level, $goal, $participants, $maxParticipants, $type, $img_path, $price, $id);

    if ($stmt->execute()) {
        $newData = [
            'trainingName' => $name, 'Description' => $desc, 'Duration' => $duration,
            'Location' => $location, 'Date' => $date, 'Time' => $time,
            'Level' => $level, 'Goal' => $goal, 'maxParticipants' => $maxParticipants, 'Price' => $price
        ];
        
        $updateDetails = generateUpdateDescription($oldData, $newData);
        $notifiedUsers = notifyRegisteredUsers($id, 'update', $con, $updateDetails);
        
        echo "<script>
            alert('The training has been updated successfully! Notifications were sent to $notifiedUsers registered users.');
            window.location.href='trainer.php';
        </script>";
    } else {
        echo "<p style='color:red'>Error: " . $stmt->error . "</p>";
    }
    $stmt->close();
}
?>

</body>
<script>
// קביעת תאריך מינימלי (היום)
function setMinDate() {
    const today = new Date();
    const todayString = today.toISOString().split('T')[0];
    
    // קביעת תאריך מינימלי לכל שדות התאריך
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.min = todayString;
    });
}

// בדיקת שעה בהתאם לתאריך שנבחר
function validateTime() {
    const dateInput = document.querySelector('input[name="date"]');
    const timeInput = document.querySelector('input[name="time"]');
    
    if (dateInput && timeInput) {
        dateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            
            // איפוס התאריך של היום כדי להשוות רק תאריכים
            today.setHours(0, 0, 0, 0);
            selectedDate.setHours(0, 0, 0, 0);
            
            if (selectedDate.getTime() === today.getTime()) {
                // אם נבחר היום - קבע שעה מינימלית לשעה הנוכחית
                const now = new Date();
                const currentHour = now.getHours().toString().padStart(2, '0');
                const currentMinute = now.getMinutes().toString().padStart(2, '0');
                const currentTime = `${currentHour}:${currentMinute}`;
                
                timeInput.min = currentTime;
            } else {
                // אם נבחר תאריך עתידי - אין הגבלת שעה
                timeInput.min = "";
            }
        });
        
        // בדיקה נוספת בעת שינוי השעה
        timeInput.addEventListener('change', function() {
            const selectedDate = new Date(dateInput.value);
            const today = new Date();
            
            today.setHours(0, 0, 0, 0);
            selectedDate.setHours(0, 0, 0, 0);
            
            if (selectedDate.getTime() === today.getTime()) {
                const now = new Date();
                const selectedTime = this.value.split(':');
                const selectedHour = parseInt(selectedTime[0]);
                const selectedMinute = parseInt(selectedTime[1]);
                
                if (selectedHour < now.getHours() || 
                    (selectedHour === now.getHours() && selectedMinute < now.getMinutes())) {
                    
                    alert('לא ניתן לבחור שעה שכבר עברה');
                    this.value = '';
                }
            }
        });
    }
}

// פונקציה לבדיקה לפני שליחת הטופס
function validateBeforeSubmit(event) {
    const dateInput = document.querySelector('input[name="date"]');
    const timeInput = document.querySelector('input[name="time"]');
    
    if (dateInput && timeInput) {
        const selectedDate = new Date(dateInput.value + 'T' + timeInput.value);
        const now = new Date();
        
        if (selectedDate <= now) {
            event.preventDefault();
            alert('You cannot schedule a training for a past date or time');
            return false;
        }
    }
    
    return true;
}

// הפעלה כשהעמוד נטען
document.addEventListener('DOMContentLoaded', function() {
    // קביעת תאריך מינימלי
    const today = new Date();
    const todayString = today.toISOString().split('T')[0];
    
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.min = todayString;
    });
    
    // הוספת אירועים לבדיקת שעה
    validateTime();
    
    // הוספת בדיקה לטפסים
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const hasDateInput = form.querySelector('input[name="date"]');
        const hasTimeInput = form.querySelector('input[name="time"]');
        
        if (hasDateInput && hasTimeInput) {
            form.addEventListener('submit', validateBeforeSubmit);
        }
    });
});
</script>
</html>