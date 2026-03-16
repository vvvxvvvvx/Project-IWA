CREATE DATABASE  IF NOT EXISTS `project_web` /*!40100 DEFAULT CHARACTER SET utf8mb3 */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `project_web`;

DROP TABLE IF EXISTS `endpoint_activity`;

CREATE TABLE `endpoint_activity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `identifier` varchar(45) NOT NULL,
  `endpoint_used` varchar(256) NOT NULL,
  `files_downloaded` int NOT NULL,
  `activity_date` date NOT NULL,
  `activity_time` time NOT NULL,
  `authorized` tinyint DEFAULT NULL,
  `data_transferred` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `Activity_identifier_idx` (`identifier`),
  CONSTRAINT `Activity_identifier` FOREIGN KEY (`identifier`) REFERENCES `subscriptions` (`identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3;

LOCK TABLES `endpoint_activity` WRITE;
INSERT INTO `endpoint_activity` VALUES (1,'HANZE14','IWA/abonnement/HANZE14',1,'2025-01-15','12:00:02',1,40962568),(2,'HANZE14','IWA/abonnement/HANZE14',1,'2025-01-16','09:43:23',1,40962568),(3,'HANZE14','IWA/abonnement/HANZE14',1,'2025-01-17','12:00:08',1,40962568),(4,'HANZE14','IWA/abonnement/HANZE14',2,'2025-01-19','12:00:05',1,81925136),(5,'HANZE14','IWA/abonnement/HANZE14/stations',0,'2025-02-06','10:05:41',1,16),(6,'OXFOR17','IWA/abonnement',0,'2025-02-07','01:00:05',0,0),(7,'OXFOR17','IWA/abonnement/OXFOR17',1,'2025-02-08','01:00:06',1,1281648),(8,'OXFOR17','IWA/abonnement/OXFOR17',1,'2025-02-08','02:00:06',1,1281648);
UNLOCK TABLES;
