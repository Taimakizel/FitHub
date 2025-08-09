<?php
$con = new mysqli("localhost", "root", "", "fithub");
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
session_start();
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION['userId'];
$id = $userId['userId'];
$firstName = $_SESSION['FirstName'];

// הוספת כרטיס חדש
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_card'])) {
    $cardNumber = str_replace(' ', '', $_POST['card_number']); // הסר רווחים
    $expirationDate = $_POST['expiration_date'];
    $cvv = $_POST['cvv'];
    $code = $_POST['code'];

    // המר MM/YY ל YYYY-MM-01
    if (preg_match('/^(\d{2})\/(\d{2})$/', $expirationDate, $matches)) {
        $month = $matches[1];
        $year = '20' . $matches[2];
        $expirationDate = $year . '-' . $month . '-01';
    }

    $stmt = $con->prepare("INSERT INTO card (CardNum, ExpirationDate, CVV, code, userId) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $cardNumber, $expirationDate, $cvv, $code, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Card Added Successfully');</script>";
    } else {
        echo "<script>alert('Failed to Add Card');</script>";
    }
}

// מחיקת כרטיס
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_card'])) {
    $cardId = $_POST['card_id'];
    
    $stmt = $con->prepare("DELETE FROM card WHERE CardNum = ? AND userId = ?");
    $stmt->bind_param("ss", $cardId, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Card Deleted Successfully');</script>";
    } else {
        echo "<script>alert('Failed to Delete Card');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Methods</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Times New Roman;
            background: url('images/gym.jpeg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
        }

        .header {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            color: rgb(116, 146, 115);
            margin: 0;
        }

        .btn {
            font-size: 18px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
            border-radius: 8px;
            transition: 0.3s ease;
            color: white;
        }

        .btn:hover {
            border-radius: 8px;
            background-color: rgba(167, 178, 139, 0.86);
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 50px;
            gap: 30px;
            flex-wrap: wrap;
        }

        .payment-box {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(14px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 500px;
            color: black;
            margin-left:20px;
            width: 100%
        }

        .payment-box h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .payment-box input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: none;
            background-color: rgba(235, 233, 233, 0.85);
            color: rgba(0, 0, 0, 0.7);
            font-size: 16px;
            box-sizing: border-box;
        }

        .payment-box input::placeholder {
            color: rgb(102, 99, 99);
        }

        .payment-box input:focus {
            outline: none;
            background: rgba(207, 212, 206, 0.9);
        }

        .btn-card {
            width: 100%;
            padding: 12px;
            background: rgba(167, 178, 139, 0.7);
            border: none;
            color: #1a1a1a;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease-in-out;
        }

        .btn-card:hover {
            background-color: rgb(196, 223, 177);
        }

        .card-list {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(14px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 600px;
            color: black;
        }

        .card-item {
            border: 2px solid rgba(167, 178, 139, 0.5);
            padding: 20px;
            margin: 15px 0;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: 0.3s ease;
        }

        .card-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .card-info {
            flex: 1;
        }

        .card-info p {
            margin: 5px 0;
            font-size: 16px;
        }

        .card-info strong {
            color: #333;
        }

        .btn-delete {
            padding: 10px 15px;
            background: #f44336;
            border: none;
            color: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease;
        }

        .btn-delete:hover {
            background: #d32f2f;
            transform: scale(1.05);
        }

        .no-cards {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 18px;
        }

        .add-card-toggle {
            text-align: center;
            margin-bottom: 20px;
        }

        .toggle-btn {
            padding: 12px 25px;
            background: rgba(167, 178, 139, 0.7);
            border: none;
            color: #1a1a1a;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease;
        }

        .toggle-btn:hover {
            background-color: rgb(196, 223, 177);
        }

        #cardForm {
            display: none;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Payment Methods Management</h1>
    <div class="header-content">
        <button class="btn" onclick="location.href='profile.php'"><i class="fas fa-user"></i></button>
        <button class="btn" onclick="location.href='home.php'"><i class="fas fa-home"></i></button>
    </div>
</div>

<div class="container">
    <!-- רשימת כרטיסים קיימים -->
    <div class="card-list">
        <h2><i class="fas fa-credit-card"></i> Your Payment Methods</h2>
        
        <!-- כפתור להוספת כרטיס חדש -->
        <div class="add-card-toggle">
            <button class="toggle-btn" onclick="toggleAddCard()">
                <i class="fas fa-plus"></i> Add New Card
            </button>
        </div>

        <!-- טופס הוספת כרטיס (מוסתר כברירת מחדל) -->
        <form method="POST" id="cardForm">
            <div class="payment-box">
                <h2>Add New Payment Method</h2>
                <input type="hidden" name="add_card" value="1">
                <input name="card_number" id="cardNumber" placeholder="Card Number (16 digits)" type="text" maxlength="19" required>
                <input name="expiration_date" id="expiryDate" placeholder="MM/YY" type="text" maxlength="5" required>
                <input name="cvv" id="cvvField" placeholder="CVV (3 digits)" type="text" maxlength="3" required>
                <input name="code" id="codeField" placeholder="Personal Code (4 digits)" type="text" maxlength="4" required>
                <button class="btn-card" type="submit">Add Card</button>
            </div>
        </form>

        <!-- רשימת כרטיסים -->
        <div class="cards-container">
            <?php
            $cardsQuery = $con->prepare("SELECT CardNum, ExpirationDate, CVV, code FROM card WHERE userId = ?");
            $cardsQuery->bind_param("s", $id);
            $cardsQuery->execute();
            $cardsResult = $cardsQuery->get_result();
            
            if ($cardsResult->num_rows > 0):
                while($card = $cardsResult->fetch_assoc()):
                    $maskedCard = '**** **** **** ' . substr($card['CardNum'], -4);
                    $displayDate = date('m/y', strtotime($card['ExpirationDate']));
            ?>
                <div class="card-item">
                    <div class="card-info">
                        <p><strong><i class="fas fa-credit-card"></i> Card:</strong> <?php echo $maskedCard; ?></p>
                        <p><strong><i class="fas fa-calendar"></i> Expires:</strong> <?php echo $displayDate; ?></p>
                    </div>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="delete_card" value="1">
                        <input type="hidden" name="card_id" value="<?php echo $card['CardNum']; ?>">
                        <button class="btn-delete" type="submit" onclick="return confirm('Are you sure you want to delete this card?')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="no-cards">
                    <i class="fas fa-credit-card" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <p>No payment methods found.</p>
                    <p>Add your first card to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function toggleAddCard() {
        const form = document.getElementById('cardForm');
        const toggleBtn = document.querySelector('.toggle-btn');
        
        if (form.style.display === "none" || form.style.display === "") {
            form.style.display = "block";
            toggleBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
        } else {
            form.style.display = "none";
            toggleBtn.innerHTML = '<i class="fas fa-plus"></i> Add New Card';
        }
    }

    // פורמט מספר כרטיס עם רווחים
    document.addEventListener('DOMContentLoaded', function() {
        const cardNumberField = document.getElementById('cardNumber');
        const expiryField = document.getElementById('expiryDate');
        const cvvField = document.getElementById('cvvField');
        const codeField = document.getElementById('codeField');

        // פורמט מספר כרטיס
        cardNumberField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            e.target.value = value;
        });

        // פורמט תאריך תוקף
        expiryField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // רק מספרים בCVV וקוד
        function numbersOnly(field) {
            field.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });
        }

        numbersOnly(cvvField);
        numbersOnly(codeField);
    });
</script>

</body>
</html>