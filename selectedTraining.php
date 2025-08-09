<?php
session_start();
$con = new mysqli("localhost", "root", "", "fithub");
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

if (!isset($_POST['trainingNum'])) {
    header("Location: training.php");
    exit();
}

$trainingNum = (int)$_POST['trainingNum'];

if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION['userId'];
$id = $userId['userId'];

// בדיקה שליפת Role של המשתמש
$roleQuery = $con->prepare("SELECT Role FROM users WHERE userId = ?");
$roleQuery->bind_param("s", $id);
$roleQuery->execute();
$roleResult = $roleQuery->get_result();

if ($roleResult->num_rows === 0) {
    echo "<script>alert('User not found.'); window.location.href = 'login.php';</script>";
    exit();
}

$userRole = $roleResult->fetch_assoc()['Role'];

// שליפת פרטי האימון
$sql = "SELECT * FROM training WHERE trainingNum = $trainingNum";
$result = $con->query($sql);
if ($result->num_rows === 0) {
    echo "Training not found.";
    exit();
}
$row = $result->fetch_assoc();
$availableSpots = max(0, $row['maxParticipants'] - $row['Participants']);
$price = $row['Price'];

// שליפת נקודות המשתמש (רק אם הוא לקוח)
$userPoints = 0;
$availableShekel = 0;

if ($userRole == 0) {
    $pointsQuery = $con->prepare("SELECT points FROM users WHERE userId = ?");
    $pointsQuery->bind_param("s", $id);
    $pointsQuery->execute();
    $pointsResult = $pointsQuery->get_result();
    $userPoints = $pointsResult->fetch_assoc()['points'] ?? 0;
    $availableShekel = floor($userPoints / 5);
}

