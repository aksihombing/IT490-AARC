CREATE TABLE `clubs` (
  `club_id` INT(11) NOT NULL AUTO_INCREMENT,
  `owner_id` INT(11) NULL,
  `name` VARCHAR(100) NULL,
  `description` TEXT NULL,
  PRIMARY KEY (`club_id`)
);
