<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$con = new mysqli("localhost", "root", "", "fithub");
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Fetch all trainees (Role = 0)
$result = $con->query("SELECT Email, FirstName FROM users WHERE Role = 0");

while ($row = $result->fetch_assoc()) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Example: Gmail
        $mail->SMTPAuth   = true;
        $mail->Username   = 'taimakizel18@gmail.com'; // Sender email
        $mail->Password   = 'ihiw lpel zlzh ucya';    // App password (for Gmail)
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('taimakizel18@gmail.com', 'FitHub');
        $mail->addAddress($row['Email'], $row['FirstName']);

        $mail->isHTML(true);
        $mail->Subject = 'Monthly Weight Update - FitHub';
        $mail->Body    = "
            <h3>Hello {$row['FirstName']},</h3>
            <p>It's time to update your monthly weight in the FitHub system!</p>
            <p>Log in to your personal profile and update your weight so we can track your progress.</p>
            <br>
            <p style='color:gray;'>This message was sent automatically on " . date('d/m/Y') . ".</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Failed to send to {$row['Email']}: " . $mail->ErrorInfo);
    }
}
?>