// טיפול בהרשמה עם מערכת תגמולים (רק אם המשתמש הוא לקוח)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['doRegister'])) {
    
    // בדיקה נוספת שהמשתמש הוא לקוח
    if ($userRole != 0) {
        echo "<script>alert('Only clients can register for trainings.'); window.location.href = 'home.php';</script>";
        exit();
    }
    
    $date = date("Y-m-d H:i:s");
    $usePoints = isset($_POST['use_points']) ? (int)$_POST['use_points'] : 0;
    $selectedCard = str_replace(' ', '', $_POST['selected_card'] ?? ''); // הסר רווחים מהכרטיס הנבחר
    $enteredCode = (int)($_POST['card_code'] ?? 0); // המר לmספר שלם
    $enteredCvv = (int)($_POST['card_cvv'] ?? 0); // המר למספר שלם

    // בדיקה אם המשתמש כבר רשום
    $checkQuery = "SELECT * FROM registeration WHERE userId = '$id' AND trainingNum = $trainingNum";
    $checkResult = $con->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        echo "<script>alert('You are already registered for this training.');</script>";
    } elseif ($availableSpots <= 0) {
        echo "<script>alert('No spots available for this training.');</script>";
    } elseif (empty($selectedCard)) {
        echo "<script>alert('Please select a payment method.');</script>";
    } else {
        // בדיקת פרטי הכרטיס
        $cardQuery = $con->prepare("SELECT code, CVV FROM card WHERE CardNum = ? AND userId = ?");
        $cardQuery->bind_param("ss", $selectedCard, $id);
        $cardQuery->execute();
        $cardResult = $cardQuery->get_result();
        
        // Debug - הוסף זמנית לבדיקה
        error_log("Selected Card: " . $selectedCard);
        error_log("User ID: " . $id);
        error_log("Cards found: " . $cardResult->num_rows);
        
        if ($cardResult->num_rows === 0) {
            $errorMessage = "Invalid payment method selected. Card: " . $selectedCard;
        } else {
            $cardData = $cardResult->fetch_assoc();
            
            // Debug - הוסף זמנית לבדיקה
            error_log("Stored code: " . $cardData['code']);
            error_log("Entered code: " . $enteredCode);
            error_log("Stored CVV: " . $cardData['CVV']);
            error_log("Entered CVV: " . $enteredCvv);
            
            if ($cardData['code'] !== $enteredCode) { // חזרה ל-=== כי עכשיו שניהם int
                $errorMessage = "Incorrect card code. Stored: " . $cardData['code'] . ", Entered: " . $enteredCode;
            } elseif ($cardData['CVV'] !== $enteredCvv) { // חזרה ל-=== כי עכשיו שניהם int
                $errorMessage = "Incorrect CVV. Stored: " . $cardData['CVV'] . ", Entered: " . $enteredCvv;
            } else {
                // פרטי הכרטיס נכונים - המשך עם ההרשמה
                // חישוב ההנחה
                $maxPointsToUse = min($usePoints, $userPoints);
                $maxPointsToUse = min($maxPointsToUse, floor($maxPointsToUse / 5) * 5); // עיגול למספר שלם של שקלים
                $maxPointsToUse = min($maxPointsToUse, $price * 5); // לא יותר מסכום ההרשמה
                $discountAmount = floor($maxPointsToUse / 5);
                $finalPrice = max(0, $price - $discountAmount);
                
                try {
                    $con->begin_transaction();
                    
                    // הוספת הרשמה עם פרטי תגמולים
                    $insertQuery = $con->prepare("INSERT INTO registeration (userId, trainingNum, date, price, final_price, points_used, discount_amount, points_earned) VALUES (?, ?, ?, ?, ?, ?, ?, 10)");
                    $insertQuery->bind_param("sisdddi", $id, $trainingNum, $date, $price, $finalPrice, $maxPointsToUse, $discountAmount);
                    $insertQuery->execute();
                    $registrationNum = $con->insert_id;
                    
                    // עדכון מספר משתתפים באימון
                    $updateParticipants = "UPDATE training SET Participants = Participants + 1 WHERE trainingNum = $trainingNum";
                    $con->query($updateParticipants);
                    
                    // אם השתמש בנקודות - הפחת אותן
                    if ($maxPointsToUse > 0) {
                        $updateUserPoints = $con->prepare("UPDATE users SET points = points - ? WHERE userId = ?");
                        $updateUserPoints->bind_param("is", $maxPointsToUse, $id);
                        $updateUserPoints->execute();
                        
                        // הוסף להיסטוריית נקודות - שימוש
                        $historyStmt = $con->prepare("INSERT INTO points_history (userId, points_change, points_type, description, registration_num) VALUES (?, ?, 'used', ?, ?)");
                        $usedPoints = -$maxPointsToUse;
                        $description = "שימוש בנקודות להנחה על אימון {$row['trainingName']} - חיסכון של {$discountAmount}₪";
                        $historyStmt->bind_param("sisi", $id, $usedPoints, $description, $registrationNum);
                        $historyStmt->execute();
                    }
                    
                    // הוסף 10 נקודות עבור ההרשמה
                    $addPoints = $con->prepare("UPDATE users SET points = points + 10 WHERE userId = ?");
                    $addPoints->bind_param("s", $id);
                    $addPoints->execute();
                    
                    // הוסף להיסטוריית נקודות - צבירה
                    $historyStmt = $con->prepare("INSERT INTO points_history (userId, points_change, points_type, description, registration_num) VALUES (?, 10, 'earned', ?, ?)");
                    $description = "צבירת נקודות עבור הרשמה לאימון {$row['trainingName']}";
                    $historyStmt->bind_param("ssi", $id, $description, $registrationNum);
                    $historyStmt->execute();
                    
                    $con->commit();
                    
                    $message = "Successfully registered! ";
                    if ($discountAmount > 0) {
                        $message .= "You saved {$discountAmount}₪ using your points! ";
                    }
                    $message .= "You earned 10 points.";
                    
                    echo "<script>alert('{$message}'); window.location.href = 'training.php';</script>";
                    exit();
                    
                } catch (Exception $e) {
                    $con->rollback();
                    echo "<script>alert('Failed to register: " . $e->getMessage() . "');</script>";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>FitHub - Training Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family:Times New Roman;
            margin: 0;
            padding: 0;
            background-color: #121212;
            background-image: url('images/gym.jpeg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            color: white;
        }

        .btn {
            font-size: 18px;
            color:rgba(0, 0, 0, 1);
            background-color:rgba(255, 255, 255, 0.4);
            border: 1px solid rgb(0, 0, 0);
            padding: 10px 15px;
            border-radius: 12px;
            transition: 0.3s;
            cursor: pointer;
            position: absolute;
            top: 20px;
            right: 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover {
            background-color: #222;
            color:rgb(125, 165, 123);
        }

        .details-container {
            max-width: 900px;
            margin: 80px auto 30px;
            background-color: rgba(255, 255, 255, 0.07);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
            font-size: 18px;
        }

        .training-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(168, 240, 165, 0.3);
        }

        .training-header h2 {
            color: black;
            margin-bottom: 10px;
            font-size: 30px;
        }

        .training-header .trainer-info {
            color: rgb(59, 72, 58);
            font-size: 19px;
        }

        .image {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
        }

        .image img {
            max-width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .training-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            backdrop-filter: blur(10px);
        }
        .training-info h3 {
            font-size:26px;
        }
        .training-description {
            color: #ddd;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 12px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            color:rgb(46, 42, 42);
        }

        .points-section {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            backdrop-filter: blur(10px);
        }

        .points-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .points-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            text-align: center;
        }

        .points-slider {
            width: 100%;
            margin: 15px 0;
            accent-color: #a8f0a5;
            height: 8px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.2);
            outline: none;
        }

        .price-breakdown {
            background: rgba(33, 150, 243, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
            border: 1px solid rgba(168, 240, 165, 0.2);
        }

        .price-line {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 16px;
        }

        .final-price {
            font-size: 20px;
            font-weight: bold;
            color:rgb(255, 255, 255);
            border-top: 2px solid rgba(168, 240, 165, 0.3);
            padding-top: 10px;
            margin-top: 10px;
        }

        .discount-highlight {
            color: #f44336;
            font-weight: bold;
        }

        .btn-register {
            background: linear-gradient(135deg,rgb(161, 190, 161),rgb(139, 161, 140));
            color: #000;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
            max-width: 300px;
            margin: 20px auto;
            display: block;
        }

        .btn-register:hover {
            background: linear-gradient(135deg,rgb(110, 143, 111),rgb(148, 184, 149));
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .btn-register:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
        }

        .registration-form {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            color:white;
            margin-top: 20px;
            backdrop-filter: blur(10px);
        }

        .form-title {
            font-size: 24px;
            font-weight: bold;
            color:rgb(255, 255, 255);
            text-align: center;
            margin-bottom: 20px;
        }

        .availability-warning {
            background: rgba(244, 67, 54, 0.1);
            border: 2px solid rgba(244, 67, 54, 0.3);
            color: #ff6b6b;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            text-align: center;
            font-weight: bold;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.1);
            border: 2px solid rgba(76, 175, 80, 0.3);
            color: #a8f0a5;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            text-align: center;
        }

        .icon {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <a href="training.php" class="btn">
        <i class="fas fa-arrow-left"></i> Back to Trainings
    </a>

    <div class="details-container">
        <?php
        $id_query = $con->query("SELECT TrainerId FROM training WHERE trainingNum = $trainingNum");
        $trainerId = ($id_query && $id_query->num_rows > 0) ? $id_query->fetch_assoc()['TrainerId'] : null;

        $trainerName = "Unknown";
        if ($trainerId) {
            $trainer_query = $con->query("SELECT FirstName, LastName FROM users WHERE userId = $trainerId");
            if ($trainer_query && $trainer_query->num_rows > 0) {
                $trainer_row = $trainer_query->fetch_assoc();
                $trainerName = htmlspecialchars($trainer_row['FirstName'] . " " . $trainer_row['LastName']);
            }
        }
        ?>

        <div class="training-header">
            <h2><?php echo htmlspecialchars($row['trainingName']); ?></h2>
            <p class="trainer-info"><span style="font-weight:bold;">WITH</span> ~ <?php echo $trainerName; ?></p>
        </div>

        <div class="image">
            <img src="<?php echo htmlspecialchars($row['img']); ?>" alt="Training Image">
        </div>

        <div class="training-info">
            <h3>Training Details</h3>
            <div class="training-description">
                <?php echo nl2br(htmlspecialchars($row['Description'])); ?>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="icon">📅</span>
                    <span class="info-value"><?php echo htmlspecialchars($row['Date']); ?></span>
                </div>
                <div class="info-item">
                    <span class="icon">⏰</span>
                    <span class="info-value"><?php echo htmlspecialchars($row['Time']); ?></span>
                </div>
                <div class="info-item">
                    <span class="icon">⏱️</span>
                    <span class="info-value"><?php echo htmlspecialchars($row['Duration']); ?> Hours</span>
                </div>
                <div class="info-item">
                    <span class="icon">📍</span>
                    <span class="info-value"><?php echo htmlspecialchars($row['Location']); ?></span>
                </div>
                <div class="info-item">
                    <span class="icon">🏅</span>
                    <span class="info-value"><?php echo htmlspecialchars($row['Level']); ?></span>
                </div>
                <div class="info-item">
                    <span class="icon">🎯</span>
                    <span class="info-value"><?php echo htmlspecialchars($row['Goal']); ?></span>
                </div>
                <div class="info-item">
                    <span class="icon">💰</span>
                    <span class="info-value">₪<?php echo number_format($row['Price'], 2); ?></span>
                </div>
                <div class="info-item">
                    <span class="icon">👥</span>
                    <span class="info-value"><?php echo $availableSpots; ?> / <?php echo $row['maxParticipants']; ?></span>
                </div>
            </div>
        </div>

        <?php if ($availableSpots <= 2 && $availableSpots > 0): ?>
            <div class="availability-warning">
                ⚠️ Only <?php echo $availableSpots; ?> spots left! Register now!
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="availability-warning">
                ❌ <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <!-- הצגת חלק ההרשמה רק ללקוחות -->
        <?php if ($userRole == 0): ?>
            <!-- לקוחות - הצג טופס הרשמה -->
            <?php if ($availableSpots > 0): ?>
                <div class="registration-form">
                    <div class="form-title">Register for Training</div>
                    
                    <form method="post" id="registrationForm">
                        <input type="hidden" name="doRegister" value="1">
                        <input type="hidden" name="trainingNum" value="<?php echo $trainingNum; ?>">
                        
                        <!-- בחירת אמצעי תשלום -->
                        <div class="points-section">
                            <div class="points-title">
                                <i class="fas fa-credit-card"></i>
                                Select Payment Method
                            </div>
                            
                            <?php
                            // שליפת כרטיסים בתוקף
                            $cardsQuery = $con->prepare("SELECT CardNum, ExpirationDate FROM card WHERE userId = ? AND ExpirationDate > CURDATE()");
                            $cardsQuery->bind_param("s", $id);
                            $cardsQuery->execute();
                            $cardsResult = $cardsQuery->get_result();
                            
                            if ($cardsResult->num_rows > 0):
                            ?>
                                <div style="margin: 15px 0;">
                                    <?php while($card = $cardsResult->fetch_assoc()): 
                                        $maskedCard = '**** **** **** ' . substr($card['CardNum'], -4);
                                        $displayDate = date('m/y', strtotime($card['ExpirationDate']));
                                        $isSelected = (isset($selectedCard) && $selectedCard == $card['CardNum']) ? 'checked' : '';
                                    ?>
                                        <label style="display: block; margin: 10px 0; padding: 15px; background: rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer;">
                                            <input type="radio" name="selected_card" value="<?php echo $card['CardNum']; ?>" required style="margin-right: 10px;" <?php echo $isSelected; ?>>
                                            <strong><?php echo $maskedCard; ?></strong> - Expires: <?php echo $displayDate; ?>
                                        </label>
                                    <?php endwhile; ?>
                                </div>
                                
                                <div style="margin: 20px 0;">
                                    <label for="card_code" style="color: white; font-weight: bold; display: block; margin-bottom: 5px;">Personal Code (4 digits):</label>
                                    <input type="text" name="card_code" id="card_code" maxlength="4" pattern="[0-9]{4}" required 
                                           style="width: 100px; padding: 8px; border-radius: 5px; border: none; text-align: center;" 
                                           placeholder="****" value="<?php echo isset($enteredCode) ? htmlspecialchars($enteredCode) : ''; ?>">
                                </div>
                                
                                <div style="margin: 20px 0;">
                                    <label for="card_cvv" style="color: white; font-weight: bold; display: block; margin-bottom: 5px;">CVV (3 digits):</label>
                                    <input type="text" name="card_cvv" id="card_cvv" maxlength="3" pattern="[0-9]{3}" required 
                                           style="width: 80px; padding: 8px; border-radius: 5px; border: none; text-align: center;" 
                                           placeholder="***" value="<?php echo isset($enteredCvv) ? htmlspecialchars($enteredCvv) : ''; ?>">
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 20px; background: rgba(244, 67, 54, 0.1); border-radius: 10px; margin: 15px 0;">
                                    <p style="color: #ff6b6b; font-weight: bold;">❌ No valid payment methods found</p>
                                    <p style="color: #ddd;">Please add a payment method in your profile before registering for trainings.</p>
                                    <a href="profile.php" style="color: #a8f0a5; text-decoration: none; font-weight: bold;">
                                        <i class="fas fa-plus"></i> Add Payment Method
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($userPoints >= 5): ?>
                        <div class="points-section">
                            <div class="points-title">
                                <i class="fas fa-gift"></i>
                                Use Your Reward Points
                            </div>
                            
                            <div class="points-info">
                                <strong>You have <?php echo number_format($userPoints); ?> points</strong><br>
                                <span style="opacity: 0.8;">Worth up to ₪<?php echo $availableShekel; ?> discount</span>
                            </div>
                            
                            <label for="use_points" style="color:rgb(250, 250, 250); font-weight: bold;">How many points to use:</label>
                            <input type="range" 
                                   class="points-slider"
                                   id="use_points" 
                                   name="use_points" 
                                   min="0" 
                                   max="<?php echo min($userPoints, $price * 5); ?>" 
                                   step="5"
                                   value="0"
                                   oninput="updatePriceCalculation()">
                            
                            <div style="text-align: center; margin: 10px 0; color:rgb(0, 0, 0);">
                                <span id="points-display">0 points selected</span>
                            </div>
                            
                            <div class="price-breakdown" id="price-breakdown" style="display: none;">
                                <div class="price-line">
                                    <span>Original Price:</span>
                                    <span id="original-price">₪<?php echo number_format($price, 2); ?></span>
                                </div>
                                <div class="price-line discount-highlight">
                                    <span>Points Discount:</span>
                                    <span id="discount-display">-₪0.00</span>
                                </div>
                                <div class="price-line final-price">
                                    <span>Final Price:</span>
                                    <span id="final-price-display">₪<?php echo number_format($price, 2); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="success-message">
                            <strong>🎉 Earn 10 points with this registration!</strong><br>
                            Start building your reward points for future discounts.
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($cardsResult->num_rows > 0): ?>
                        <button type="submit" class="btn-register">
                            Complete Registration & Pay
                        </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php else: ?>
                <div class="availability-warning">
                    ❌ Sorry, this training is fully booked!
                </div>
                <button class="btn-register" disabled>Fully Booked</button>
            <?php endif; ?>
            
        <?php elseif ($userRole == 1): ?>
            <!-- מאמנים - הצג הודעה מתאימה -->
            <div class="training-info" style="text-align: center; padding: 30px;">
                <h3 style="color: #a8f0a5;">👨‍🏫 Trainer View</h3>
                <p style="color: #ddd; font-size: 18px;">
                    You are viewing this training as a trainer.<br>
                    Only clients can register for training sessions.
                </p>
                <div style="margin-top: 20px;">
                    <a href="trainer.php" style="color: #a8f0a5; text-decoration: none; font-weight: bold;">
                        <i class="fas fa-cog"></i> Manage Your Trainings
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- אדמינים או תפקידים אחרים -->
            <div class="training-info" style="text-align: center; padding: 30px;">
                <h3 style="color: #a8f0a5;">👨‍💼 Admin View</h3>
                <p style="color: #ddd; font-size: 18px;">
                    You are viewing this training as an administrator.<br>
                    Only clients can register for training sessions.
                </p>
                <div style="margin-top: 20px;">
                    <a href="home.php" style="color: #a8f0a5; text-decoration: none; font-weight: bold;">
                        <i class="fas fa-home"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script>
        function updatePriceCalculation() {
            const pointsSlider = document.getElementById('use_points');
            const pointsDisplay = document.getElementById('points-display');
            const priceBreakdown = document.getElementById('price-breakdown');
            const discountDisplay = document.getElementById('discount-display');
            const finalPriceDisplay = document.getElementById('final-price-display');
            
            // בדיקה שהאלמנטים קיימים (רק אצל לקוחות)
            if (!pointsSlider) return;
            
            const originalPrice = <?php echo $price; ?>;
            const usedPoints = parseInt(pointsSlider.value) || 0;
            const discount = Math.floor(usedPoints / 5);
            const finalPrice = Math.max(0, originalPrice - discount);
            
            // עדכון התצוגה
            pointsDisplay.textContent = `${usedPoints} points selected`;
            discountDisplay.textContent = `-₪${discount.toFixed(2)}`;
            finalPriceDisplay.textContent = `₪${finalPrice.toFixed(2)}`;
            
            // הצגת פירוט המחיר רק כשיש נקודות בשימוש
            if (usedPoints > 0) {
                priceBreakdown.style.display = 'block';
            } else {
                priceBreakdown.style.display = 'none';
            }
        }
        
        // הפעלה ראשונית
        document.addEventListener('DOMContentLoaded', function() {
            updatePriceCalculation();
            
            // הגבלת קלט למספרים בלבד עבור קוד וCVV
            const cardCodeField = document.getElementById('card_code');
            const cardCvvField = document.getElementById('card_cvv');
            
            if (cardCodeField) {
                cardCodeField.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/\D/g, '');
                });
            }
            
            if (cardCvvField) {
                cardCvvField.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/\D/g, '');
                });
            }
            
            // אימות טופס לפני שליחה
            const registrationForm = document.getElementById('registrationForm');
            if (registrationForm) {
                registrationForm.addEventListener('submit', function(e) {
                    const selectedCard = document.querySelector('input[name="selected_card"]:checked');
                    const cardCode = document.getElementById('card_code').value;
                    const cardCvv = document.getElementById('card_cvv').value;
                    
                    if (!selectedCard) {
                        alert('Please select a payment method.');
                        e.preventDefault();
                        return;
                    }
                    
                    if (cardCode.length !== 4) {
                        alert('Please enter a 4-digit card code.');
                        e.preventDefault();
                        return;
                    }
                    
                    if (cardCvv.length !== 3) {
                        alert('Please enter a 3-digit CVV.');
                        e.preventDefault();
                        return;
                    }
                });
            }
        });
    </script>
</body>
</html>