CREATE DATABASE  IF NOT EXISTS `project_web` ;
USE `project_web`;

DROP TABLE IF EXISTS `measurement`;

CREATE TABLE `measurement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `station` varchar(10) CHARACTER SET utf16 COLLATE utf16_general_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `temperature` float DEFAULT NULL,
  `dewpoint_temperature` float DEFAULT NULL,
  `air_pressure_station` float DEFAULT NULL,
  `air_pressure_sea_level` float DEFAULT NULL,
  `visibility` float DEFAULT NULL,
  `wind_speed` float DEFAULT NULL,
  `percipation` float DEFAULT NULL,
  `snow_depth` float DEFAULT NULL,
  `conditions` varchar(6) DEFAULT NULL,
  `cloud_cover` float DEFAULT NULL,
  `wind_direction` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `measurement_station` (`station`),
  CONSTRAINT `measurement_station` FOREIGN KEY (`station`) REFERENCES `station` (`name`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
