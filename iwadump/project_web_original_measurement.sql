CREATE DATABASE  IF NOT EXISTS `project_web` ;
USE `project_web`;

DROP TABLE IF EXISTS `original_measurement`;
CREATE TABLE `original_measurement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `corrected_measurement` int NOT NULL,
  `missing_field` varchar(32) DEFAULT NULL,
  `inavlid_temperature` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `measurement_correction` (`corrected_measurement`),
  CONSTRAINT `measurement_correction` FOREIGN KEY (`corrected_measurement`) REFERENCES `measurement` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
