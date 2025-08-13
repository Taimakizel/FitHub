<?php
session_start();
$con = new mysqli("localhost", "root", "", "fithub");
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// בדיקה שהמשתמש מחובר ושהוא אדמין
if (!isset($_SESSION['FirstName']) || !isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['userId']['userId'];
$userName = $_SESSION['FirstName'];

// בדיקת הרשאות אדמין
$roleQuery = $con->prepare("SELECT Role FROM users WHERE userId = ?");
$roleQuery->bind_param("s", $userId);
$roleQuery->execute();
$roleResult = $roleQuery->get_result();
$userRole = $roleResult->fetch_assoc()['Role'] ?? null;

if ($userRole != 2) {
    echo "<script>alert('Access denied. Admin only.'); window.location.href = 'home.php';</script>";
    exit();
}

// חישובים כלכליים
function calculateTrainerPayment($trainingNum, $con) {
    // שליפת פרטי האימון והמאמן
    $trainingQuery = $con->prepare("
        SELECT t.Price, t.Participants, t.TrainerId, u.salary 
        FROM training t 
        LEFT JOIN users u ON t.TrainerId = u.userId 
        WHERE t.trainingNum = ?
    ");
    $trainingQuery->bind_param("i", $trainingNum);
    $trainingQuery->execute();
    $training = $trainingQuery->get_result()->fetch_assoc();
    
    if (!$training) return 0;
    
    $totalRevenue = $training['Price'] * $training['Participants'];
    $trainerPayment = $training['salary'] ?? ($totalRevenue * 0.4); // אם אין שכר מוגדר, נשתמש ב-40%
    
    return $trainerPayment;
}

// סטטיסטיקות כלליות
$stats = [];

// סה"כ הכנסות מאימונים
$revenueQuery = "SELECT SUM(final_price) as total_revenue FROM registeration";
$revenueResult = $con->query($revenueQuery);
$stats['total_revenue'] = $revenueResult->fetch_assoc()['total_revenue'] ?? 0;

// סה"כ הוצאות
$expensesQuery = "SELECT SUM(amount) as total_expenses FROM expenses";
$expensesResult = $con->query($expensesQuery);
$stats['total_expenses'] = $expensesResult->fetch_assoc()['total_expenses'] ?? 0;

// חישוב תשלומים למאמנים - עם שכר מהמסד נתונים
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

$stats['trainer_payments'] = $trainerPayments;

// רווח נקי
$stats['net_profit'] = $stats['total_revenue'] - $stats['total_expenses'] - $stats['trainer_payments'];

// סטטיסטיקות מפורטות לפי מאמן
$trainerStatsQuery = "
    SELECT 
        u.FirstName, 
        u.LastName,
        u.userId,
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
$trainerStatsResult = $con->query($trainerStatsQuery);

// ביטולים (אם יש טבלה של ביטולים)
$cancellationsQuery = "SELECT COUNT(*) as total_cancellations, SUM(refund_amount) as total_refunds FROM cancellations";
$cancellationsResult = $con->query($cancellationsQuery);
$cancellationStats = $cancellationsResult ? $cancellationsResult->fetch_assoc() : ['total_cancellations' => 0, 'total_refunds' => 0];

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitHub - Financial Statistics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #fff;
            margin: 0;
            padding: 0;
            background: url('images/gym.jpeg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
        }

        .header {
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
        }

        .header h1 {
            font-size: 28px;
            color:rgb(116, 146, 115);
            margin: 0;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            font-size: 18px;
            background: none;
            border: none;
            cursor: pointer;
            transition: 0.3s ease;
            color:white;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: rgba(167, 178, 139, 0.7);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 2px solid transparent;
            transition: 0.3s;
        }

        .stat-card.revenue {
            border-color: rgba(76, 175, 80, 0.5);
        }

        .stat-card.expenses {
            border-color: rgba(244, 67, 54, 0.5);
        }

        .stat-card.profit {
            border-color: rgba(255, 193, 7, 0.5);
        }

        .stat-card.trainer-payments {
            border-color: rgba(33, 150, 243, 0.5);
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-value.positive {
            color:rgb(10, 65, 12);
        }

        .stat-value.negative {
            color: #f44336;
        }

        .stat-value.neutral {
            color:rgb(0, 0, 0);
        }

        .stat-label {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.8);
        }

        .detailed-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            backdrop-filter: blur(10px);
        }

        .section-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #fff;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        th {
            background: rgba(168, 240, 165, 0.2);
            font-weight: bold;
            color:rgb(25, 46, 24);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0);
        }

        .profit-summary {
            background: linear-gradient(135deg, rgba(168, 240, 165, 0.2), rgba(123, 201, 125, 0.2));
            border: 2px solid rgba(168, 240, 165, 0.5);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }

        .profit-title {
            font-size: 28px;
            font-weight: bold;
            color:rgb(0, 0, 0);
            margin-bottom: 15px;
        }

        .profit-amount {
            font-size: 48px;
            font-weight: bold;
            margin: 20px 0;
        }

        .breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .breakdown-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        .stat_img{
            font-size: 48px; color:rgb(55, 72, 54); margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-chart-line"></i> Financial Statistics</h1>
        <div class="nav-buttons">
            <a href="home.php" class="btn">
                <i class="fas fa-home"></i>
            </a>
        </div>
    </div>

    <div class="container">
        <!-- סטטיסטיקות כלליות -->
        <div class="stats-grid">
            <div class="stat-card revenue">
                <div class="stat-value positive">₪<?php echo number_format($stats['total_revenue'], 2); ?></div>
                <div class="stat-label">
                    <i class="fas fa-arrow-up"></i> Total Revenue
                </div>
            </div>

            <div class="stat-card expenses">
                <div class="stat-value negative">₪<?php echo number_format($stats['total_expenses'], 2); ?></div>
                <div class="stat-label">
                    <i class="fas fa-arrow-down"></i> Total Expenses
                </div>
            </div>

            <div class="stat-card trainer-payments">
                <div class="stat-value neutral">₪<?php echo number_format($stats['trainer_payments'], 2); ?></div>
                <div class="stat-label">
                    <i class="fas fa-user-tie"></i> Trainer Payments (per salary)
                </div>
            </div>

            <div class="stat-card profit">
                <div class="stat-value <?php echo $stats['net_profit'] >= 0 ? 'positive' : 'negative'; ?>">
                    ₪<?php echo number_format($stats['net_profit'], 2); ?>
                </div>
                <div class="stat-label">
                    <i class="fas fa-chart-line"></i> Net Profit
                </div>
            </div>
        </div>

        <!-- סיכום רווח -->
        <div class="profit-summary">
            <div class="profit-title">Monthly Financial Summary</div>
            <div class="profit-amount <?php echo $stats['net_profit'] >= 0 ? 'positive' : 'negative'; ?>">
                ₪<?php echo number_format($stats['net_profit'], 2); ?>
            </div>
            
            <div class="breakdown">
                <div class="breakdown-item">
                    <strong>Revenue</strong><br>
                    <span style="color:rgb(37, 67, 38);">+₪<?php echo number_format($stats['total_revenue'], 2); ?></span>
                </div>
                <div class="breakdown-item">
                    <strong>Trainer Payments</strong><br>
                    <span style="color: #ff9800;">-₪<?php echo number_format($stats['trainer_payments'], 2); ?></span>
                </div>
                <div class="breakdown-item">
                    <strong>Expenses</strong><br>
                    <span style="color: #f44336;">-₪<?php echo number_format($stats['total_expenses'], 2); ?></span>
                </div>
                <div class="breakdown-item">
                    <strong>Net Profit</strong><br>
                    <span class="<?php echo $stats['net_profit'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $stats['net_profit'] >= 0 ? '+' : ''; ?>₪<?php echo number_format($stats['net_profit'], 2); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- סטטיסטיקות מאמנים -->
        <div class="detailed-section">
            <div class="section-title">
                <i class="fas fa-users"></i> Trainer Performance & Payments
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Trainer Name</th>
                        <th>Salary per Hour</th>
                        <th>Total Trainings</th>
                        <th>Total Participants</th>
                        <th>Revenue Generated</th>
                        <th>Trainer Payment</th>
                        <th>Gym Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($trainerStatsResult && $trainerStatsResult->num_rows > 0) { 
                        while ($trainer = $trainerStatsResult->fetch_assoc()) {
                            $gymProfit = $trainer['total_revenue'] - $trainer['trainer_payment'];
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($trainer['FirstName'] . " " . $trainer['LastName']) . "</td>";
                            echo "<td>₪" . number_format($trainer['salary'] ?? 0, 2) . "</td>";
                            echo "<td>" . $trainer['total_trainings'] . "</td>";
                            echo "<td>" . $trainer['total_participants'] . "</td>";
                            echo "<td>₪" . number_format($trainer['total_revenue'], 2) . "</td>";
                            echo "<td style='color: #ff9800;'>₪" . number_format($trainer['trainer_payment'], 2) . "</td>";
                            echo "<td style='color: #4caf50;'>₪" . number_format($gymProfit, 2) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align: center; padding: 30px;'>No trainer data available</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- הוצאות לפי קטגוריה -->
        <div class="detailed-section">
            <div class="section-title">
                <i class="fas fa-chart-pie"></i> Expenses by Category
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Number of Expenses</th>
                        <th>Total Amount</th>
                        <th>Average per Expense</th>
                        <th>% of Total Expenses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($expensesCategoryResult && $expensesCategoryResult->num_rows > 0) {
                        while ($category = $expensesCategoryResult->fetch_assoc()) {
                            $percentage = $stats['total_expenses'] > 0 ? ($category['total_amount'] / $stats['total_expenses']) * 100 : 0;
                            $average = $category['count'] > 0 ? $category['total_amount'] / $category['count'] : 0;
                            
                            echo "<tr>";
                            echo "<td>" . ucfirst(htmlspecialchars($category['expense_type'])) . "</td>";
                            echo "<td>" . $category['count'] . "</td>";
                            echo "<td>₪" . number_format($category['total_amount'], 2) . "</td>";
                            echo "<td>₪" . number_format($average, 2) . "</td>";
                            echo "<td>" . number_format($percentage, 1) . "%</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align: center; padding: 30px;'>No expense data available</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- ביטולים והחזרים -->
        <div class="detailed-section">
            <div class="section-title">
                <i class="fas fa-undo"></i> Cancellations & Refunds
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value neutral"><?php echo $cancellationStats['total_cancellations']; ?></div>
                    <div class="stat-label">Total Cancellations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value negative">₪<?php echo number_format(floatval($cancellationStats['total_refunds']), 2); ?></div>
                    <div class="stat-label">Total Refunds</div>
                </div>
            </div>

            <!-- טבלת ביטולים אחרונים -->
            <?php
            $recentCancellationsQuery = "
                SELECT 
                    c.*,
                    u.FirstName,
                    u.LastName,
                    t.trainingName
                FROM cancellations c
                LEFT JOIN users u ON c.userId = u.userId
                LEFT JOIN training t ON c.trainingNum = t.trainingNum
                ORDER BY c.cancellation_date DESC
                LIMIT 10
            ";
            $recentCancellationsResult = $con->query($recentCancellationsQuery);
            ?>

            <?php if ($recentCancellationsResult && $recentCancellationsResult->num_rows > 0): ?>
            <h3 style="color: #a8f0a5; margin: 20px 0;">Recent Cancellations</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Training</th>
                        <th>Refund Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($cancellation = $recentCancellationsResult->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $cancellation['cancellation_date'] . "</td>";
                        echo "<td>" . $cancellation['FirstName'] . " " . $cancellation['LastName'] . "</td>";
                        echo "<td>" . $cancellation['trainingName'] . "</td>";
                        echo "<td style='color: #f44336;'>₪" . $cancellation['refund_amount'], 2 . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- תחזית חודשית -->
        <div class="detailed-section">
            <div class="section-title">
                <i class="fas fa-calendar-alt"></i> Monthly Financial Trends
            </div>
            
            <?php
            // סטטיסטיקות חודשיות - 6 חודשים אחרונים
            $monthlyQuery = "
                SELECT 
                    DATE_FORMAT(r.date, '%Y-%m') as month,
                    SUM(r.final_price) as monthly_revenue,
                    COUNT(r.registerationNum) as registration
                FROM registeration r
                WHERE r.date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(r.date, '%Y-%m')
                ORDER BY month DESC
                LIMIT 6
            ";
            $monthlyResult = $con->query($monthlyQuery);
            ?>

            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Revenue</th>
                        <th>Registrations</th>
                        <th>Avg per Registration</th>
                        <th>Estimated Trainer Payments</th>
                        <th>Estimated Gym Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($monthlyResult && $monthlyResult->num_rows > 0) {
                        while ($month = $monthlyResult->fetch_assoc()) {
                            $avgPerReg = $month['registration'] > 0 ? $month['monthly_revenue'] / $month['registration'] : 0;
                            
                            // חישוב משוער לפי שכר ממוצע של המאמנים
                            $avgSalaryQuery = "SELECT AVG(salary) as avg_salary FROM users WHERE Role = 1 AND salary > 0";
                            $avgSalaryResult = $con->query($avgSalaryQuery);
                            $avgSalary = $avgSalaryResult->fetch_assoc()['avg_salary'] ?? ($month['monthly_revenue'] * 0.4);
                            
                            $estimatedTrainerPayment = $avgSalary * $month['registration']; // משוער לפי ממוצע
                            $estimatedGymProfit = $month['monthly_revenue'] - $estimatedTrainerPayment;
                            
                            echo "<tr>";
                            echo "<td>" . date('F Y', strtotime($month['month'] . '-01')) . "</td>";
                            echo "<td>₪" . number_format($month['monthly_revenue'], 2) . "</td>";
                            echo "<td>" . $month['registration'] . "</td>";
                            echo "<td>₪" . number_format($avgPerReg, 2) . "</td>";
                            echo "<td style='color: #ff9800;'>₪" . number_format($estimatedTrainerPayment, 2) . "</td>";
                            echo "<td style='color:rgb(55, 92, 56);'>₪" . number_format($estimatedGymProfit, 2) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align: center; padding: 30px;'>No monthly data available</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- אזור פעולות מהירות -->
        <div class="detailed-section">
            <div class="section-title">
                <i class="fas fa-tools"></i> Quick Actions
            </div>
            
            <div class="stats-grid">
                <div class="stat-card" style="cursor: pointer;" onclick="exportFinancialReport()">
                    <div class="stat_img">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <div class="stat-label">Export Financial Report</div>
                </div>
                
                <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='expenses.php'">
                    <div class="stat_img">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="stat-label">Add New Expense</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function exportFinancialReport() {
            if (confirm('Export financial report for this period?')) {
                // יצירת קובץ CSV או PDF עם הנתונים הכלכליים
                window.open('export_financial_report.php', '_blank');
            }
        }

        // עדכון אוטומטי של הנתונים כל 5 דקות
        setInterval(function() {
            location.reload();
        }, 300000); // 5 דקות
    </script>
</body>
</html>