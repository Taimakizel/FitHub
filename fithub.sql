-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: אוקטובר 01, 2026 בזמן 06:58 PM
-- גרסת שרת: 8.3.0
-- PHP Version: 8.1.28

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fithub`
--

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `cancellations`
--

DROP TABLE IF EXISTS `cancellations`;
CREATE TABLE IF NOT EXISTS `cancellations` (
  `cancellation_id` int NOT NULL AUTO_INCREMENT,
  `userId` int NOT NULL,
  `trainingNum` int NOT NULL,
  `cancellation_date` datetime NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`cancellation_id`),
  KEY `userId` (`userId`),
  KEY `trainingNum` (`trainingNum`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `card`
--

DROP TABLE IF EXISTS `card`;
CREATE TABLE IF NOT EXISTS `card` (
  `autoINC` int NOT NULL AUTO_INCREMENT,
  `CardNum` varchar(16) COLLATE utf8mb3_unicode_ci NOT NULL,
  `ExpirationDate` date NOT NULL,
  `CVV` int NOT NULL,
  `code` int NOT NULL,
  `userId` int NOT NULL,
  PRIMARY KEY (`autoINC`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `eventregisteration`
--

DROP TABLE IF EXISTS `eventregisteration`;
CREATE TABLE IF NOT EXISTS `eventregisteration` (
  `registerationNum` int NOT NULL AUTO_INCREMENT,
  `userId` varchar(9) COLLATE utf8mb3_unicode_ci NOT NULL,
  `eventId` int NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`registerationNum`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `eventId` int NOT NULL AUTO_INCREMENT,
  `eventName` varchar(200) COLLATE utf8mb3_unicode_ci NOT NULL,
  `Date` datetime NOT NULL,
  `Description` varchar(2000) COLLATE utf8mb3_unicode_ci NOT NULL,
  `Location` varchar(200) COLLATE utf8mb3_unicode_ci NOT NULL,
  `eventImg` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`eventId`)
) ENGINE=MyISAM AUTO_INCREMENT=2213 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE IF NOT EXISTS `expenses` (
  `ExNum` int NOT NULL AUTO_INCREMENT,
  `expense_type` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `vendor` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `receipt_path` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`ExNum`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- הוצאת מידע עבור טבלה `expenses`
--

INSERT INTO `expenses` (`ExNum`, `expense_type`, `description`, `amount`, `expense_date`, `vendor`, `category`, `receipt_path`, `created_at`) VALUES
(7, 'equipment', 'equipment for gym', 200.00, '2025-09-12', 'FitForce', 'Gym Equipment', 'receipts/receipt_68c8e7a0b0849.png', '2025-09-16 07:29:20');

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `points_history`
--

DROP TABLE IF EXISTS `points_history`;
CREATE TABLE IF NOT EXISTS `points_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userId` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `points_change` int NOT NULL,
  `points_type` enum('earned','used') COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `registration_num` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  KEY `registration_num` (`registration_num`)
) ENGINE=MyISAM AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `registeration`
--

DROP TABLE IF EXISTS `registeration`;
CREATE TABLE IF NOT EXISTS `registeration` (
  `registerationNum` int NOT NULL AUTO_INCREMENT,
  `userId` varchar(9) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `trainingNum` int NOT NULL,
  `date` datetime NOT NULL,
  `price` float NOT NULL,
  `points_used` int DEFAULT '0',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `final_price` decimal(10,2) DEFAULT '0.00',
  `points_earned` int DEFAULT '10',
  PRIMARY KEY (`registerationNum`)
) ENGINE=MyISAM AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `training`
--

DROP TABLE IF EXISTS `training`;
CREATE TABLE IF NOT EXISTS `training` (
  `trainingNum` int NOT NULL AUTO_INCREMENT,
  `trainingName` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `Description` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `Duration` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `Location` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `Date` date NOT NULL,
  `Time` time NOT NULL,
  `Level` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `Goal` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `Participants` int NOT NULL,
  `maxParticipants` int NOT NULL,
  `Type` int NOT NULL,
  `img` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `Price` decimal(6,2) NOT NULL,
  `TrainerId` bigint DEFAULT NULL,
  PRIMARY KEY (`trainingNum`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- הוצאת מידע עבור טבלה `training`
--

INSERT INTO `training` (`trainingNum`, `trainingName`, `Description`, `Duration`, `Location`, `Date`, `Time`, `Level`, `Goal`, `Participants`, `maxParticipants`, `Type`, `img`, `Price`, `TrainerId`) VALUES
(23, 'pilates training', 'he workout then moves on to core-strengthening exercises, targeting the abdominal and back muscles with movements such as the “Hundred,” “Roll-Up,” and “Leg Circles.” This is followed by exercises for the upper and lower body, including leg lifts, arm movements, and exercises using small balls or resistance bands.', '60', 'maghar', '2026-03-31', '15:30:00', 'All', 'Stretching', 2, 10, 10, 'uploads/pilatestraining.jpg', 159.90, 315439876),
(22, 'Zumba Dance Party', 'Fun and energetic dance workout combining Latin and international music with easy-to-follow choreography. Burns calories while improving coordination and mood.', '50', 'Studio C - maghar', '2025-09-20', '18:10:00', 'All', 'Fat Burn', 2, 20, 8, 'uploads/zomba.jpg', 40.00, 302432144),
(21, 'Strength & Conditioning', 'Focus on building muscle strength and improving overall conditioning using free weights, resistance bands, and bodyweight exercises.', '60', 'Weight GYM - Haifa', '2025-10-01', '14:00:00', 'Advanced', 'Muscle Gain', 2, 12, 10, 'uploads/StrengthConditioning.jpg', 60.00, 302432144),
(19, 'pilates training', 'he workout then moves on to core-strengthening exercises, targeting the abdominal and back muscles with movements such as the “Hundred,” “Roll-Up,” and “Leg Circles.” This is followed by exercises for the upper and lower body, including leg lifts, arm movements, and exercises using small balls or resistance bands.', '60', 'icon fitness karmial', '2025-08-30', '12:00:00', 'Beginners', 'Stretching', 2, 10, 9, 'uploads/pilatestraining.jpg', 85.00, 315439876),
(20, 'HIIT Blast', 'High-intensity interval training combining cardio, strength, and core exercises. Includes jumping jacks, burpees, squats, and push-ups in timed intervals for maximum fat burn.', '45', 'Main Gym Arena', '2025-09-25', '17:00:00', 'Intermediate', 'Fat Burn', 1, 15, 8, 'uploads/hit.jpg', 50.00, 315439876),
(24, 'Yoga Flow', 'A gentle yoga class focusing on flexibility, balance, and relaxation. Perfect for stress relief and mindfulness.', '60', 'Studio A - Tel Aviv', '2025-09-18', '08:10:00', 'All', 'Stretching', 1, 15, 3, 'uploads/yoga.jpg', 200.00, 302432144),
(25, 'HIIT Blast', 'High-intensity interval training to burn fat quickly and improve cardiovascular fitness. Short bursts of intense exercises followed by rest.', '45', 'Gym Floor - Haifa', '2025-09-20', '11:00:00', 'Intermediate', 'Fat Burn', 3, 20, 8, 'uploads/hitt.jpg', 50.00, 302432144),
(26, 'Strength Training', 'Full-body strength workout using weights and resistance exercises to build muscle and increase strength.', '50', 'Weight Room - maghar', '2025-09-22', '06:30:00', 'Advanced', 'Muscle Gain', 1, 12, 10, 'uploads/images.jpeg', 80.00, 315439876);

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `types`
--

DROP TABLE IF EXISTS `types`;
CREATE TABLE IF NOT EXISTS `types` (
  `typeId` int NOT NULL AUTO_INCREMENT,
  `typeName` varchar(200) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`typeId`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- הוצאת מידע עבור טבלה `types`
--

INSERT INTO `types` (`typeId`, `typeName`) VALUES
(8, 'Cardio'),
(2, 'Running'),
(3, 'stretch'),
(9, 'Flexibility'),
(10, 'Strength');

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `userId` int NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(200) COLLATE utf8mb3_unicode_ci NOT NULL,
  `LastName` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `Email` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `Phone` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `Password` varchar(25) COLLATE utf8mb3_unicode_ci NOT NULL,
  `JoinDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `Role` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT 'images/default-user.png',
  `points` int DEFAULT '0',
  `login_attempts` int DEFAULT '0',
  `last_attempt` datetime DEFAULT NULL,
  `verification_code` varchar(6) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `code_expiry` datetime DEFAULT NULL,
  `salary` int DEFAULT '0',
  PRIMARY KEY (`userId`)
) ENGINE=MyISAM AUTO_INCREMENT=324567892 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `weights`
--

DROP TABLE IF EXISTS `weights`;
CREATE TABLE IF NOT EXISTS `weights` (
  `WeightId` int NOT NULL AUTO_INCREMENT,
  `userId` int NOT NULL,
  `weight` int NOT NULL,
  `DateRecorded` datetime NOT NULL,
  PRIMARY KEY (`WeightId`)
) ENGINE=MyISAM AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
