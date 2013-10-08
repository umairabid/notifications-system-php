CREATE TABLE `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `actor_id` INT(11) DEFAULT NULL, /* To store actor Id */
  `subject_id` INT(11) DEFAULT NULL, /* To store subject Id */
  `object_id` INT(11) DEFAULT NULL, /* To store object Id */
  `type_id` INT(11) DEFAULT NULL, /* To store type Id */
  `status` VARCHAR(100) DEFAULT NULL, /* To store status of notification i.e seen or not unseen */
  `created_date` DATETIME DEFAULT NULL, /* To store notification creation datetime */
  `updated_date` DATETIME DEFAULT NULL, /* To store notification updgradation datetime */
  PRIMARY KEY (`id`)
) ENGINE=MYISAM AUTO_INCREMENT=1126 DEFAULT CHARSET=utf8;
,
CREATE TABLE `users` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(250) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDb DEFAULT CHARSET=utf8;

CREATE TABLE `status` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`user_id` INT(11) NOT NULL,
	`status` TEXT NOT NULL,
	`created_date` DATETIME,
	`updated_date` DATETIME,
	PRIMARY KEY (`id`)
) ENGINE=INNODB, DEFAULT CHARSET=utf8;

CREATE TABLE `comments` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`status_id` INT(11) NOT NULL,
	`user_id` INT(11) NOT NULL,
	`comment` TEXT NOT NULL,
	`created_date` DATETIME,
	`updated_date` DATETIME,
	PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

INSERT INTO users VALUES ('', 'Umair Abid'), ('', 'Umair Maqsood');
INSERT INTO status VALUES('', 1, 'This is my test status', CURDATE(), '')