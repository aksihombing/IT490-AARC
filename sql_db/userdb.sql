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
  `id` int NOT NULL AUTO_INCREMENT,
  `emailAddress` varchar(250) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `sessions` (
  `session_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_key` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_key`(`session_key`),
  KEY `user_id`(`user_id`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `clubs` (
  `club_id` INT NOT NULL AUTO_INCREMENT,
  `owner_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  PRIMARY KEY (`club_id`),
  UNIQUE KEY `unique_owner_name`(`owner_id`,`name`),
  CONSTRAINT `fk_club_owner` FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `club_members` (
  `member_id` INT NOT NULL AUTO_INCREMENT,
  `club_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `unique_invite`(`club_id`,`user_id`),
  KEY (`club_id`),
  KEY `fk_member_user`(`user_id`),
  CONSTRAINT `fk_member_club` FOREIGN KEY (`club_id`) REFERENCES `clubs`(`club_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `events` (
  `eventID` INT NOT NULL AUTO_INCREMENT,
  `creatorUserID` INT NOT NULL,
  `club_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `event_date` date DEFAULT NULL,
  `startTime` datetime DEFAULT NULL,
  `endTime` datetime DEFAULT NULL,
  PRIMARY KEY (`eventID`),
  UNIQUE KEY `unique_event`(`club_id`,`title`,`event_date`),
  KEY `creatorUserID`(`creatorUserID`),
  CONSTRAINT `fk_creator_user` FOREIGN KEY (`creatorUserID`) REFERENCES `users`(`id`),
  CONSTRAINT `fk_event_club` FOREIGN KEY (`club_id`) REFERENCES `clubs`(`club_id`) ON DELETE CASCADE
);

CREATE TABLE `EventAttendees` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `eventID` INT NOT NULL,
  `userID` INT NOT NULL,
  `rsvpStatus` VARCHAR(20) NOT NULL DEFAULT 'Going',
  `attended` tinyint(1) DEFAULT 0,
  `rsvpDate` TIMESTAMP NULL CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `eventUserUnique`(`eventID`,`userID`), 
  CONSTRAINT `fkEventAttendeeEventID` FOREIGN KEY (`eventID`) REFERENCES `events`(`eventID`) ON DELETE CASCADE,
  CONSTRAINT `fkEventAttendeeUserID` FOREIGN KEY (`userID`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE user_library (
  user_id  INT NOT NULL,
  works_id VARCHAR(50) NOT NULL,
  added_at TIMESTAMP NULL CURRENT_TIMESTAMP,
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

CREATE TABLE `club_invites` (
  `invite_id` int NOT NULL AUTO_INCREMENT,
  `club_id` int NOT NULL,
  `hash` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`invite_id`),
  UNIQUE KEY `hash` (`hash`),
  KEY `club_id` (`club_id`),
  CONSTRAINT `club_invites_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE
);
