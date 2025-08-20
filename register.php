<?php
    $con = new mysqli("localhost", "root", "", "fithub"); 
    if (!$con) { 
        die("Could not connect: " . mysqli_error()); 
    } 
    $flag=0;
    if(isset($_POST['bt'])!=null){
        if(isset($_POST['id'])!=null &&isset($_POST['Fname'])!=null&& isset($_POST['Lname'])!=null&& isset($_POST['Email'])!=null && isset($_POST['password'])!=null){
            $id= $_POST['id'];
            $Fname= $_POST['Fname'];
            $Lname= $_POST['Lname'];
            $Email= $_POST['Email'];
            $Phone= $_POST['Phone'];
            $pass=$_POST['password'];
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
            background: rgba(25, 63, 92, 0.9);        }

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
        const signupBtn = document.getElementById('signupBtn');

        function checkFields() {
            if (
                idField.value.trim() !== '' &&
                FnameField.value.trim() !== '' &&
                LnameField.value.trim() !== '' &&
                emailField.value.trim() !== '' &&
                phoneField.value.trim() !== '' &&
                passwordField.value.trim() !== ''
            ) {
                signupBtn.disabled = false;
            } else {
                signupBtn.disabled = true;
            }
        }

        [idField, FnameField, LnameField, emailField, phoneField, passwordField].forEach(field => {
            field.addEventListener('input', checkFields);
        });
    });
</script>
</body>
</html>
