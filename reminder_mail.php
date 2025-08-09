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
// שליפת כל המתאמנים (Role = 0)
$result = $con->query("SELECT Email, FirstName FROM users WHERE Role = 0");

while ($row = $result->fetch_assoc()) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // לדוגמה Gmail
        $mail->SMTPAuth   = true;
        $mail->Username   = 'taimakizel18@gmail.com'; // כתובת השולח
        $mail->Password   = 'ihiw lpel zlzh ucya';        // סיסמת אפליקציה (אם Gmail)
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('taimakizel18@gmail.com', 'FitHub');
        $mail->addAddress($row['Email'], $row['FirstName']);

        $mail->isHTML(true);
        $mail->Subject = 'עדכון משקל חודשי - FitHub';
        $mail->Body    = "
            <h3>שלום {$row['FirstName']},</h3>
            <p>הגיע הזמן לעדכן את המשקל החודשי שלך במערכת FitHub!</p>
            <p>היכנס לפרופיל האישי שלך ובצע עדכון משקל כדי שנוכל לעקוב אחרי ההתקדמות שלך.</p>
            <br>
            <p style='color:gray;'>ההודעה נשלחה אוטומטית בתאריך " . date('d/m/Y') . "</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Failed to send to {$row['Email']}: " . $mail->ErrorInfo);
    }
}
?>
