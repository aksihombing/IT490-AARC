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
  MULTIPLE KEY (`club_id`)
);
