CREATE DATABASE  IF NOT EXISTS `project_web` ;
USE `project_web`;

DROP TABLE IF EXISTS `userroles`;

CREATE TABLE `userroles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role` varchar(45) NOT NULL,
  `description` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_UNIQUE` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;

LOCK TABLES `userroles` WRITE;

INSERT INTO `userroles` VALUES (1,'Technisch medewerker','Medewerker van de afdeling monitoring en beheer'),(2,'Technisch onderzoeker','Medewerker van de afdeling analyse en datamining'),(3,'Commercieel medewerker','Medewerker van de afdeling marketing en klant beheer'),(4,'Administratief medewerker','Medewerker van de afdeling personeelszaken'),(5,'Technisch beheerder','Medewerker van de afdeling IT-support'),(6,'Administrator','Super user');

UNLOCK TABLES;
