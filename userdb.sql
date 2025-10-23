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
  `owner_id` INT(11) NULL,
  `name` VARCHAR(100) NULL,
  `description` TEXT NULL,
  PRIMARY KEY (`club_id`)
);

CREATE TABLE `club_members` (
  `member_id` INT(11) NOT NULL AUTO_INCREMENT,
  `club_id` INT(11) NULL,
  `user_id` INT(11) NULL,
  PRIMARY KEY (`member_id`),
    KEY (`club_id`)
);

CREATE TABLE `club_events` (
  `event_id` INT(11) NOT NULL AUTO_INCREMENT,
  `club_id` INT(11) NULL,
  `title` VARCHAR(100) NULL,
  `event_date` DATE NULL,
  `description` TEXT NULL,
  PRIMARY KEY (`event_id`)
);

CREATE TABLE `events` (
  `eventID` INT(11) NOT NULL AUTO_INCREMENT,
  `creatorUserID` INT(11) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `location` VARCHAR(255) NULL,
  `startTime` DATETIME NOT NULL,
  `endTime` DATETIME NOT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`eventID`),
  KEY `creatorUserID` (`creatorUserID`),
  CONSTRAINT `fk_creator_user` FOREIGN KEY (`creatorUserID`) REFERENCES `users` (`id`)
);

CREATE TABLE `EventAttendees` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `eventID` INT(11) NOT NULL,
  `userID` INT(11) NOT NULL,
  `rsvpStatus` VARCHAR(20) NOT NULL DEFAULT 'Going',
  `attended` BOOLEAN DEFAULT FALSE,
  `rsvpDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `eventUserUnique` (`eventID`, `userID`), 
  CONSTRAINT `fkEventAttendeeEventID` FOREIGN KEY (`eventID`) REFERENCES `events` (`eventID`) ON DELETE CASCADE,
  CONSTRAINT `fkEventAttendeeUserID` FOREIGN KEY (`userID`) REFERENCES `users` (`id`) ON DELETE CASCADE
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