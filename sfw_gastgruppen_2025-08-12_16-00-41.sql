/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: sfw_gastgruppen
-- ------------------------------------------------------
-- Server version	10.11.13-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `abrechnung`
--

DROP TABLE IF EXISTS `abrechnung`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `abrechnung` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fluglager_id` int(11) NOT NULL,
  `daten` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`daten`)),
  `status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fluglager_id` (`fluglager_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `abrechnung`
--

LOCK TABLES `abrechnung` WRITE;
/*!40000 ALTER TABLE `abrechnung` DISABLE KEYS */;
/*!40000 ALTER TABLE `abrechnung` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aenderungshistorie`
--

DROP TABLE IF EXISTS `aenderungshistorie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aenderungshistorie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fluglager_id` int(11) NOT NULL,
  `bearbeiter` varchar(255) NOT NULL,
  `status_von` varchar(50) NOT NULL,
  `status_nach` varchar(50) NOT NULL,
  `kommentar` text DEFAULT NULL,
  `zeitpunkt` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fluglager_id` (`fluglager_id`),
  CONSTRAINT `aenderungshistorie_ibfk_1` FOREIGN KEY (`fluglager_id`) REFERENCES `fluglager` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aenderungshistorie`
--

LOCK TABLES `aenderungshistorie` WRITE;
/*!40000 ALTER TABLE `aenderungshistorie` DISABLE KEYS */;
INSERT INTO `aenderungshistorie` VALUES
(7,9,'Admin','eingereicht','bestaetigt','Wir freuen uns auf Euch!','2025-08-11 11:03:44'),
(8,9,'Admin','bestaetigt','abgelehnt','Pech gehabt','2025-08-11 13:02:11');
/*!40000 ALTER TABLE `aenderungshistorie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `einstellungen`
--

DROP TABLE IF EXISTS `einstellungen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `einstellungen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `einstellung_name` varchar(100) NOT NULL,
  `einstellung_wert` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `einstellung_name` (`einstellung_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `einstellungen`
--

LOCK TABLES `einstellungen` WRITE;
/*!40000 ALTER TABLE `einstellungen` DISABLE KEYS */;
INSERT INTO `einstellungen` VALUES
(1,'preis_anzahlung','35'),
(2,'max_teilnehmer','45'),
(3,'max_flugzeuge','10'),
(4,'admin_email','vorstand1@flugplatz-ohlstadt.de'),
(5,'kontonummer_anzahlung','DE78 7039 0000 0000 0058 35'),
(6,'notification_emails','vorstand1@flugplatz-ohlstadt.de,info@achim-holzmann.de'),
(7,'abrechnungsbenachrichtigung_emails','buchhaltung@flugplatz-ohlstadt.de');
/*!40000 ALTER TABLE `einstellungen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fluglager`
--

DROP TABLE IF EXISTS `fluglager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fluglager` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `startdatum` date NOT NULL,
  `enddatum` date NOT NULL,
  `exklusiv` tinyint(1) DEFAULT 0,
  `status` enum('in_planung','eingereicht','bestaetigt','abgelehnt','abrechnung_gesendet','fertig_abgerechnet') DEFAULT 'in_planung',
  `kommentar_admin` text DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  `geaendert_am` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ansprechpartner_vorname` varchar(255) DEFAULT NULL,
  `ansprechpartner_nachname` varchar(255) DEFAULT NULL,
  `ansprechpartner_email` varchar(255) DEFAULT NULL,
  `ansprechpartner_telefon` varchar(255) DEFAULT NULL,
  `hinweise_an_admin` text DEFAULT NULL,
  `anzahlung_bezahlt` tinyint(1) NOT NULL DEFAULT 0,
  `abrechnung_gesendet` tinyint(1) NOT NULL DEFAULT 0,
  `anzahlung_bezahlt_am` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fluglager_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fluglager`
--

LOCK TABLES `fluglager` WRITE;
/*!40000 ALTER TABLE `fluglager` DISABLE KEYS */;
INSERT INTO `fluglager` VALUES
(9,11,'2025-08-18','2025-08-24',1,'abgelehnt','Pech gehabt','2025-08-11 10:39:43','2025-08-11 13:02:11','Peter','Lustig','achimace@googlemail.com','01701240376','Wir würden gern Grillen',0,0,NULL);
/*!40000 ALTER TABLE `fluglager` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flugzeuge`
--

DROP TABLE IF EXISTS `flugzeuge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `flugzeuge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fluglager_id` int(11) NOT NULL,
  `typ` varchar(255) NOT NULL,
  `kennzeichen` varchar(100) NOT NULL,
  `muster` varchar(255) DEFAULT NULL,
  `flarm_id` varchar(100) DEFAULT NULL,
  `spot` varchar(100) DEFAULT NULL,
  `pilot_id` int(11) DEFAULT NULL,
  `abrechnung_anreise` date DEFAULT NULL,
  `abrechnung_abreise` date DEFAULT NULL,
  `abrechnung_tage_halle` int(11) NOT NULL DEFAULT 0,
  `abrechnung_tage_werkstatt` int(11) NOT NULL DEFAULT 0,
  `hat_teilgenommen` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fluglager_id` (`fluglager_id`),
  KEY `fk_flugzeug_pilot` (`pilot_id`),
  CONSTRAINT `fk_flugzeug_pilot` FOREIGN KEY (`pilot_id`) REFERENCES `teilnehmer` (`id`) ON DELETE SET NULL,
  CONSTRAINT `flugzeuge_ibfk_1` FOREIGN KEY (`fluglager_id`) REFERENCES `fluglager` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flugzeuge`
--

LOCK TABLES `flugzeuge` WRITE;
/*!40000 ALTER TABLE `flugzeuge` DISABLE KEYS */;
INSERT INTO `flugzeuge` VALUES
(12,9,'Segler','D-1234','Astir CS','',NULL,NULL,NULL,NULL,0,0,1);
/*!40000 ALTER TABLE `flugzeuge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kalender_block`
--

DROP TABLE IF EXISTS `kalender_block`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kalender_block` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startdatum` date NOT NULL,
  `enddatum` date NOT NULL,
  `grund` varchar(255) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kalender_block`
--

LOCK TABLES `kalender_block` WRITE;
/*!40000 ALTER TABLE `kalender_block` DISABLE KEYS */;
INSERT INTO `kalender_block` VALUES
(5,'2025-08-15','2025-08-23','Kunstfluglehrgang',0,'2025-08-11 12:53:45');
/*!40000 ALTER TABLE `kalender_block` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preise`
--

DROP TABLE IF EXISTS `preise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `preise` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preis_name` varchar(100) NOT NULL COMMENT 'e.g., pilot_pro_tag, camping_pro_nacht',
  `wert` decimal(10,2) NOT NULL,
  `gueltig_ab` date NOT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preise`
--

LOCK TABLES `preise` WRITE;
/*!40000 ALTER TABLE `preise` DISABLE KEYS */;
INSERT INTO `preise` VALUES
(1,'pilot_pro_tag',20.00,'2020-01-01','2025-08-04 21:43:27'),
(2,'camping_pro_nacht',10.00,'2020-01-01','2025-08-04 21:43:27'),
(3,'flugzeug_stationierung_pro_tag',5.00,'2020-01-01','2025-08-04 21:43:27'),
(4,'flugzeug_halle_pro_tag',15.00,'2020-01-01','2025-08-04 21:43:27'),
(5,'pilot_pro_tag',7.00,'2025-01-01','2025-08-04 21:51:54'),
(6,'camping_pro_nacht',10.00,'2025-01-01','2025-08-04 21:51:54'),
(7,'flugzeug_stationierung_pro_tag',12.00,'2025-01-01','2025-08-04 21:51:54'),
(8,'flugzeug_halle_pro_tag',10.00,'2025-01-01','2025-08-04 21:51:54'),
(9,'pilot_pro_tag',12.00,'2026-01-01','2025-08-11 12:54:59'),
(10,'camping_pro_nacht',10.00,'2026-01-01','2025-08-11 12:54:59'),
(11,'flugzeug_stationierung_pro_tag',12.00,'2026-01-01','2025-08-11 12:54:59'),
(12,'flugzeug_halle_pro_tag',15.00,'2026-01-01','2025-08-11 12:54:59');
/*!40000 ALTER TABLE `preise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `status_log`
--

DROP TABLE IF EXISTS `status_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fluglager_id` int(11) NOT NULL,
  `status` enum('in_planung','eingereicht','bestaetigt','abgelehnt','abrechnung_gesendet','fertig_abgerechnet','info') DEFAULT 'info',
  `nachricht` text DEFAULT NULL,
  `geaendert_am` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fluglager_id` (`fluglager_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `status_log`
--

LOCK TABLES `status_log` WRITE;
/*!40000 ALTER TABLE `status_log` DISABLE KEYS */;
INSERT INTO `status_log` VALUES
(1,9,'eingereicht','Fluglager vom Kunden zur Prüfung eingereicht.','2025-08-11 10:47:09');
/*!40000 ALTER TABLE `status_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teilnehmer`
--

DROP TABLE IF EXISTS `teilnehmer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teilnehmer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fluglager_id` int(11) NOT NULL,
  `vorname` varchar(255) NOT NULL,
  `nachname` varchar(255) NOT NULL,
  `geburtsdatum` date DEFAULT NULL,
  `telefon` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `camping` tinyint(1) NOT NULL DEFAULT 0,
  `rolle` enum('Pilot','Flugschüler','Begleitperson') DEFAULT NULL,
  `vereinsflieger_nr` varchar(100) DEFAULT NULL,
  `aufenthalt_von` date DEFAULT NULL,
  `aufenthalt_bis` date DEFAULT NULL,
  `hat_teilgenommen` tinyint(1) NOT NULL DEFAULT 1,
  `abrechnung_naechte_camping` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fluglager_id` (`fluglager_id`),
  CONSTRAINT `teilnehmer_ibfk_1` FOREIGN KEY (`fluglager_id`) REFERENCES `fluglager` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teilnehmer`
--

LOCK TABLES `teilnehmer` WRITE;
/*!40000 ALTER TABLE `teilnehmer` DISABLE KEYS */;
INSERT INTO `teilnehmer` VALUES
(31,9,'Super','Man','1977-12-16',NULL,'super.man@test.de',1,'Pilot','123456','2025-08-11','2025-08-12',1,0);
/*!40000 ALTER TABLE `teilnehmer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vorname` varchar(100) NOT NULL,
  `nachname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobiltelefon` varchar(50) DEFAULT NULL,
  `vereinsname` varchar(255) DEFAULT NULL,
  `strasse` varchar(255) DEFAULT NULL,
  `plz` varchar(20) DEFAULT NULL,
  `ort` varchar(100) DEFAULT NULL,
  `bundesland` varchar(100) DEFAULT NULL,
  `passwort_hash` varchar(255) NOT NULL,
  `email_bestaetigt` tinyint(1) DEFAULT 0,
  `bestaetigungs_token` varchar(255) DEFAULT NULL,
  `token_ablauf` datetime DEFAULT NULL,
  `passwort_reset_token` varchar(255) DEFAULT NULL,
  `reset_token_ablauf` datetime DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(11,'Peter','Lustig','achimace@googlemail.com','01701240376','Abgleiter e.V.','Bauwagen 24','12345','Großbaustelle',NULL,'$2y$10$b6i2fVf0V/6Zk/BK3dXaHOhuFdTQ5/oBUsvBiVvoMtMboKqwGYgzK',1,NULL,NULL,NULL,NULL,'2025-08-11 10:26:33');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'sfw_gastgruppen'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-12 14:00:51
