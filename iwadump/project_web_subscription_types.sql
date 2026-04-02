CREATE DATABASE  IF NOT EXISTS `project_web` ;
USE `project_web`;

DROP TABLE IF EXISTS `subscription_types`;
CREATE TABLE `subscription_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `description` varchar(256) DEFAULT NULL,
  `nr_stations` int DEFAULT NULL,
  `frequency_in_hours` int DEFAULT NULL,
  `frequency_in_days` int DEFAULT NULL,
  `continuous` tinyint DEFAULT NULL,
  `price_per_station` float NOT NULL,
  `valid_through` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3;

LOCK TABLES `subscription_types` WRITE;

INSERT INTO `subscription_types` VALUES (11,'SIMPLE','Eenvoudig abonnement voor dagelijkse update van gegevens voor 1 station',1,NULL,1,0,49,NULL),(12,'BUDGET','Goedkoop abonnement voor weekelijkse informatie',1,NULL,7,0,24.99,NULL),(13,'STREAM','Live data voor 1 station',1,NULL,NULL,1,115.76,NULL),(14,'SIMPLE+','Eenvoudig abonnement voor 1 station met data per uur',1,1,NULL,0,65,NULL),(15,'GROUP','Weekelijkse data voor meerdere stations',NULL,NULL,7,0,23.5,NULL),(16,'GROUP+','Dagelijkse data voor meerdere stations',NULL,NULL,1,0,28.25,NULL),(17,'GROUP++','Data elk uur voor meerdere stations',NULL,1,NULL,NULL,47.65,NULL);

UNLOCK TABLES;
