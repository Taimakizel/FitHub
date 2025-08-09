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
$id=$userId['userId'];
$firstName = $_SESSION['FirstName'];
$lastName = $_SESSION['LastName'];
$email = $_SESSION['Email'];
$phone =$_SESSION['Phone'];
$role = $_SESSION['Role'];

$query = $con->prepare("SELECT image_path FROM users WHERE userId=?");
$query->bind_param("s", $id);
$query->execute();
$result = $query->get_result();
$row = $result->fetch_assoc();
$img = $row['image_path'] ?? 'images/default-user.png';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['weight'])) {
    $weight = $_POST['weight'];
    $date = date('Y-m-d H:i:s');

    $stmt = $con->prepare("INSERT INTO weights (userId, weight, DateRecorded) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $id, $weight, $date);

    if ($stmt->execute()) {
        echo "<script>alert('Update Successfully');</script>";
    } else {
        echo "<script>alert('Update Failed');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {
    $newFirstName = $_POST['first_name'];
    $newLastName = $_POST['last_name'];
    $newEmail = $_POST['email'];
    $newPhone = $_POST['phone'];
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // בדיקת סיסמה אם הוזנה
    $passwordError = '';
    if (!empty($newPassword)) {
        if (strlen($newPassword) < 6) {
            $passwordError = 'Password must be at least 6 characters';
        } elseif ($newPassword !== $confirmPassword) {
            $passwordError = 'Passwords do not match';
        }
    }

    if (!empty($passwordError)) {
        echo "<script>alert('$passwordError');</script>";
    } else {
        // משתנה לשמירת נתיב קובץ התמונה החדש (אם קיים)
        $uploadImagePath = $img; // כברירת מחדל שומר את התמונה הנוכחית

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $targetDir = "uploads/"; // תקייה שתכיל את כל התמונות (וודאי שהיא קיימת!)
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true); // צור תיקייה אם לא קיימת
            }

            $fileName = basename($_FILES["profile_image"]["name"]);
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid("img_") . "." . $fileExtension;
            $targetFile = $targetDir . $newFileName;

            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFile)) {
                $uploadImagePath = $targetFile;
            }
        }

        // עדכון במסד נתונים - כולל סיסמה אם הוזנה
        if (!empty($newPassword)) {
            $stmt = $con->prepare("UPDATE users SET FirstName=?, LastName=?, Email=?, Phone=?, image_path=?, Password=? WHERE userId=?");
            $stmt->bind_param("sssssss", $newFirstName, $newLastName, $newEmail, $newPhone, $uploadImagePath, $newPassword, $id);
        } else {
            $stmt = $con->prepare("UPDATE users SET FirstName=?, LastName=?, Email=?, Phone=?, image_path=? WHERE userId=?");
            $stmt->bind_param("ssssss", $newFirstName, $newLastName, $newEmail, $newPhone, $uploadImagePath, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['FirstName'] = $newFirstName;
            $_SESSION['LastName'] = $newLastName;
            $_SESSION['Email'] = $newEmail;
            $_SESSION['Phone'] = $newPhone;
            
            $successMessage = !empty($newPassword) ? 'Profile and Password Updated Successfully' : 'Profile Updated Successfully';
            echo "<script>alert('$successMessage'); location.href='profile.php';</script>";
        } else {
            echo "<script>alert('Update Failed');</script>";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_card'])) {
    $cardNumber = str_replace(' ', '', $_POST['card_number']); // הסר רווחים
    $expirationDate = $_POST['expiration_date'];
    $cvv = $_POST['cvv'];
    $code = $_POST['code'];

    // המר MM/YY ל YYYY-MM-01
    if (preg_match('/^(\d{2})\/(\d{2})$/', $expirationDate, $matches)) {
        $month = $matches[1];
        $year = '20' . $matches[2];
        $expirationDate = $year . '-' . $month . '-01';
    }

    $stmt = $con->prepare("INSERT INTO card (CardNum, ExpirationDate, CVV, code, userId) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $cardNumber, $expirationDate, $cvv, $code, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Card Added Successfully');</script>";
    } else {
        echo "<script>alert('Failed to Add Card');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_card'])) {
    $cardId = $_POST['card_id'];
    
    $stmt = $con->prepare("DELETE FROM card WHERE CardNum = ? AND userId = ?");
    $stmt->bind_param("ss", $cardId, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Card Deleted Successfully');</script>";
    } else {
        echo "<script>alert('Failed to Delete Card');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>פרופיל משתמש</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            font-family:Times New Roman;
            background: url('images/gym.jpeg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
        }
        .container{
            display:flex;
            justify-content: center;
            align-items: center;
            margin-top:100px;
        }
         .ProfileBox {
            position: relative;
            background: rgba(255, 255, 255, 0);
            backdrop-filter: blur(14px);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 500px;
            color:black;
            text-align: center;
        }

        .ProfileBox input {
            width: 300px;
            padding: 12px;
            margin-bottom:10px;
            margin-top:10px;
            border-radius: 8px;
            border: none;
            background-color:rgba(235, 233, 233, 0.85);
            color: rgba(0, 0, 0, 0.5);
            font-size: 16px;
            text-align:left;
        }

        input::placeholder {
            color: rgb(102, 99, 99);
            text-align: left;

        }

        input:focus {
            outline: none;
            background: rgba(207, 212, 206, 0.9);
        }

        .update {
            width: 100px;
            padding: 12px;
            background: none;
            border: 2px solid #ccc;
            color: #1a1a1a;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease-in-out;
        }

        .update:hover:enabled {
            background-color:  rgba(167, 178, 139, 0.7);
        }

        .update:disabled {
            background: none;
            border: 2px solid #ccc;
            cursor: not-allowed;
        }
        .profile-img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
            margin-bottom: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.4);
        }

        .profile-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .profile-buttons button {
            background-color: rgba(167, 178, 139, 0.7);
            color: #1a1a1a;
            border: none;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            font-size: 14px;
        }

        .profile-buttons button:hover {
            background-color: rgb(229, 241, 220);
        }

     .header {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

    .header h1 {
        font-size: 24px;
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
    .btnCard{
        width: 140px;
        padding: 12px;
        background: none;
        border: 2px solid #ccc;
        color: #1a1a1a;
        border-radius: 10px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s ease-in-out;  
    }
    .btnCard:hover{
        background-color:  rgba(167, 178, 139, 0.7);
    }
    .card-box {
        border: 1px solid #ccc;
        padding: 10px;
        margin: 10px 0;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.1);
        font-size: 18px;

    }
    .btnDelete{
        width: 90px;
        padding: 12px;
        background: none;
        border: 2px solid #ccc;
        color: #1a1a1a;
        border-radius: 10px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s ease-in-out;
    }
    .btnDelete:hover {   
        margin: 5px;
        background-color: #f44336;
    }

    .password-section {
        border-top: 2px solid rgba(167, 178, 139, 0.5);
        margin-top: 20px;
        padding-top: 15px;
    }

    .password-match {
        border: 2px solid #4caf50 !important;
    }

    .password-mismatch {
        border: 2px solid #f44336 !important;
    }

    .password-info {
        font-size: 12px;
        color: rgba(0, 0, 0, 0.6);
        margin-top: 5px;
        text-align: center;
    }

    .strength-indicator {
        height: 4px;
        background: #ddd;
        border-radius: 2px;
        margin: 5px 0;
        overflow: hidden;
    }

    .strength-fill {
        height: 100%;
        transition: all 0.3s;
        border-radius: 2px;
    }

    .strength-weak { background: #f44336; width: 25%; }
    .strength-fair { background: #ff9800; width: 50%; }
    .strength-good { background: #2196f3; width: 75%; }
    .strength-strong { background: #4caf50; width: 100%; }

    </style>
</head>
<body>

<div class="header">
    <h1>Profile Management</h1>
    <div class="header-content">
        <button class="btn" onclick="location.href='home.php'"><i class="fas fa-home"></i></button>
        <button class="btn" onclick="location.href='login.php'"><i class="fas fa-right-from-bracket"></i></button>
    </div>
</div>
<div class="container">
  <div class="ProfileBox">
    <img src="<?php echo htmlspecialchars($img); ?>" class="profile-img" alt="תמונה אישית">
    <h2><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></h2>
    
    <div class="profile-buttons">
        <button onclick="toggleForm('updateForm')">Update Profile</button>
        <?php if ($role == 1): ?>
            <button onclick="location.href='trainer.php'">Add Trainings</button>
        <?php endif; ?>
        <?php if ($role == 2): ?>
            <button onclick="location.href='financial_statistics.php'">Statistics</button>
            <button onclick="location.href='admin.php'">Management</button>
        <?php endif; ?>
        <?php if ($role == 0): ?>
            <button onclick="toggleForm('weightForm')">Weight</button>
            <button onclick="location.href='registerations.php'">Registrations</button>
            <button onclick="location.href='rewards.php'" class="rewards-btn">Rewards</button>
            <button onclick="location.href='payment.php'">Payment Methods</button>
        <?php endif; ?>
    </div>

    <!-- טופס עדכון הפרטים (מוסתר כברירת מחדל) -->
    <form id="updateForm" method="POST" enctype="multipart/form-data" style="display:none;" oninput="checkChanges()">
      <input type="hidden" name="update_profile" value="1">
      <input name="first_name" id="first_name" placeholder="First Name" type="text" value="<?php echo htmlspecialchars($firstName); ?>">
      <input name="last_name" id="last_name" placeholder="Last Name" type="text" value="<?php echo htmlspecialchars($lastName); ?>">
      <input name="email" id="email" placeholder="Email" type="email" value="<?php echo htmlspecialchars($email); ?>">
      <input name="phone" id="phone" placeholder="Phone" type="text" value="<?php echo htmlspecialchars($phone); ?>">
      <input type="file" name="profile_image" accept="image/*" ><br>
      
      <!-- קטע עדכון סיסמה -->
      <div class="password-section">
        <h3 style="margin: 15px 0; color: rgba(0, 0, 0, 0.7);">Change Password (Optional)</h3>
        <input name="new_password" id="new_password" placeholder="New Password (min 6 chars)" type="password" minlength="6">
        <div class="strength-indicator">
          <div class="strength-fill" id="strengthFill"></div>
        </div>
        <div class="password-info" id="strengthText">Enter a password to see strength</div>
        
        <input name="confirm_password" id="confirm_password" placeholder="Confirm New Password" type="password" disabled>
        <div class="password-info" id="matchInfo"></div>
      </div>
      
      <button class="update" type='submit' id="updateBtn" disabled>Update</button>
    </form>
    
    <form method="POST" id="weightForm" style="display:none;">
        <h2>Update Your Weight</h2>
        <label for="weight">Current Weight (Kg) </label><br>
        <input class="weight" type="number-format" name="weight" id="weight" required><br>
        <button class="update" type="submit" >Update</button>
    </form>

    <form method="POST" id="cardForm" style="display:none;">
        <h2>Add Payment Method</h2>
        <input type="hidden" name="add_card" value="1">
        <input name="card_number" id="cardNumber" placeholder="Card Number (16 digits)" type="text" maxlength="19" required><br>
        <input name="expiration_date" id="expiryDate" placeholder="MM/YY" type="text" maxlength="5" required><br>
        <input name="cvv" id="cvvField" placeholder="CVV (3 digits)" type="text" maxlength="3" required><br>
        <input name="code" id="codeField" placeholder="Personal Code (4 digits)" type="text" maxlength="4" required><br>
        <button class="btnCard" type="submit">Add Card</button>
    </form>

    <div id="viewCardsForm" style="display:none;">
        <h2>Your Payment Methods</h2>
        <?php
        $cardsQuery = $con->prepare("SELECT CardNum, ExpirationDate, CVV, code FROM card WHERE userId = ?");
        $cardsQuery->bind_param("s", $id);
        $cardsQuery->execute();
        $cardsResult = $cardsQuery->get_result();
        
        if ($cardsResult->num_rows > 0):
            while($card = $cardsResult->fetch_assoc()):
                $maskedCard = '**** **** **** ' . substr($card['CardNum'], -4);
                // המר תאריך חזרה לפורמט MM/YY להצגה
                $displayDate = date('m/y', strtotime($card['ExpirationDate']));
        ?>
            <div class="card-box">
                <p><strong>Card:</strong> <?php echo $maskedCard; ?></p>
                <p><strong>Expires:</strong> <?php echo $displayDate; ?></p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="delete_card" value="1">
                    <input type="hidden" name="card_id" value="<?php echo $card['CardNum']; ?>">
                    <button class="btnDelete" type="submit" onclick="return confirm('Are you sure you want to delete this card?')" >Delete</button>
                </form>
            </div>
        <?php 
            endwhile;
        else:
        ?>
            <p>No payment methods found.</p>
        <?php endif; ?>
    </div>
  </div>
</div>
 
<script>
    const originalData = {
        first_name: "<?php echo htmlspecialchars($firstName); ?>",
        last_name: "<?php echo htmlspecialchars($lastName); ?>",
        email: "<?php echo htmlspecialchars($email); ?>",
        phone: "<?php echo htmlspecialchars($phone); ?>"
    };

    function checkPasswordStrength(password) {
        if (!password) {
            document.getElementById('strengthFill').className = 'strength-fill';
            document.getElementById('strengthText').textContent = 'Enter a password to see strength';
            return;
        }

        let strength = 0;
        let feedback = 'Weak';
        let className = 'strength-weak';

        if (password.length >= 6) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;

        switch(strength) {
            case 0:
            case 1:
                feedback = 'Very Weak';
                className = 'strength-weak';
                break;
            case 2:
                feedback = 'Weak';
                className = 'strength-weak';
                break;
            case 3:
                feedback = 'Fair';
                className = 'strength-fair';
                break;
            case 4:
                feedback = 'Good';
                className = 'strength-good';
                break;
            case 5:
                feedback = 'Strong';
                className = 'strength-strong';
                break;
        }

        document.getElementById('strengthFill').className = `strength-fill ${className}`;
        document.getElementById('strengthText').textContent = `Password strength: ${feedback}`;
    }

    function checkPasswordMatch() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const matchInfo = document.getElementById('matchInfo');
        const confirmField = document.getElementById('confirm_password');

        if (!confirmPassword) {
            matchInfo.textContent = '';
            confirmField.className = '';
            return;
        }

        if (newPassword === confirmPassword) {
            matchInfo.textContent = '✓ Passwords match';
            matchInfo.style.color = '#4caf50';
            confirmField.className = 'password-match';
        } else {
            matchInfo.textContent = '✗ Passwords do not match';
            matchInfo.style.color = '#f44336';
            confirmField.className = 'password-mismatch';
        }
    }

    function checkChanges() {
        const firstName = document.getElementById('first_name').value;
        const lastName = document.getElementById('last_name').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const imgInput = document.querySelector('input[name="profile_image"]');

        // בדיקה אם יש שינויים בפרטים הבסיסיים
        const basicChanged =
            firstName !== originalData.first_name ||
            lastName !== originalData.last_name ||
            email !== originalData.email ||
            phone !== originalData.phone ||
            (imgInput && imgInput.files.length > 0);

        // בדיקה אם יש שינוי סיסמה תקין
        const passwordChanged = newPassword.length >= 6 && newPassword === confirmPassword;

        // אפשר עדכון אם יש שינוי בסיסי או שינוי סיסמה תקין
        document.getElementById('updateBtn').disabled = !(basicChanged || passwordChanged);
    }

    // אירועי שדות הסיסמה
    document.getElementById('new_password').addEventListener('input', function() {
        const password = this.value;
        const confirmField = document.getElementById('confirm_password');
        
        // הפעל/כבה את שדה האימות
        confirmField.disabled = password.length === 0;
        if (password.length === 0) {
            confirmField.value = '';
            document.getElementById('matchInfo').textContent = '';
            confirmField.className = '';
        }
        
        checkPasswordStrength(password);
        checkPasswordMatch();
        checkChanges();
    });

    document.getElementById('confirm_password').addEventListener('input', function() {
        checkPasswordMatch();
        checkChanges();
    });

    function toggleForm(formId) {
        const form = document.getElementById(formId);
        form.style.display = form.style.display === "none" ? "block" : "none";
    }

    // פורמט מספר כרטיס עם רווחים
    document.addEventListener('DOMContentLoaded', function() {
        const cardNumberField = document.getElementById('cardNumber');
        const expiryField = document.getElementById('expiryDate');
        const cvvField = document.getElementById('cvvField');
        const codeField = document.getElementById('codeField');

        // פורמט מספר כרטיס
        if (cardNumberField) {
            cardNumberField.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
                e.target.value = value;
            });
        }

        // פורמט תאריך תוקף
        if (expiryField) {
            expiryField.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            });
        }

        // רק מספרים בCVV וקוד
        function numbersOnly(field) {
            if (field) {
                field.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/\D/g, '');
                });
            }
        }

        numbersOnly(cvvField);
        numbersOnly(codeField);
    });
</script>

</body>
</html>