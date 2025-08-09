<?php
session_start();
$con = new mysqli("localhost", "root", "", "fithub");
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

if (!isset($_SESSION['FirstName']) || !isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['userId']['userId'];
$adminName = $_SESSION['FirstName'] . ' ' . $_SESSION['LastName'];
$adminEmail = $_SESSION['Email'];

$roleQuery = $con->prepare("SELECT Role FROM users WHERE userId = ?");
$roleQuery->bind_param("s", $userId);
$roleQuery->execute();
$roleResult = $roleQuery->get_result();
$userRole = $roleResult->fetch_assoc()['Role'] ?? null;

if ($userRole != 2) {
    echo "<script>alert('Access denied. Admin only.'); window.close();</script>";
    exit();
}

// חישוב כל הנתונים הכלכליים
$reportData = [];

// סה"כ הכנסות מאימונים
$revenueQuery = "SELECT SUM(final_price) as total_revenue FROM registeration";
$revenueResult = $con->query($revenueQuery);
$reportData['total_revenue'] = $revenueResult->fetch_assoc()['total_revenue'] ?? 0;

// סה"כ הוצאות
$expensesQuery = "SELECT SUM(amount) as total_expenses FROM expenses";
$expensesResult = $con->query($expensesQuery);
$reportData['total_expenses'] = $expensesResult->fetch_assoc()['total_expenses'] ?? 0;

// חישוב תשלומים למאמנים - לפי שכר מהמסד נתונים
$trainerPayments = 0;
$trainingsQuery = "
    SELECT t.trainingNum, t.Price, t.Participants, t.TrainerId, u.salary 
    FROM training t 
    LEFT JOIN users u ON t.TrainerId = u.userId 
    WHERE t.Participants > 0 AND u.Role = 1
";
$trainingsResult = $con->query($trainingsQuery);

if ($trainingsResult && $trainingsResult->num_rows > 0) {
    while ($training = $trainingsResult->fetch_assoc()) {
        $totalTrainingRevenue = $training['Price'] * $training['Participants'];
        // אם יש שכר מוגדר למאמן, נשתמש בו, אחרת 40%
        if (isset($training['salary']) && $training['salary'] > 0) {
            $trainerPayments += $training['salary'] * $training['Participants']; // שכר לפי משתתף
        } else {
            $trainerPayments += $totalTrainingRevenue * 0.4; // ברירת מחדל
        }
    }
}
$reportData['trainer_payments'] = $trainerPayments;

// רווח נקי
$reportData['net_profit'] = $reportData['total_revenue'] - $reportData['total_expenses'] - $reportData['trainer_payments'];

// ביטולים והחזרים
$cancellationsQuery = "SELECT COUNT(*) as total_cancellations, SUM(refund_amount) as total_refunds FROM cancellations";
$cancellationsResult = $con->query($cancellationsQuery);
$cancellationData = $cancellationsResult ? $cancellationsResult->fetch_assoc() : ['total_cancellations' => 0, 'total_refunds' => 0];

// נתונים מפורטים של מאמנים
$trainerDetailsQuery = "
    SELECT 
        u.FirstName, 
        u.LastName,
        u.salary,
        COUNT(t.trainingNum) as total_trainings,
        SUM(t.Participants) as total_participants,
        SUM(t.Price * t.Participants) as total_revenue,
        CASE 
            WHEN u.salary > 0 THEN SUM(u.salary * t.Participants)
            ELSE SUM(t.Price * t.Participants * 0.4)
        END as trainer_payment
    FROM users u
    LEFT JOIN training t ON u.userId = t.TrainerId
    WHERE u.Role = 1 AND t.Participants > 0
    GROUP BY u.userId, u.FirstName, u.LastName, u.salary
    ORDER BY trainer_payment DESC
";
$trainerDetailsResult = $con->query($trainerDetailsQuery);

// הוצאות לפי קטגוריה
$expensesCategoryQuery = "
    SELECT 
        expense_type,
        COUNT(*) as count,
        SUM(amount) as total_amount 
    FROM expenses 
    GROUP BY expense_type 
    ORDER BY total_amount DESC
";
$expensesCategoryResult = $con->query($expensesCategoryQuery);

// יצירת תוכן ה-HTML של הדוח
$reportDate = date('d/m/Y H:i');
$reportMonth = date('F Y');

$htmlContent = "
<!DOCTYPE html>
<html dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background: #4e684fff; color: white; padding: 20px; text-align: center; }
        .summary { background: #f4f4f4; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .stat { display: inline-block; margin: 10px; padding: 15px; background: white; border-radius: 5px; min-width: 200px; text-align: center; }
        .positive { color: #4e684fff; font-weight: bold; }
        .negative { color: #a82f2fff; font-weight: bold; }
        .neutral { color: #826f52ff; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: right; }
        th { background: #4e684fff; color: white; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🏋️ FitHub - דוח כלכלי חודשי</h1>
        <p>תאריך הפקת הדוח: $reportDate</p>
        <p>תקופת הדוח: $reportMonth</p>
    </div>

    <div class='summary'>
        <h2>📊 סיכום כלכלי</h2>
        <div style='text-align: center;'>
            <div class='stat'>
                <h3>💰 סה\"כ הכנסות</h3>
                <div class='positive'>₪" . number_format($reportData['total_revenue'], 2) . "</div>
            </div>
            <div class='stat'>
                <h3>💸 תשלומים למאמנים</h3>
                <div class='neutral'>₪" . number_format($reportData['trainer_payments'], 2) . "</div>
            </div>
            <div class='stat'>
                <h3>📉 הוצאות</h3>
                <div class='negative'>₪" . number_format($reportData['total_expenses'], 2) . "</div>
            </div>
            <div class='stat'>
                <h3>📈 רווח נקי</h3>
                <div class='" . ($reportData['net_profit'] >= 0 ? 'positive' : 'negative') . "'>₪" . number_format($reportData['net_profit'], 2) . "</div>
            </div>
        </div>
    </div>

    <h2>👥 פילוח ביצועים של מאמנים</h2>
    <table>
        <tr>
            <th>שם המאמן</th>
            <th>שכר לשעה</th>
            <th>מספר אימונים</th>
            <th>סה\"כ משתתפים</th>
            <th>הכנסות שיצר</th>
            <th>תשלום למאמן</th>
            <th>רווח לחדר כושר</th>
        </tr>";

if ($trainerDetailsResult && $trainerDetailsResult->num_rows > 0) {
    while ($trainer = $trainerDetailsResult->fetch_assoc()) {
        $gymProfit = $trainer['total_revenue'] - $trainer['trainer_payment'];
        $salaryDisplay = isset($trainer['salary']) && $trainer['salary'] > 0 ? 
                        number_format($trainer['salary'], 2) : 
                        "40% מההכנסות";
        
        $htmlContent .= "
        <tr>
            <td>" . htmlspecialchars($trainer['FirstName'] . " " . $trainer['LastName']) . "</td>
            <td>₪" . $salaryDisplay . "</td>
            <td>" . $trainer['total_trainings'] . "</td>
            <td>" . $trainer['total_participants'] . "</td>
            <td>₪" . number_format($trainer['total_revenue'], 2) . "</td>
            <td class='neutral'>₪" . number_format($trainer['trainer_payment'], 2) . "</td>
            <td class='positive'>₪" . number_format($gymProfit, 2) . "</td>
        </tr>";
    }
} else {
    $htmlContent .= "<tr><td colspan='7' style='text-align: center;'>אין נתוני מאמנים זמינים</td></tr>";
}

$htmlContent .= "
    </table>

    <h2>💳 פילוח הוצאות לפי קטגוריה</h2>
    <table>
        <tr>
            <th>קטגוריה</th>
            <th>מספר הוצאות</th>
            <th>סכום כולל</th>
            <th>ממוצע להוצאה</th>
            <th>אחוז מסה\"כ הוצאות</th>
        </tr>";

if ($expensesCategoryResult && $expensesCategoryResult->num_rows > 0) {
    while ($category = $expensesCategoryResult->fetch_assoc()) {
        $percentage = $reportData['total_expenses'] > 0 ? ($category['total_amount'] / $reportData['total_expenses']) * 100 : 0;
        $average = $category['count'] > 0 ? $category['total_amount'] / $category['count'] : 0;
        
        $htmlContent .= "
        <tr>
            <td>" . ucfirst(htmlspecialchars($category['expense_type'])) . "</td>
            <td>" . $category['count'] . "</td>
            <td class='negative'>₪" . number_format($category['total_amount'], 2) . "</td>
            <td>₪" . number_format($average, 2) . "</td>
            <td>" . number_format($percentage, 1) . "%</td>
        </tr>";
    }
} else {
    $htmlContent .= "<tr><td colspan='5' style='text-align: center;'>אין נתוני הוצאות זמינים</td></tr>";
}

$htmlContent .= "
    </table>

    <h2>🔄 ביטולים והחזרים</h2>
    <div class='summary'>
        <div style='text-align: center;'>
            <div class='stat'>
                <h3>מספר ביטולים</h3>
                <div class='neutral'>" . $cancellationData['total_cancellations'] . "</div>
            </div>
            <div class='stat'>
                <h3>סה\"כ החזרים</h3>
                <div class='negative'>₪" . number_format(floatval($cancellationData['total_refunds']), 2) . "</div>
            </div>
        </div>
    </div>

    <div class='footer'>
        <p>דוח זה הופק אוטומטית על ידי מערכת FitHub</p>
        <p>לשאלות או בעיות, פנה למנהל המערכת</p>
        <p><strong>הערה:</strong> התשלומים למאמנים מחושבים לפי השכר המוגדר במערכת, או 40% מההכנסות במקרה שלא הוגדר שכר</p>
    </div>
</body>
</html>";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'taimakizel18@gmail.com'; // <-- שימי כאן את המייל שלך
    $mail->Password   = 'ihiw lpel zlzh ucya';   // <-- שימי כאן את סיסמת האפליקציה
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('noreply@fithub.com', 'FitHub System');
    $mail->addAddress($adminEmail, $adminName);

    $reportMonth = date('F Y');
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'FitHub - דוח כלכלי חודשי - ' . $reportMonth;
    $mail->Body    = $htmlContent;

    $mail->send();

    echo "<script>
        alert('הדוח נשלח בהצלחה למייל: $adminEmail');
        window.close();
    </script>";
} catch (Exception $e) {
    echo "<h3>⚠️ שגיאה בשליחת הדוח:</h3><p>{$mail->ErrorInfo}</p>";
    echo $htmlContent;
    echo "<script>
        setTimeout(function() {
            if(confirm('שליחת המייל נכשלה. תרצה להדפיס את הדוח?')) {
                window.print();
            }
        }, 2000);
    </script>";
}

$con->close();
?>