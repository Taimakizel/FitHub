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
$role = $_SESSION['Role'];
/// אלגוריתם סינון שיתופי 
// 
$userTrainings = []; //לאחסון כל האימונים למתאמן המחובר
$res = $con->query("SELECT trainingNum FROM registeration WHERE userId = $id"); // מחזירה מס' האימונים שהמתאמן הנוכחי נרשם אליהם
while ($row = $res->fetch_assoc()) {
    $userTrainings[] = $row['trainingNum'];
} // עוברים בלולאה על כל שורה שהחזירה השאילתה ומכניסים את הערך של מזהה האימון לתוך המערך 
$userTrainingsList = empty($userTrainings) ? '0' : implode(',', $userTrainings);
// בדיקה אם המערך ריק , מציבים בתוך המחרוזת 0 אחרת מחברים מס אימון למחרוזת מופרדת בפסיקים
$similarUsers = [];
$res = $con->query("
    SELECT DISTINCT userId
    FROM registeration
    WHERE trainingNum IN ($userTrainingsList) AND userId != $id
"); // מחפשת משתמשים שנרשמו לאותם אימונים
// DISTINCT מבטיחהמ שהערכים יהיו ללא כפוליות 
while ($row = $res->fetch_assoc()) {
    $similarUsers[] = $row['userId'];
}
// הלולאה יוצרת מערך שמכיל כל המשתמשים שדומים למשתמש המחובר 
$recommendedTrainings = [];
if (!empty($similarUsers) && $role == 0) { //בדיקה אם נמצאו משתמשים דומים
    $similarUsersList = implode(',', $similarUsers); // ממרים את המערך למחרוזת מופרדת בפסיקים

    $res = $con->query("
        SELECT t.*, COUNT(*) AS relevance
        FROM registeration r
        JOIN training t ON r.trainingNum = t.trainingNum
        WHERE r.userId IN ($similarUsersList)
          AND r.trainingNum NOT IN ($userTrainingsList)
          AND CONCAT(t.Date, ' ', t.Time) >= NOW()
        GROUP BY r.trainingNum
        ORDER BY relevance DESC 
        LIMIT 3
    ");
    // order by relevance - הצגת האימונים לפי פופולריות 
    // array for 3 trainings
    while ($row = $res->fetch_assoc()) {
        $recommendedTrainings[] = $row;
    }
} // יצירת מערך שמכיל האימונים במומלצים למשתמש המחובר
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FitHub - Home</title>
  <style>
    body {
        font-family: 'Times New Roman';
        margin: 0;
        padding: 0;
        color: #000;
        background: url('images/gym.jpeg');
        background-size: cover;
        background-attachment: fixed;
        background-position: center;
        background-repeat: no-repeat;
    }

    a {
        text-decoration: none;
        color: inherit;
    }

    .navbar {
        background-color: rgba(0, 0, 0, 0.7);
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        font-size: 2rem;
        font-weight: bold;
        color: rgb(116, 146, 115);
    }

    .nav-links {
        list-style: none;
        display: flex;
        gap: 2rem;
        font-size: 18px;
    }

    .nav-links li a {
        font-weight: bold;
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        transition: background-color 0.3s;
    }

    .nav-links li a:hover,
    .nav-links li a.active {
        background-color: rgba(167, 178, 139, 0.7);
    }

    .hero {
        display: flex;
        align-items: center;
        height: 100vh;
        padding: 0 5%;
        position: relative;
        margin-top:-420px;
    }

    .hero-content {
        width: 500px;
        background-color: rgba(255, 255, 255, 0.9);
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        text-align: center;
        position: relative;
        height: 300px;
    }

    .recommend-title {
        color: #6f8d4f;
        margin-bottom: 20px;
        font-size: 2rem;
        font-weight: bold;
    }

    .training-card {
        background-color: rgba(255, 255, 255, 0.9);
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 12px;
        text-align: left;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease, transform 0.5s ease;
        position: absolute;
        width: 430px;
        margin-left:20px;
        display: flex;
        justify-content: space-betwen;
        gap: 20px;
      
    }

    .training-card.active {
        opacity: 1;
        transform: translateY(0);
    }

    .training-card h3 {
        color: #000;
        margin-top: 0;
    }

    .training-card p {
        color: #333;
        margin: 5px 0;
    }

    .training-meta {
        color: #555;
    }

    .cta-button {
        margin-top: 2rem;
        display: inline-block;
        background-color: rgb(205, 232, 191);
        color: #000;
        padding: 12px 24px;
        font-weight: bold;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        transition: background-color 0.3s ease;
    }

    .cta-button:hover {
        background-color: rgb(229, 241, 220);
    }

    .training-indicators {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
    }

    .indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: rgba(111, 141, 79, 0.3);
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .indicator.active {
        background-color: rgb(111, 141, 79);
    }
     .card img {
            width: 150px;
            height:150px;
            object-fit: cover;
            padding:10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2); /* מראה מודרני */
            border-radius:10px;
        }

    /* משפט מוטיבציה */
    .motivation-quote {
        position: absolute;
        top: 120px;
        left:80px;
        background: rgba(255, 255, 255, 0.95);
        padding: 25px 30px;
        border-radius: 15px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(10px);
        max-width: 350px;
        text-align: center;
        animation: fadeInRight 1s ease-out;
        z-index: 3;
    }

    .quote-text {
        font-size: 18px;
        color: #2c3e50;
        font-style: italic;
        margin-bottom: 10px;
        line-height: 1.4;
        transition: all 0.5s ease;
    }

    .quote-emoji {
        font-size: 24px;
        margin-bottom: 10px;
        display: block;
    }

    @keyframes fadeInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* אפקט הזוהר */
    .motivation-quote::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: linear-gradient(45deg, #6f8d4f, #a7b28b, #6f8d4f);
        border-radius: 17px;
        z-index: -1;
        animation: glow 3s ease-in-out infinite;
    }

    @keyframes glow {
        0%, 100% {
            opacity: 0.5;
        }
        50% {
            opacity: 0.8;
        }
    }

    .start-button {
        background: linear-gradient(135deg, #6f8d4f, #a7b28b);
        color: white;
        padding: 15px 30px;
        border: none;
        border-radius: 25px;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .start-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(111, 141, 79, 0.4);
    }
    .HomeDiv{
      padding: 20px;
      border-radius: 12px;
      text-align: center;
      width: 550px;
      margin-left:800px;
      font-size:20px;
      background-color: rgba(255, 255, 255, 0.4);
      margin-top:30px;
    }
    .homeHeader{
      font-size:30px;
      background: linear-gradient(45deg, #6f8d4f, #a7b28b, #6f8d4f);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;   
    }
  </style>
</head>
<body>
  <header class="navbar">
    <div class="logo">FitHub</div>
    <nav>
      <ul class="nav-links">
        <li><a href="profile.php">Profile</a></li>
        <li><a href="training.php">Trainings</a></li>
        <li><a href="events.php">Events</a></li>
        <li><a href="about.php">About Us</a></li>
      </ul>
    </nav>
  </header>
  <div class="bodyCon">
      <div class="HomeDiv">
        <h1 class="homeHeader">Why is Physical Activity So Important?</h1>
        <div class="HomeDivContent">
          those by the CDC, WHO, and Mayo Clinic – have shown that physical activity significantly reduces the risk of chronic diseases such as heart disease,
          type 2 diabetes, high blood pressure, and obesity. Lab and field tests reveal that regular workouts improve blood sugar levels,
          reduce systemic inflammation, and enhance immune function.
          On a mental level, exercise boosts the release of endorphins – natural mood elevators – which help reduce stress, anxiety, and depression,
          while also improving sleep and cognitive function. Strength training and weight-bearing activities are proven to maintain muscle mass and bone density,
          particularly as we age, reducing the risk of falls and fractures.
          Beyond aesthetics, physical activity enhances self-discipline, builds confidence,
          and empowers individuals to lead longer, healthier lives.
          Don’t wait for tomorrow – start moving today. Your body and mind will thank you.<br>
        </div>
      </div>
    <div>
      <div class="motivation-quote">
        <span class="quote-emoji" id="quoteEmoji">💪</span>
        <div class="quote-text" id="quoteText">
          Your only limit is you. Push beyond!
        </div>
      </div>

      <?php if (!empty($recommendedTrainings)) : ?>
      <section class="hero">
        <div class="hero-content">
          <h2 class="recommend-title">For you</h2>
          <div class="card">
          <?php foreach ($recommendedTrainings as $index => $training): ?>
            <div class="training-card" data-index="<?= $index ?>">
              <div><img src="<?php echo htmlspecialchars($training['img']); ?>"></div>
              <div>
                <h3><?= htmlspecialchars($training['trainingName']) ?></h3>
                <p class="training-meta">
                  <strong>📍</strong> <?= htmlspecialchars($training['Location']) ?><br>
                  <strong>📅</strong> <?= htmlspecialchars($training['Date']) ?> |
                  <strong>⏰</strong> <?= htmlspecialchars($training['Time']) ?>
                </p>
                <form method='post' action='selectedTraining.php' style='display: inline;'>
                  <input type='hidden' name='trainingNum' value='<?= $training['trainingNum'] ?>'>
                  <button type='submit' class='cta-button' style='border: none; cursor: pointer;'>Read More</button>
                </form>
              </div>
          </div>
          <?php endforeach; ?>
          
          <div class="training-indicators">
            <?php foreach ($recommendedTrainings as $index => $training): ?>
              <div class="indicator" data-index="<?= $index ?>"></div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
      <?php endif; ?>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.training-card');
      const indicators = document.querySelectorAll('.indicator');
      let currentIndex = 0;
      let intervalId;

      function showCard(index) {
        // הסתר את כל הקלפים
        cards.forEach(card => card.classList.remove('active'));
        indicators.forEach(indicator => indicator.classList.remove('active'));
        
        // הצג את הקלף הנוכחי
        if (cards[index]) {
          cards[index].classList.add('active');
          indicators[index].classList.add('active');
        }
      }

      function nextCard() {
        currentIndex = (currentIndex + 1) % cards.length;
        showCard(currentIndex);
      }

      function startRotation() {
        intervalId = setInterval(nextCard, 4000); // החלף כל 4 שניות
      }

      function stopRotation() {
        clearInterval(intervalId);
      }

      // הוסף אירועי לחיצה על האינדיקטורים
      indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
          currentIndex = index;
          showCard(currentIndex);
          stopRotation();
          startRotation(); // התחל מחדש את הרוטציה
        });
      });

      // הצג את הקלף הראשון ותתחיל את הרוטציה
      if (cards.length > 0) {
        showCard(0);
        if (cards.length > 1) {
          startRotation();
        }
      }

      // עצור את הרוטציה כשהעכבר נמצא על הקלף
      const heroContent = document.querySelector('.hero-content');
      if (heroContent) {
        heroContent.addEventListener('mouseenter', stopRotation);
        heroContent.addEventListener('mouseleave', () => {
          if (cards.length > 1) {
            startRotation();
          }
        });
      }

      // משפטי מוטיבציה מתחלפים
      const motivationQuotes = [
        { text: "Your only limit is you. Push beyond!", emoji: "💪" },
        { text: "Progress, not perfection!", emoji: "⭐" },
        { text: "Every workout counts!", emoji: "🔥" },
        { text: "Believe in yourself!", emoji: "✨" },
        { text: "Champions train, others complain!", emoji: "🏆" },
        { text: "Your body can do it. Convince your mind!", emoji: "🧠" },
        { text: "Success starts with self-discipline!", emoji: "💯" },
        { text: "Strong body, strong mind!", emoji: "⚡" }
      ];

      let quoteIndex = 0;
      const quoteText = document.getElementById('quoteText');
      const quoteEmoji = document.getElementById('quoteEmoji');
      
      function changeMotivationQuote() {
        quoteIndex = (quoteIndex + 1) % motivationQuotes.length;
        const quote = motivationQuotes[quoteIndex];
        
        // אפקט דהייה
        quoteText.style.opacity = '0';
        quoteEmoji.style.opacity = '0';
        
        setTimeout(() => {
          quoteText.textContent = quote.text;
          quoteEmoji.textContent = quote.emoji;
          quoteText.style.opacity = '1';
          quoteEmoji.style.opacity = '1';
        }, 300);
      }

      // החלף משפט מוטיבציה כל 6 שניות
      setInterval(changeMotivationQuote, 6000);
    });
  </script>
</body>
</html>