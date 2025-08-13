<?php
session_start();

$con = new mysqli("localhost", "root", "", "fithub");
if (!$con) {
    die("Could not connect: " . mysqli_error());
}

// Check that the user arrived here correctly
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['code_verified'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['reset_user_id'];

// Fetch user details for display
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

// Handle password reset
if (isset($_POST['reset_password'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Update password and reset attempts
        $updateQuery = "UPDATE users SET Password = ?, login_attempts = 0, verification_code = NULL, code_expiry = NULL WHERE userId = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param("ss", $newPassword, $userId);
        
        if ($updateStmt->execute()) {
            // Clear session variables
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['code_verified']);
            unset($_SESSION['blocked_user_id']);
            unset($_SESSION['show_verification']);
            
            $success = true;
        } else {
            $error = "Error updating password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitHub - Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Your CSS stays exactly the same */
    </style>
</head>
<body>
    <div class="reset-box">
        <?php if (isset($success) && $success): ?>
            <!-- Success message -->
            <div class="success-animation">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="title">🎉 Password Reset Successful!</div>
                <div class="success-message">
                    Your password has been successfully updated!<br>
                    You can now log in with your new password.
                </div>
                <div class="countdown" id="countdown">Redirecting to login in <span id="timer">5</span> seconds...</div>
                <br>
                <a href="login.php" class="back-btn">
                    <i class="fas fa-sign-in-alt"></i> Login Now
                </a>
            </div>
        <?php else: ?>
            <!-- Password reset form -->
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
                <strong>Password Requirements:</strong>
                <ul>
                    <li>At least 6 characters</li>
                    <li>Mix of letters and numbers recommended</li>
                    <li>Avoid using personal information</li>
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
        // Countdown and redirect to login
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
        // Password strength checker
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

        // Real-time password match check
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
