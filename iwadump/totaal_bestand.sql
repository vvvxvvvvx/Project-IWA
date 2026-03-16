-- Tabellen aanmaken

CREATE TABLE `country` (
  `country_code` varchar(2) NOT NULL,
  `country_name` varchar(100) NOT NULL,
  PRIMARY KEY (`country_code`)
);

CREATE TABLE `companies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `city` varchar(100),
  `street` varchar(100),
  `number` int,
  `number_additional` varchar(15),
  `zip_code` varchar(15),
  `country` varchar(2) NOT NULL,
  `email` varchar(100),
  PRIMARY KEY (`id`)
);

CREATE TABLE `job_titles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `initials` varchar(10),
  `insertion` varchar(20),
  `email` varchar(100) NOT NULL,
  `employee_number` varchar(20) NOT NULL,
  `role_id` int NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `initials` varchar(10),
  `insertion` varchar(20),
  `email` varchar(100) NOT NULL,
  `company_id` int NOT NULL,
  `job_title_id` int NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `incident_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `incident_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `priority` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `stations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `incidents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `user_id` int NOT NULL,
  `station_id` int NOT NULL,
  `incident_type_id` int NOT NULL,
  `priority_id` int NOT NULL,
  `incident_status_id` int NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Relaties (Foreign Keys) toevoegen

ALTER TABLE `companies` ADD CONSTRAINT `fk_company_country` FOREIGN KEY (`country`) REFERENCES `country` (`country_code`);
ALTER TABLE `users` ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
ALTER TABLE `contacts` ADD CONSTRAINT `fk_contact_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);
ALTER TABLE `contacts` ADD CONSTRAINT `fk_contact_job` FOREIGN KEY (`job_title_id`) REFERENCES `job_titles` (`id`);
ALTER TABLE `contacts` ADD CONSTRAINT `fk_contact_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
ALTER TABLE `incidents` ADD CONSTRAINT `fk_incident_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
ALTER TABLE `incidents` ADD CONSTRAINT `fk_incident_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);
ALTER TABLE `incidents` ADD CONSTRAINT `fk_incident_type` FOREIGN KEY (`incident_type_id`) REFERENCES `incident_type` (`id`);
ALTER TABLE `incidents` ADD CONSTRAINT `fk_incident_priority` FOREIGN KEY (`priority_id`) REFERENCES `priority` (`id`);
ALTER TABLE `incidents` ADD CONSTRAINT `fk_incident_status` FOREIGN KEY (`incident_status_id`) REFERENCES `incident_status` (`id`);