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