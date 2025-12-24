-- Cabs Management Module - Database Migration
-- Run this in phpMyAdmin or MySQL CLI
-- Created: 2024-12-04

-- --------------------------------------------------------
-- Table structure for table `cab_details`
-- --------------------------------------------------------

CREATE TABLE `cab_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `cab_number` varchar(50) NOT NULL COMMENT 'Vehicle registration number',
  `cab_name` varchar(100) DEFAULT NULL COMMENT 'Friendly name like Shuttle-1',
  `cab_type` enum('sedan','suv','van','bus','minibus') DEFAULT 'sedan',
  `capacity` int(11) NOT NULL DEFAULT 4,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_org_status` (`organization_id`, `status`),
  CONSTRAINT `fk_cab_org` FOREIGN KEY (`organization_id`) 
    REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `cab_schedule`
-- --------------------------------------------------------

CREATE TABLE `cab_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `cab_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'FK to registered.id',
  `schedule_date` date NOT NULL,
  `pickup_time` time NOT NULL,
  `trip_type` enum('pickup','drop','round_trip') DEFAULT 'pickup',
  `pickup_location` varchar(255) DEFAULT NULL,
  `drop_location` varchar(255) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `whatsapp_sent` tinyint(1) DEFAULT 0 COMMENT 'Flag for WhatsApp notification',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_org_date` (`organization_id`, `schedule_date`),
  KEY `idx_cab_date` (`cab_id`, `schedule_date`),
  UNIQUE KEY `unique_assignment` (`cab_id`, `user_id`, `schedule_date`, `trip_type`),
  CONSTRAINT `fk_sched_org` FOREIGN KEY (`organization_id`) 
    REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sched_cab` FOREIGN KEY (`cab_id`) 
    REFERENCES `cab_details` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sched_user` FOREIGN KEY (`user_id`) 
    REFERENCES `registered` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
