-- =====================================================
-- Royal Farming House Database Schema (Simplified)
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS `royal_farming_house` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `royal_farming_house`;

-- =====================================================
-- Bookings Table
-- =====================================================
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `visitor_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `date` DATE NOT NULL,
  `time_slot` VARCHAR(20) NOT NULL,
  `participants` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_booking` (`date`, `time_slot`),
  INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Contacts Table
-- =====================================================
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- End of Schema
-- =====================================================