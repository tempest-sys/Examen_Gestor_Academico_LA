CREATE DATABASE  IF NOT EXISTS `gestor_academico_la` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `gestor_academico_la`;
-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: gestor_academico_la
-- ------------------------------------------------------
-- Server version	8.4.8

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `tareas`
--

DROP TABLE IF EXISTS `tareas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tareas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(120) NOT NULL,
  `materia` varchar(100) NOT NULL,
  `descripcion` text,
  `fecha_entrega` date NOT NULL,
  `estado` enum('Pendiente','En proceso','Completada') NOT NULL DEFAULT 'Pendiente',
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tareas`
--

LOCK TABLES `tareas` WRITE;
/*!40000 ALTER TABLE `tareas` DISABLE KEYS */;
INSERT INTO `tareas` VALUES (1,'Estudiar HTML','Desarrollo Web','Repasar etiquetas HTML','2026-11-25','Pendiente','2026-11-22 01:08:54'),(2,'Practicar MySQL','Base de Datos','Realizar consultas SQL','2026-11-26','En proceso','2026-11-22 01:08:54'),(3,'Informe de minería','Minería de Datos','Preparar el informe final','2026-11-27','Completada','2026-11-22 01:08:54'),(5,'Configuracion de AWS','Servicios en la Nube','Desarrolla la actividad propuesta','2026-11-23','Pendiente','2026-11-22 01:36:45'),(6,'Conifguracion del servidor en AWS','Servicios en la Nube','Desarrolla la actividad propuesta','2026-11-23','Completada','2026-11-22 01:37:14'),(7,'P.A.E. - Práctica_01: La nube invisible, servicios que utilizamos todos los días','Servicios en  la Nube','Desarrolla la actividad Practica propuesta','2026-11-24','Completada','2026-11-22 01:58:43'),(9,'Prueba del sistema','Desarrollo Web','Verificar el registro y la edición','2026-11-25','Pendiente','2026-11-22 02:09:38'),(15,'Informe <A4> & prácticas','Desarrollo Web','Prueba de símbolos <A4> y &','2026-11-30','Pendiente','2026-11-23 23:23:41'),(16,'Prueba de estado válido','Desarrollo Web','Comprobación de regla de negocio','2026-11-30','Pendiente','2026-11-23 23:27:58');
/*!40000 ALTER TABLE `tareas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-23 19:03:08
