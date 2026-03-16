CREATE DATABASE  IF NOT EXISTS `project_web` ;
USE `project_web`;

DROP TABLE IF EXISTS `subscription_station`;
CREATE TABLE `subscription_station` (
  `subscription` int NOT NULL,
  `station` varchar(10) CHARACTER SET utf16 COLLATE utf16_general_ci NOT NULL,
  PRIMARY KEY (`subscription`,`station`),
  KEY `station_subscription` (`station`),
  CONSTRAINT `company_subscription` FOREIGN KEY (`subscription`) REFERENCES `subscriptions` (`id`),
  CONSTRAINT `station_subscription` FOREIGN KEY (`station`) REFERENCES `station` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

LOCK TABLES `subscription_station` WRITE;
INSERT INTO `subscription_station` VALUES (5,'102240'),(8,'20500'),(8,'21730'),(8,'28360'),(8,'29430'),(9,'30890'),(4,'62000'),(1,'62250'),(1,'62350'),(6,'62390'),(1,'62400'),(2,'62500'),(3,'62500'),(4,'62600'),(7,'62670'),(2,'62680'),(3,'62680'),(2,'62690'),(2,'62790'),(3,'62790'),(4,'62790'),(2,'62800'),(3,'62800'),(4,'62800'),(1,'63300'),(10,'70150'),(10,'74820');
UNLOCK TABLES;
