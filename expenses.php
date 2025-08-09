
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


// טיפול בהוספת הוצאה
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['addExpense'])) {
    $expenseType = $_POST['expense_type'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $expenseDate = $_POST['expense_date'];
    $vendor = $_POST['vendor'];
    $category = $_POST['category'];
    $receipt = '';
    
    // טיפול בהעלאת קבלה
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "receipts/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = basename($_FILES["receipt"]["name"]);
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        
        if (in_array(strtolower($fileExtension), $allowedTypes)) {
            $newFileName = "receipt_" . uniqid() . "." . $fileExtension;
            $targetFile = $targetDir . $newFileName;

            if (move_uploaded_file($_FILES["receipt"]["tmp_name"], $targetFile)) {
                $receipt = $targetFile;
            }
        }
    }

    // הוספת הוצאה למסד הנתונים
    $stmt = $con->prepare("INSERT INTO expenses (expense_type, description, amount, expense_date, vendor, category, receipt_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssdssss", $expenseType, $description, $amount, $expenseDate, $vendor, $category, $receipt);

    if ($stmt->execute()) {
        echo "<script>alert('Expense added successfully!'); window.location.href = 'expenses.php';</script>";
    } else {
        echo "<script>alert('Error adding expense: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// שליפת הוצאות קיימות
$expensesQuery = "SELECT * FROM expenses ORDER BY expense_date DESC, created_at DESC";
$expensesResult = $con->query($expensesQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitHub - Expense Management</title>
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
            color:rgb(255, 255, 255);
            margin: 0;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            background:  linear-gradient(135deg,rgb(127, 160, 126),rgb(130, 146, 131));
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .nav-btn:hover {
            background: linear-gradient(135deg,rgb(141, 155, 141),rgb(180, 180, 180));
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .form-title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group option{
            color:black;
        }
        label {
            font-weight: bold;
            color: #fff;
            margin-bottom: 8px;
        }

        input, select, textarea {
            padding: 12px;
            border-radius: 8px;
            border: none;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            font-size: 16px;
        }

        input::placeholder, textarea::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .submit-btn {
            background: linear-gradient(135deg,rgb(127, 160, 126),rgb(130, 146, 131));
            color: #000;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            max-width: 300px;
            margin: 20px auto;
            display: block;
            transition: 0.3s;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg,rgb(141, 155, 141),rgb(180, 180, 180));
            transform: translateY(-2px);
        }

        .expenses-table {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
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
            color:rgb(38, 52, 37);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .receipt-link {
            color:rgb(0, 0, 0);
            text-decoration: none;
            font-weight: bold;
        }

        .receipt-link:hover {
            color: #7bc97d;
        }

        .total-summary {
            background: rgba(168, 240, 165, 0.1);
            border: 2px solid rgba(168, 240, 165, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .total-amount {
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-receipt"></i> Expense Management</h1>
        <div class="nav-buttons">
            <a href="financial_statistics.php" class="nav-btn">
                <i class="fas fa-chart-line"></i>
            </a>
            <a href="home.php" class="nav-btn">
                <i class="fas fa-home"></i>
            </a>
        </div>
    </div>

    <div class="container">
        <!-- טופס הוספת הוצאה -->
        <div class="form-container">
            <div class="form-title">Add New Expense</div>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="expense_type">Expense Type:</label>
                        <select name="expense_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="equipment">Equipment</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="supplies">Supplies</option>
                            <option value="utilities">Utilities</option>
                            <option value="marketing">Marketing</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="category">Category:</label>
                        <input type="text" name="category" placeholder="e.g., Gym Equipment, Cleaning, etc." required>
                    </div>

                    <div class="form-group">
                        <label for="vendor">Vendor/Supplier:</label>
                        <input type="text" name="vendor" placeholder="Company/Store name" required>
                    </div>

                    <div class="form-group">
                        <label for="amount">Amount (₪):</label>
                        <input type="number" step="0.01" name="amount" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label for="expense_date">Expense Date:</label>
                        <input type="date" name="expense_date" required>
                    </div>

                    <div class="form-group">
                        <label for="receipt">Receipt (Image/PDF):</label>
                        <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.gif,.pdf">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" rows="4" placeholder="Detailed description of the expense..." required></textarea>
                </div>

                <button type="submit" name="addExpense" class="submit-btn">
                    <i class="fas fa-plus"></i> Add Expense
                </button>
            </form>
        </div>

        <!-- טבלת הוצאות -->
        <div class="expenses-table">
            <h2 style="text-align: center; margin-bottom: 20px;">
                <i class="fas fa-list"></i> Recent Expenses
            </h2>

            <?php
            // חישוב סה"כ הוצאות
            $totalQuery = "SELECT SUM(amount) as total_expenses FROM expenses";
            $totalResult = $con->query($totalQuery);
            $totalExpenses = $totalResult->fetch_assoc()['total_expenses'] ?? 0;
            ?>

            <div class="total-summary">
                <div class="total-amount">
                    Total Expenses: ₪<?php echo number_format($totalExpenses, 2); ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Vendor</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($expensesResult && $expensesResult->num_rows > 0) {
                        while ($expense = $expensesResult->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($expense['expense_date']) . "</td>";
                            echo "<td>" . ucfirst(htmlspecialchars($expense['expense_type'])) . "</td>";
                            echo "<td>" . htmlspecialchars($expense['category']) . "</td>";
                            echo "<td>" . htmlspecialchars($expense['vendor']) . "</td>";
                            echo "<td>" . htmlspecialchars(substr($expense['description'], 0, 50)) . "...</td>";
                            echo "<td>₪" . number_format($expense['amount'], 2) . "</td>";
                            echo "<td>";
                            if ($expense['receipt_path']) {
                                echo "<a href='" . htmlspecialchars($expense['receipt_path']) . "' target='_blank' class='receipt-link'>";
                                echo "<i class='fas fa-file'></i> View";
                                echo "</a>";
                            } else {
                                echo "<span style='color: #999;'>No receipt</span>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align: center; padding: 30px;'>No expenses recorded yet</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // קביעת תאריך מקסימלי (היום)
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="expense_date"]').max = today;
        });
    </script>
</body>
</html>

