<?php
    $con = new mysqli("localhost", "root", "", "fithub"); 
    if (!$con) { 
        die("Could not connect: " . mysqli_error()); 
    } 
    $flag=0;
    if(isset($_POST['bt'])!=null){
        if(isset($_POST['id'])!=null &&isset($_POST['Fname'])!=null&& isset($_POST['Lname'])!=null&& isset($_POST['Email'])!=null && isset($_POST['password'])!=null && isset($_POST['confirm_password'])!=null){
            $id= $_POST['id'];
            $Fname= $_POST['Fname'];
            $Lname= $_POST['Lname'];
            $Email= $_POST['Email'];
            $Phone= $_POST['Phone'];
            $pass=$_POST['password'];
            $confirm_pass=$_POST['confirm_password'];
            
            // בדיקה אם הסיסמאות תואמות
            if($pass !== $confirm_pass) {
                echo "<script>alert('Error: Passwords do not match.');</script>";
            } else {
                $sql = "SELECT * FROM users WHERE userId = '$id'";
                $result = $con->query($sql);
                if ($result->num_rows > 0) {
                        echo "<script>alert('Error: User with the given ID or Email already exists.');</script>";
                        exit();
                }
                $sql = "INSERT INTO users (userId, FirstName, LastName, Email, Phone, Password , Role) VALUES ('$id', '$Fname', '$Lname', '$Email','$Phone', '$pass',0)";
                if ($con->query($sql) === TRUE) {
                    header('Location: login.php');
                    exit();                
                } else {
                    echo $con->error;
                }
            }
        } else {
            echo "Please fill in all fields.";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> FitHub - Sign-up</title>
    <style>
           * {
            box-sizing: border-box;
            font-family:Times New Roman;
        }

        body {
            margin: 0;
            padding: 0;
            background: url('images/gym.jpeg') no-repeat center/cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            direction: rtl;
            color: #fff;
        }

        .content {
            position: relative;
            background: rgba(255, 255, 255, 0.08);
            padding: 40px 30px 30px;
            border-radius: 20px;
            backdrop-filter: blur(14px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 400px;
            text-align: center;
            margin:20px;
        }

        

        .p {
            font-size: 28px;
            margin-bottom: 25px;
            font-weight: bold;
            color: #fff;
            margin-top: 20px;
        }

        input[type='text'],
        input[type='email'],
        input[type='number_format'],
        input[type='password'] {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            background: rgba(25, 63, 92, 0.7);
            color: #fff;
            margin-bottom: 20px;
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.7);
            text-align: left;

        }

        input:focus {
            outline: none;
            background: rgba(25, 63, 92, 0.9);        
        }

        .signup {
            width:100%;
            padding: 12px;
            color: #1a1a1a;
            background: none;
            border: 2px solid #ccc;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease-in-out;
        }

        .signup:hover:enabled {
            background-color: white;
        }

        .signup:disabled {
            background: none;
            border: 2px solid #ccc;
            cursor: not-allowed;
        }

        .login-link {
            margin-top: 20px;
            font-size: 15px;
            color:rgba(44, 42, 42, 0.52) ;
        }

        .login-link a {
            color: #fff;
            text-decoration: underline;
            font-weight: bold;
        }

        .login-link a:hover {
            color:black;
        }

        .password-strength {
            text-align: right;
            font-size: 12px;
            margin-top: -15px;
            margin-bottom: 15px;
        }

        .password-match {
            border: 2px solid #4caf50 !important;
        }

        .password-mismatch {
            border: 2px solid #f44336 !important;
        }

        .strength-weak { color: #f44336; }
        .strength-fair { color: #ff9800; }
        .strength-good { color: #2196f3; }
        .strength-strong { color: #4caf50; }

        .confirm-password-feedback {
            text-align: right;
            font-size: 12px;
            margin-top: -15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="content">
      <form method="post">
        <p class="p">Sign-up</p>
        <input type="number_format" name="id" id="id" max-length="9" required placeholder="Enter your ID"  />
        <input type="text" name="Fname" id="Fname" required placeholder="Enter your FirstName" />
        <input type="text" name="Lname" id="Lname" required placeholder="Enter your LastName"  />
        <input type="email" name="Email" id="Email" required placeholder="Enter your Email" />
        <input type="number_format" name="Phone" id="Phone" required placeholder="Enter your Phone Number"  />
        <input type="password" name="password" id="password" required placeholder="Enter your Password" />
        <div class="password-strength" id="passwordStrength"></div>
        <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm your Password" />
        <div class="confirm-password-feedback" id="confirmFeedback"></div>
        <button type="submit" name="bt" id="signupBtn" disabled class="signup">sign up</button>
        <div class="login-link">
            already have account? 
            <a href="login.php">login</a>
        </div>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const idField = document.getElementById('id');
        const FnameField = document.getElementById('Fname');
        const LnameField = document.getElementById('Lname');
        const emailField = document.getElementById('Email');
        const phoneField = document.getElementById('Phone');
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        const signupBtn = document.getElementById('signupBtn');
        const passwordStrength = document.getElementById('passwordStrength');
        const confirmFeedback = document.getElementById('confirmFeedback');

        // פונקציה לבדיקת חוזק סיסמה
        function checkPasswordStrength(password) {
            if (!password) {
                passwordStrength.textContent = '';
                return 0;
            }

            let strength = 0;
            let feedback = 'חלשה';
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

            passwordStrength.textContent = `Password strength: ${feedback}`;
            passwordStrength.className = `password-strength ${className}`;
            return strength;
        }

        // פונקציה לבדיקת תאימות סיסמאות
        function checkPasswordMatch() {
            const password = passwordField.value;
            const confirmPassword = confirmPasswordField.value;

            if (!confirmPassword) {
                confirmFeedback.textContent = '';
                confirmPasswordField.className = '';
                return false;
            }

            if (password === confirmPassword) {
                confirmFeedback.textContent = 'Passwords match ✓';
                confirmFeedback.style.color = '#4caf50';
                confirmPasswordField.classList.remove('password-mismatch');
                confirmPasswordField.classList.add('password-match');
                return true;
            } else {
                confirmFeedback.textContent = 'Passwords do not match ✗';
                confirmFeedback.style.color = '#f44336';
                confirmPasswordField.classList.remove('password-match');
                confirmPasswordField.classList.add('password-mismatch');
                return false;
            }
        }

        // פונקציה לבדיקת כל השדות
        function checkFields() {
            const passwordStrengthGood = checkPasswordStrength(passwordField.value) >= 2;
            const passwordsMatch = checkPasswordMatch();
            
            if (
                idField.value.trim() !== '' &&
                FnameField.value.trim() !== '' &&
                LnameField.value.trim() !== '' &&
                emailField.value.trim() !== '' &&
                phoneField.value.trim() !== '' &&
                passwordField.value.trim() !== '' &&
                confirmPasswordField.value.trim() !== '' &&
                passwordStrengthGood &&
                passwordsMatch
            ) {
                signupBtn.disabled = false;
            } else {
                signupBtn.disabled = true;
            }
        }

        // הוספת event listeners
        passwordField.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
            checkFields();
        });

        confirmPasswordField.addEventListener('input', function() {
            checkPasswordMatch();
            checkFields();
        });

        [idField, FnameField, LnameField, emailField, phoneField].forEach(field => {
            field.addEventListener('input', checkFields);
        });
    });
</script>
</body>
</html>