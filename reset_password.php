<?php
session_start();

$con = new mysqli("localhost", "root", "", "fithub");
if (!$con) {
    die("Could not connect: " . mysqli_error());
}

// בדיקה שהמשתמש הגיע לכאן בדרך הנכונה
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['code_verified'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['reset_user_id'];

// שליפת פרטי המשתמש לתצוגה
$userQuery = "SELECT FirstName, Email FROM users WHERE userId = ?";
$stmt = $con->prepare($userQuery);
$stmt->bind_param("s", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();

// טיפול באיפוס סיסמה
if (isset($_POST['reset_password'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword !== $confirmPassword) {
        $error = "הסיסמאות אינן תואמות";
    } elseif (strlen($newPassword) < 6) {
        $error = "הסיסמה חייבת להכיל לפחות 6 תווים";
    } else {
        // עדכון סיסמה ואיפוס נסיונות
        $updateQuery = "UPDATE users SET Password = ?, login_attempts = 0, verification_code = NULL, code_expiry = NULL WHERE userId = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param("ss", $newPassword, $userId);
        
        if ($updateStmt->execute()) {
            // ניקוי משתני Session
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['code_verified']);
            unset($_SESSION['blocked_user_id']);
            unset($_SESSION['show_verification']);
            
            $success = true;
        } else {
            $error = "שגיאה בעדכון הסיסמה, נסה שוב";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitHub - Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            background: url('images/gym.jpeg') no-repeat center/cover;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Times New Roman;
        }

        .reset-box {
            position: relative;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 450px;
            color: #fff;
            text-align: center;
            margin-top:50px;
        }

        .reset-box::before {
            content: '';
            position: absolute;
            top: -40px;
            left: calc(50% - 40px);
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            backdrop-filter: blur(8px);
            background-image: url('https://cdn-icons-png.flaticon.com/512/3064/3064197.png');
            background-size: 50%;
            background-repeat: no-repeat;
            background-position: center;
            border: 2px solid rgba(255,255,255,0.4);
        }

        .reset-box .title {
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: bold;
            color: #fff;
        }

        .user-info {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .input-box {
            position: relative;
            margin-bottom: 20px;
        }

        .input-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            font-size: 18px;
        }

        .input-box input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            background: rgba(25, 63, 92, 0.7);
            color: #fff;
        }

        input::placeholder {
            color: rgba(223, 222, 222, 0.7);
            text-align: left;
        }

        input:focus {
            outline: none;
            background: rgba(25, 63, 92, 0.9);
        }

        .reset-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #4caf50, #45a049);
            border: none;
            color: white;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease-in-out;
            margin-bottom: 15px;
        }

        .reset-btn:hover:enabled {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-2px);
        }

        .reset-btn:disabled {
            background: #666;
            cursor: not-allowed;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .error-message {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.5);
            color: #ffcdd2;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
            color: #c8e6c9;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .password-requirements {
            background: rgba(33, 150, 243, 0.2);
            border: 1px solid rgba(33, 150, 243, 0.5);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: right;
        }

        .password-requirements ul {
            margin: 10px 0;
            padding-right: 20px;
        }

        .password-requirements li {
            margin: 5px 0;
        }

        .strength-meter {
            height: 5px;
            background: #ddd;
            border-radius: 3px;
            margin: 10px 0;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 3px;
        }

        .strength-weak { background: #f44336; width: 25%; }
        .strength-fair { background: #ff9800; width: 50%; }
        .strength-good { background: #2196f3; width: 75%; }
        .strength-strong { background: #4caf50; width: 100%; }

        .success-animation {
            text-align: center;
            padding: 40px 20px;
        }

        .success-icon {
            font-size: 64px;
            color: #4caf50;
            margin-bottom: 20px;
            animation: bounce 1s ease-in-out;
        }

        @keyframes bounce {
            0%, 20%, 60%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            80% { transform: translateY(-10px); }
        }

        .countdown {
            font-size: 18px;
            color: #4caf50;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="reset-box">
        <?php if (isset($success) && $success): ?>
            <!-- הודעת הצלחה -->
            <div class="success-animation">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="title">🎉 Password Reset Successful!</div>
                <div class="success-message">
                    Your password has been successfully updated!<br>
                    You can now login with your new password.
                </div>
                <div class="countdown" id="countdown">Redirecting to login in <span id="timer">5</span> seconds...</div>
                <br>
                <a href="login.php" class="back-btn">
                    <i class="fas fa-sign-in-alt"></i> Login Now
                </a>
            </div>
        <?php else: ?>
            <!-- טופס איפוס סיסמה -->
            <div class="title">🔑 Reset Password</div>
            
            <div class="user-info">
                <i class="fas fa-user"></i> Welcome <?php echo htmlspecialchars($user['FirstName']); ?>!<br>
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['Email']); ?>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="password-requirements">
                <strong>דרישות סיסמה:</strong>
                <ul>
                    <li>לפחות 6 תווים</li>
                    <li>מומלץ שילוב של אותיות ומספרים</li>
                    <li>הימנע משימוש בפרטים אישיים</li>
                </ul>
            </div>

            <form method='post' id="resetForm">
                <div class="input-box">
                    <i class="fa fa-lock"></i>
                    <input type='password' name='new_password' id="newPassword" required placeholder='New Password (min 6 chars)' minlength='6' />
                </div>

                <div class="strength-meter">
                    <div class="strength-fill" id="strengthFill"></div>
                </div>
                <small id="strengthText" style="color: #ccc;">Password strength: Not entered</small>

                <div class="input-box">
                    <i class="fa fa-lock"></i>
                    <input type='password' name='confirm_password' id="confirmPassword" required placeholder='Confirm New Password' />
                </div>

                <button class="reset-btn" type='submit' name='reset_password' id="resetBtn" disabled>
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </form>

            <a href="login.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        <?php endif; ?>
    </div>

    <script>
        <?php if (isset($success) && $success): ?>
        // ספירה לאחור וחזרה לדף התחברות
        let timeLeft = 5;
        const timerElement = document.getElementById('timer');
        
        const countdown = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                window.location.href = 'login.php';
            }
        }, 1000);
        <?php else: ?>
        // בדיקת חוזק סיסמה
        const newPasswordField = document.getElementById('newPassword');
        const confirmPasswordField = document.getElementById('confirmPassword');
        const resetBtn = document.getElementById('resetBtn');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');

        function checkPasswordStrength(password) {
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

            strengthFill.className = `strength-fill ${className}`;
            strengthText.textContent = `Password strength: ${feedback}`;
        }

        function validateForm() {
            const newPassword = newPasswordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            if (newPassword.length >= 6 && newPassword === confirmPassword) {
                resetBtn.disabled = false;
            } else {
                resetBtn.disabled = true;
            }
        }

        newPasswordField.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            validateForm();
        });

        confirmPasswordField.addEventListener('input', validateForm);

        // בדיקת התאמה בזמן אמת
        confirmPasswordField.addEventListener('input', function() {
            if (this.value && newPasswordField.value !== this.value) {
                this.style.borderColor = '#f44336';
            } else {
                this.style.borderColor = '';
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>