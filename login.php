<?php
session_start();

// כלול PHPMailer בתחילת הקובץ
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$con = new mysqli("localhost", "root", "", "fithub");
if (!$con) {
    die("Could not connect: " . mysqli_error());
}

// פונקציה לשליחת קוד אימות
function sendVerificationCode($email, $firstName, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'taimakizel18@gmail.com';
        $mail->Password   = 'ljrj dprw dtgm bqxf';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@fithub.com', 'FitHub Security');
        $mail->addAddress($email, $firstName);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'FitHub - code to password ';

        $htmlContent = "
        <!DOCTYPE html>
        <html dir='rtl'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; }
                .container { max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                .header { background: #4e684f; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; margin: -30px -30px 30px -30px; }
                .code-box { background: #f8f9fa; border: 2px solid #4e684f; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .code { font-size: 32px; font-weight: bold; color: #4e684f; letter-spacing: 5px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
         <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔒 FitHub - Verification Code</h1>
                </div>
                
                <p>Hello $firstName,</p>
                
                <p>A password reset request has been made for your FitHub account.</p>
                
                <div class='code-box'>
                    <p><strong>Your verification code:</strong></p>
                    <div class='code'>$code</div>
                </div>
                
                <div class='warning'>
                    <p><strong>⚠️ Important Instructions:</strong></p>
                    <ul>
                        <li>Enter the code on the login page</li>
                        <li>The code is valid for 15 minutes only</li>
                        <li>After entering the correct code, you will be redirected to reset your password</li>
                        <li>If the code is wrong, a new one will be sent automatically</li>
                    </ul>
                </div>
                
                <div class='footer'>
                    <p>This email was sent automatically by the FitHub system</p>
                    <p>For questions, contact the support team</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->Body = $htmlContent;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// פונקציה ליצירת קוד אימות
function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// פונקציה לשליחת קוד חדש ועדכון במסד נתונים
function sendNewVerificationCode($userId, $con) {
    $userQuery = "SELECT FirstName, Email FROM users WHERE userId = ?";
    $stmt = $con->prepare($userQuery);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $newCode = generateVerificationCode();
        $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $updateQuery = "UPDATE users SET verification_code = ?, code_expiry = ? WHERE userId = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param("sss", $newCode, $expiry, $userId);
        $updateStmt->execute();

        return sendVerificationCode($user['Email'], $user['FirstName'], $newCode);
    }
    return false;
}

// טיפול בהתחברות רגילה
if(isset($_POST['bt']) && $_POST['Id'] != null && $_POST['password'] != null){
    $id = $_POST['Id'];
    $pass = $_POST['password'];
    
    // בדיקת חסימה
    if(isset($_SESSION['blocked_until']) && time() < $_SESSION['blocked_until']) {
        $remaining = ceil(($_SESSION['blocked_until'] - time()) / 60);
        echo "<script>alert('החשבון חסום ל-$remaining דקות נוספות.');</script>";
    } else if(isset($_SESSION['blocked_until'])) {
        unset($_SESSION['blocked_until']);
        unset($_SESSION['failed_attempts']);
    }
    
    // אם החשבון לא חסום
    if(!isset($_SESSION['blocked_until']) || time() >= $_SESSION['blocked_until']) {
        $sql = "SELECT userId, FirstName, LastName, Email, Phone, Password, Role, image_path FROM users WHERE userId = '$id' AND Password = '$pass'";
        $result = $con->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $_SESSION['userId'] = $row;
            $_SESSION['Role'] = $row['Role'];
            $_SESSION['FirstName'] = $row['FirstName'];
            $_SESSION['LastName'] = $row['LastName'];
            $_SESSION['Email'] = $row['Email'];
            $_SESSION['Phone'] = $row['Phone'];
            $_SESSION['image_path'] = $row['image_path'];
            
            // איפוס ניסיונות כושלים
            unset($_SESSION['failed_attempts']);
            unset($_SESSION['blocked_until']);
            
            if ($row['Role'] == 2) {
                header('Location: admin.php');
                exit();
            } else if($row['Role'] == 1){
                header('Location: trainer.php');
                exit();
            } else {
                header('Location: home.php');
                exit();
            }
        } else {
            // ניסיון כושל
            if(!isset($_SESSION['failed_attempts'])) {
                $_SESSION['failed_attempts'] = 0;
            }
            $_SESSION['failed_attempts']++;
            
            if($_SESSION['failed_attempts'] >= 3) {
                // Block for 30 minutes + send verification code
                $_SESSION['blocked_until'] = time() + (30 * 60);
                
                $sql = "SELECT Email FROM users WHERE userId = '$id'";
                $result = $con->query($sql);
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if (sendNewVerificationCode($id, $con)) {
                        $_SESSION['blocked_user_id'] = $id;
                        $_SESSION['show_verification'] = true;
                        echo "<script>alert('The account has been blocked for 30 minutes. A reset code has been sent to your email.');</script>";
                    } else {
                        echo "<script>alert('The account has been blocked for 30 minutes. Error sending reset code.');</script>";
                    }
                } else {
                    echo "<script>alert('ID not found.');</script>";
                }
            } else {
                $remaining = 3 - $_SESSION['failed_attempts'];
                echo "<script>alert('Incorrect! $remaining attempts remaining.');</script>";
            }

        }
    }
}

