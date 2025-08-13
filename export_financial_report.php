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

// Calculate all financial data
$reportData = [];

// Total revenue from trainings
$revenueQuery = "SELECT SUM(final_price) as total_revenue FROM registeration";
$revenueResult = $con->query($revenueQuery);
$reportData['total_revenue'] = $revenueResult->fetch_assoc()['total_revenue'] ?? 0;

// Total expenses
$expensesQuery = "SELECT SUM(amount) as total_expenses FROM expenses";
$expensesResult = $con->query($expensesQuery);
$reportData['total_expenses'] = $expensesResult->fetch_assoc()['total_expenses'] ?? 0;

// Calculate trainer payments - based on salary from DB
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
        // If salary is defined, use it; otherwise use 40%
        if (isset($training['salary']) && $training['salary'] > 0) {
            $trainerPayments += $training['salary'] * $training['Participants']; // Salary per participant
        } else {
            $trainerPayments += $totalTrainingRevenue * 0.4; // Default
        }
    }
}
$reportData['trainer_payments'] = $trainerPayments;

// Net profit
$reportData['net_profit'] = $reportData['total_revenue'] - $reportData['total_expenses'] - $reportData['trainer_payments'];

// Cancellations and refunds
$cancellationsQuery = "SELECT COUNT(*) as total_cancellations, SUM(refund_amount) as total_refunds FROM cancellations";
$cancellationsResult = $con->query($cancellationsQuery);
$cancellationData = $cancellationsResult ? $cancellationsResult->fetch_assoc() : ['total_cancellations' => 0, 'total_refunds' => 0];

// Trainer performance details
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

// Expenses by category
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

// Generate HTML report content
$reportDate = date('d/m/Y H:i');
$reportMonth = date('F Y');

$htmlContent = "
<!DOCTYPE html>
<html dir='ltr'>
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
        <h1>🏋️ FitHub - Monthly Financial Report</h1>
        <p>Report generated on: $reportDate</p>
        <p>Report period: $reportMonth</p>
    </div>

    <div class='summary'>
        <h2>📊 Financial Summary</h2>
        <div style='text-align: center;'>
            <div class='stat'>
                <h3>💰 Total Revenue</h3>
                <div class='positive'>₪" . number_format($reportData['total_revenue'], 2) . "</div>
            </div>
            <div class='stat'>
                <h3>💸 Trainer Payments</h3>
                <div class='neutral'>₪" . number_format($reportData['trainer_payments'], 2) . "</div>
            </div>
            <div class='stat'>
                <h3>📉 Expenses</h3>
                <div class='negative'>₪" . number_format($reportData['total_expenses'], 2) . "</div>
            </div>
            <div class='stat'>
                <h3>📈 Net Profit</h3>
                <div class='" . ($reportData['net_profit'] >= 0 ? 'positive' : 'negative') . "'>₪" . number_format($reportData['net_profit'], 2) . "</div>
            </div>
        </div>
    </div>

    <h2>👥 Trainer Performance Breakdown</h2>
    <table>
        <tr>
            <th>Trainer Name</th>
            <th>Hourly Salary</th>
            <th>Total Trainings</th>
            <th>Total Participants</th>
            <th>Total Revenue Generated</th>
            <th>Trainer Payment</th>
            <th>Gym Profit</th>
        </tr>";

if ($trainerDetailsResult && $trainerDetailsResult->num_rows > 0) {
    while ($trainer = $trainerDetailsResult->fetch_assoc()) {
        $gymProfit = $trainer['total_revenue'] - $trainer['trainer_payment'];
        $salaryDisplay = isset($trainer['salary']) && $trainer['salary'] > 0 ? 
                        number_format($trainer['salary'], 2) : 
                        "40% of revenue";
        
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
    $htmlContent .= "<tr><td colspan='7' style='text-align: center;'>No trainer data available</td></tr>";
}

$htmlContent .= "
    </table>

    <h2>💳 Expenses by Category</h2>
    <table>
        <tr>
            <th>Category</th>
            <th>Number of Expenses</th>
            <th>Total Amount</th>
            <th>Average per Expense</th>
            <th>Percentage of Total Expenses</th>
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
    $htmlContent .= "<tr><td colspan='5' style='text-align: center;'>No expense data available</td></tr>";
}

$htmlContent .= "
    </table>

    <h2>🔄 Cancellations and Refunds</h2>
    <div class='summary'>
        <div style='text-align: center;'>
            <div class='stat'>
                <h3>Total Cancellations</h3>
                <div class='neutral'>" . $cancellationData['total_cancellations'] . "</div>
            </div>
            <div class='stat'>
                <h3>Total Refunds</h3>
                <div class='negative'>₪" . number_format(floatval($cancellationData['total_refunds']), 2) . "</div>
            </div>
        </div>
    </div>

    <div class='footer'>
        <p>This report was automatically generated by the FitHub system</p>
        <p>For questions or issues, contact the system administrator</p>
        <p><strong>Note:</strong> Trainer payments are calculated according to the salary defined in the system, or 40% of revenue if no salary is set</p>
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
    $mail->Username   = 'taimakizel18@gmail.com';
    $mail->Password   = 'ihiw lpel zlzh ucya';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('noreply@fithub.com', 'FitHub System');
    $mail->addAddress($adminEmail, $adminName);

    $reportMonth = date('F Y');
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'FitHub - Monthly Financial Report - ' . $reportMonth;
    $mail->Body    = $htmlContent;

    $mail->send();

    echo "<script>
        alert('Report successfully sent to email: $adminEmail');
        window.close();
    </script>";
} catch (Exception $e) {
    echo "<h3>⚠️ Error sending the report:</h3><p>{$mail->ErrorInfo}</p>";
    echo $htmlContent;
    echo "<script>
        setTimeout(function() {
            if(confirm('Email sending failed. Would you like to print the report?')) {
                window.print();
            }
        }, 2000);
    </script>";
}

$con->close();
?>
