<?php
/**
 * הגדרות מייל למערכת FitHub
 * עדכן את ההגדרות בהתאם לספק המייל שלך
 */

// הגדרות כלליות
define('EMAIL_FROM_ADDRESS', 'noreply@fithub.com');
define('EMAIL_FROM_NAME', 'FitHub System');
define('EMAIL_REPLY_TO', 'admin@fithub.com');

// בחר את שיטת השליחה: 'phpmailer', 'sendgrid', או 'mail'
define('EMAIL_METHOD', 'phpmailer');

// הגדרות PHPMailer
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // החלף עם המייל שלך
define('SMTP_PASSWORD', 'your-app-password');    // החלף עם App Password
define('SMTP_ENCRYPTION', 'tls'); // 'tls' או 'ssl'

// הגדרות SendGrid (אם משתמש)
define('SENDGRID_API_KEY', 'your-sendgrid-api-key');

// הגדרות מתקדמות
define('EMAIL_DEBUG', false); // true להצגת שגיאות מפורטות
define('EMAIL_TIMEOUT', 30);  // זמן קצוב בשניות

/**
 * פונקציה מתקדמת לשליחת מייל
 */
function sendEmailReport($to, $subject, $htmlContent, $textContent = '') {
    switch (EMAIL_METHOD) {
        case 'phpmailer':
            return sendWithPHPMailer($to, $subject, $htmlContent, $textContent);
        case 'sendgrid':
            return sendWithSendGrid($to, $subject, $htmlContent, $textContent);
        case 'mail':
        default:
            return sendWithBuiltInMail($to, $subject, $htmlContent);
    }
}

/**
 * שליחה עם PHPMailer
 */
function sendWithPHPMailer($to, $subject, $htmlContent, $textContent = '') {
    require_once 'vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // הגדרות שרת
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION === 'tls' ? 
                           PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : 
                           PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = EMAIL_TIMEOUT;
        
        // הגדרות תוכן
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
        
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlContent;
        
        if (!empty($textContent)) {
            $mail->AltBody = $textContent;
        }
        
        // הפעלת דיבוג אם נדרש
        if (EMAIL_DEBUG) {
            $mail->SMTPDebug = 2;
        }
        
        $mail->send();
        return ['success' => true, 'message' => 'מייל נשלח בהצלחה'];
        
    } catch (Exception $e) {
        $error = EMAIL_DEBUG ? $mail->ErrorInfo : 'שגיאה בשליחת המייל';
        return ['success' => false, 'message' => $error];
    }
}

/**
 * שליחה עם SendGrid
 */
function sendWithSendGrid($to, $subject, $htmlContent, $textContent = '') {
    require_once 'vendor/autoload.php';
    
    try {
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $email->setSubject($subject);
        $email->addTo($to);
        $email->addContent("text/html", $htmlContent);
        
        if (!empty($textContent)) {
            $email->addContent("text/plain", $textContent);
        }

        $sendgrid = new \SendGrid(SENDGRID_API_KEY);
        $response = $sendgrid->send($email);
        
        if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
            return ['success' => true, 'message' => 'מייל נשלח בהצלחה'];
        } else {
            $error = EMAIL_DEBUG ? $response->body() : 'שגיאה בשליחת המייל';
            return ['success' => false, 'message' => $error];
        }
        
    } catch (Exception $e) {
        $error = EMAIL_DEBUG ? $e->getMessage() : 'שגיאה בשליחת המייל';
        return ['success' => false, 'message' => $error];
    }
}

/**
 * שליחה עם mail() המובנה
 */
function sendWithBuiltInMail($to, $subject, $htmlContent) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM_ADDRESS . '>' . "\r\n";
    $headers .= 'Reply-To: ' . EMAIL_REPLY_TO . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
    
    if (mail($to, $subject, $htmlContent, $headers)) {
        return ['success' => true, 'message' => 'מייל נשלח בהצלחה'];
    } else {
        return ['success' => false, 'message' => 'שגיאה בשליחת המייל'];
    }
}

/**
 * יצירת תוכן טקסט מתוך HTML
 */
function htmlToText($html) {
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

/**
 * וידוא תקינות כתובת מייל
 */
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'כתובת מייל לא תקינה'];
    }
    
    // בדיקות נוספות
    $domain = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($domain, "MX")) {
        return ['valid' => false, 'message' => 'דומיין המייל לא קיים'];
    }
    
    return ['valid' => true, 'message' => 'כתובת מייל תקינה'];
}

/**
 * רישום לוג של שליחות מייל
 */
function logEmailActivity($to, $subject, $success, $error = '') {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $to,
        'subject' => $subject,
        'success' => $success,
        'error' => $error,
        'method' => EMAIL_METHOD
    ];
    
    $logFile = 'logs/email_log.txt';
    
    // יצירת תיקיית logs אם לא קיימת
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
}
?>