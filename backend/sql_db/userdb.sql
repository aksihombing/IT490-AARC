DROP TABLE IF EXISTS `EventAttendees`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `user_library`;
DROP TABLE IF EXISTS `club_events`;
DROP TABLE IF EXISTS `club_members`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `clubs`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emailAddress` varchar(250) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_key` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_key` (`session_key`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
);

CREATE TABLE `clubs` (
  `club_id` INT(11) NOT NULL AUTO_INCREMENT,
  `owner_id` INT(11) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  PRIMARY KEY (`club_id`)
);

CREATE TABLE `club_members` (
  `member_id` INT(11) NOT NULL AUTO_INCREMENT,
  `club_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  PRIMARY KEY (`member_id`),
    KEY (`club_id`)
);

CREATE TABLE `events` (
  `eventID` INT(11) NOT NULL AUTO_INCREMENT,
  `creatorUserID` INT(11) NOT NULL,
  `club_id` INT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `location` VARCHAR(255) NULL,
  `startTime` DATETIME NOT NULL,
  `endTime` DATETIME NOT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`eventID`),
  KEY `creatorUserID` (`creatorUserID`),
  CONSTRAINT `fk_creator_user` FOREIGN KEY (`creatorUserID`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_event_club` FOREIGN KEY (`club_id`) REFERENCES `clubs`(`club_id`) ON DELETE CASCADE
);

CREATE TABLE `EventAttendees` (
  `eventID` INT(11) NOT NULL,
  `userID` INT(11) NOT NULL,
  `rsvpStatus` ENUM('going','not_going') NOT NULL DEFAULT 'going',
  `rsvpDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`eventID`,`userID`)
);

CREATE TABLE user_library (
  user_id  INT NOT NULL,
  works_id VARCHAR(50) NOT NULL,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (user_id, works_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
  user_id  INT NOT NULL,
  works_id VARCHAR(50) NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  body TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (user_id, works_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE club_invites (
  invite_id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT NOT NULL,
  hash VARCHAR(64) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE
);
