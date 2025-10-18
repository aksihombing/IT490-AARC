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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