// טיפול בכפתור "שכחתי סיסמה"
if (isset($_POST['forgot_password']) && !empty($_POST['Id'])) {
    $userId = $_POST['Id'];
    $userQuery = "SELECT userId FROM users WHERE userId = ?";
    $stmt = $con->prepare($userQuery);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        if (sendNewVerificationCode($userId, $con)) {
            $_SESSION['blocked_user_id'] = $userId;
            $_SESSION['show_verification'] = true;
            echo "<script>alert('A verification code has been sent to your email.');</script>";
        } else {
            echo "<script>alert('Error sending the verification code. Please try again later.');</script>";
        }
    } else {
        echo "<script>alert('User does not exist in the system');</script>";
    }

}

// טיפול באימות קוד
if (isset($_POST['verify_code'])) {
    $userId = $_POST['user_id'];
    $enteredCode = $_POST['verification_code'];

    $query = "SELECT verification_code, code_expiry FROM users WHERE userId = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $storedCode = $row['verification_code'];
        $expiry = $row['code_expiry'];

        if ($enteredCode == $storedCode && strtotime($expiry) > time()) {
            $_SESSION['reset_user_id'] = $userId;
            $_SESSION['code_verified'] = true;
            header("Location: reset_password.php");
            exit();
        } else {
            if (sendNewVerificationCode($userId, $con)) {
                echo "<script>alert('Incorrect or expired code. A new code has been sent to your email');</script>";
            } else {
                echo "<script>alert('Error sending a new code, please try again later');</script>";
            }

        }
    }
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitHub - Login</title>
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

        .login-box {
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
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: -40px;
            left: calc(50% - 40px);
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            backdrop-filter: blur(8px);
            background-image: url('https://cdn-icons-png.flaticon.com/512/149/149071.png');
            background-size: 50%;
            background-repeat: no-repeat;
            background-position: center;
            border: 2px solid rgba(255,255,255,0.4);
        }

        .login-box .p {
            font-size: 28px;
            margin-bottom: 25px;
            font-weight: bold;
            color: #fff;
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

        .login {
            width: 100%;
            padding: 12px;
            background: none;
            border: 2px solid #ccc;
            color: #1a1a1a;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease-in-out;
            margin-bottom: 10px;
        }

        .login:hover:enabled {
            background-color: white;
        }

        .login:disabled {
            background: none;
            border: 2px solid #ccc;
            cursor: not-allowed;
        }

        .ques {
            margin-top: 20px;
            font-size: 15px;
            color: #ccc;
        }

        .signup {
            margin-top: 10px;
            background: none;
            border: 2px solid #fff;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease;
        }

        .signup:hover {
            background-color: #fff;
            color: #1a1a1a;
        }

        .verification-section {
            display: none;
        }

        .reset-password-section {
            display: none;
        }

        /* כיתות עזר לתצוגה */
        .show {
            display: block !important;
        }

        .hide {
            display: none !important;
        }

        .warning-text {
            color: #ff6b6b;
            font-size: 14px;
            margin: 10px 0;
            text-align: center;
        }

        .success-text {
            color: #51cf66;
            font-size: 14px;
            margin: 10px 0;
            text-align: center;
        }

        .back-btn {
            background: rgba(167, 178, 139, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }

        .back-btn:hover {
            background:rgb(229, 241, 220);
        }

        .code-input {
            text-align: center;
            font-size: 20px;
            letter-spacing: 3px;
            font-weight: bold;
        }

        .info-text {
            color: #0c3231ff;
            font-size: 13px;
            margin: 10px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <!-- דף התחברות רגיל -->
        <div id="login-section" <?php echo isset($_SESSION['show_verification']) ? 'class="hide"' : ''; ?>>
            <form method='post'>
                <p class="p">Login</p>
                <div class="input-box">
                    <i class="fa fa-user"></i>
                    <input type='text' name='Id' id='Id' required placeholder='Enter your ID' />
                </div>
                <div class="input-box">
                    <i class="fa fa-lock"></i>
                    <input type='password' name='password' id="password" required placeholder='Enter your Password' />
                </div>
                <button class="login" type='submit' name='bt' id="loginBtn" disabled>Login</button>
                <button class="signup" type='button' onclick="window.location.href='register.php'">Sign up</button>
                <button class="signup" type='submit' name='forgot_password'>Forgot Password?</button>
            </form>
        </div>

        <!-- דף אימות קוד -->
        <div id="verification-section" class="verification-section <?php echo isset($_SESSION['show_verification']) ? 'show' : ''; ?>">
            <form method='post'>
                <p class="p">🔐 Account Blocked</p>
                <div class="warning-text">
                    <i class="fas fa-exclamation-triangle"></i><br>
                    Your account has been blocked after 3 failed attempts.<br>
                    A verification code has been sent to your email.
                </div>
                
                <div class="input-box">
                    <i class="fa fa-key"></i>
                    <input type='text' name='verification_code' id='verificationCode' class="code-input" required placeholder='000000' maxlength='6' />
                </div>
                
                <div class="info-text">
                    Enter the 6-digit code sent to your email.<br>
                    If the code is wrong, a new one will be sent automatically.
                </div>
                
                <input type='hidden' name='user_id' value='<?php echo $_SESSION['blocked_user_id'] ?? ''; ?>' />
                <button class="login" type='submit' name='verify_code' id="verifyBtn" disabled>Verify Code</button>
                <button class="back-btn" type='button' onclick="goBackToLogin()">Back to Login</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // עבור דף ההתחברות הרגיל
            const idField = document.getElementById('Id');
            const passwordField = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            
            // עבור דף האימות
            const verificationCodeField = document.getElementById('verificationCode');
            const verifyBtn = document.getElementById('verifyBtn');

            // פונקציה לבדיקת שדות ההתחברות
            function checkLoginFields() {
                if (idField && passwordField && loginBtn) {
                    if (idField.value.trim() !== '' && passwordField.value.trim() !== '') {
                        loginBtn.disabled = false;
                    } else {
                        loginBtn.disabled = true;
                    }
                }
            }

            // פונקציה לבדיקת שדה קוד האימות
            function checkVerificationField() {
                if (verificationCodeField && verifyBtn) {
                    if (verificationCodeField.value.trim().length === 6) {
                        verifyBtn.disabled = false;
                    } else {
                        verifyBtn.disabled = true;
                    }
                }
            }

            // אירועים לשדות ההתחברות
            if (idField) {
                idField.addEventListener('input', checkLoginFields);
            }
            if (passwordField) {
                passwordField.addEventListener('input', checkLoginFields);
            }

            // אירועים לשדה קוד האימות
            if (verificationCodeField) {
                verificationCodeField.addEventListener('input', function() {
                    // מגביל רק למספרים
                    this.value = this.value.replace(/[^0-9]/g, '');
                    checkVerificationField();
                });
            }

            // בדיקה ראשונית
            checkLoginFields();
            checkVerificationField();
        });

        // פונקציה לחזרה לדף ההתחברות
        function goBackToLogin() {
            document.getElementById('verification-section').classList.remove('show');
            document.getElementById('verification-section').classList.add('hide');
            document.getElementById('login-section').classList.remove('hide');
            document.getElementById('login-section').classList.add('show');
            
            <?php 
            if (isset($_SESSION['show_verification'])) {
                unset($_SESSION['show_verification']);
                unset($_SESSION['blocked_user_id']);
            }
            ?>
        }
    </script>
</body>
</html>