-- MySQL dump 10.13  Distrib 5.7.26, for Linux (x86_64)
--
-- Host: localhost    Database: test-db
-- ------------------------------------------------------
-- Server version	5.7.26-0ubuntu0.18.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `test_table_1`
--

DROP TABLE IF EXISTS `test_table_1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_table_1` (
  `test_pk` int(11) NOT NULL AUTO_INCREMENT,
  `test_varchar` varchar(255) DEFAULT NULL,
  `test_text` text,
  `test_date` date DEFAULT NULL,
  `test_unique` varchar(1024) DEFAULT NULL,
  `test_decimal` decimal(10,2) DEFAULT NULL,
  `test_float` float DEFAULT NULL,
  `test_double` double DEFAULT NULL,
  `test_blob` blob,
  `test_bigint` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`test_pk`),
  UNIQUE KEY `test_unique_UNIQUE` (`test_unique`),
  KEY `test_index` (`test_varchar`),
  KEY `test_compound_index` (`test_varchar`,`test_double`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_table_1`
--

LOCK TABLES `test_table_1` WRITE;
/*!40000 ALTER TABLE `test_table_1` DISABLE KEYS */;
INSERT INTO `test_table_1` VALUES (1,'This is a test message foo bar \'hi\'','nothing here','2019-04-27','This needs to be unique',1.75,1.75,1.75,NULL,NULL),(2,'There is nothing relevant here','a:2:{s:6:\"wibble\";s:3:\"foo\";s:6:\"wobble\";s:3:\"bar\";}','2019-04-27','foo bar baz',40.00,40,40,NULL,NULL),(3,'Woop woop','This one has a\nnewline',NULL,'This is foo',NULL,NULL,NULL,NULL,NULL),(4,'Another foo test',NULL,NULL,'Wow, much lovely',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `test_table_1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_table_compound_pk`
--

DROP TABLE IF EXISTS `test_table_compound_pk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_table_compound_pk` (
  `column_1_pk` int(11) NOT NULL,
  `column_2_pk` int(11) NOT NULL,
  `column_data` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`column_1_pk`,`column_2_pk`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_table_compound_pk`
--

LOCK TABLES `test_table_compound_pk` WRITE;
/*!40000 ALTER TABLE `test_table_compound_pk` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_table_compound_pk` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-05-29 12:44:04