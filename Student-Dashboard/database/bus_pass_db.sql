-- Database: bus_pass_db
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `bus_pass_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bus_pass_db`;

-- --------------------------------------------------------
-- Table structure for table `students`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `college_name` varchar(255) NOT NULL,
  `college_id_number` varchar(100) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT 'default.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `college_id_number` (`college_id_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `routes`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `routes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `route_name` varchar(255) NOT NULL,
  `source` varchar(255) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `via_points` text DEFAULT NULL,
  `distance_km` decimal(10,2) DEFAULT NULL,
  `fare` decimal(10,2) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `routes`
-- --------------------------------------------------------
INSERT INTO `routes` (`id`, `route_name`, `source`, `destination`, `via_points`, `distance_km`, `fare`, `status`) VALUES
(1, 'Route 1: City Center - University', 'City Center', 'University Main Campus', 'Main Street, Gandhi Nagar, RTO Circle', 12.50, 1500.00, 'Active'),
(2, 'Route 2: Railway Station - University', 'Railway Station', 'University Main Campus', 'Bus Stand, Old Town, MG Road', 15.00, 1800.00, 'Active'),
(3, 'Route 3: East Colony - University', 'East Colony', 'University Main Campus', 'Lake View, Market Road, College Road', 18.50, 2000.00, 'Active'),
(4, 'Route 4: West Side - University', 'West Side Terminal', 'University Main Campus', 'Green Park, Hospital Road, Mall Road', 20.00, 2200.00, 'Active'),
(5, 'Route 5: North Gate - University', 'North Gate', 'University Main Campus', 'Temple Road, Library Square, Sports Complex', 10.00, 1300.00, 'Active'),
(6, 'Route 6: South Extension - University', 'South Extension', 'University Main Campus', 'Market Complex, Lake Road, Auditorium', 14.50, 1700.00, 'Active');

-- --------------------------------------------------------
-- Table structure for table `bus_passes`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bus_passes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_no` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `pass_type` enum('Daily','Monthly','Quarterly','Half Yearly','Yearly') NOT NULL DEFAULT 'Monthly',
  `college_id_doc` varchar(255) NOT NULL,
  `photo_doc` varchar(255) NOT NULL,
  `address_proof_doc` varchar(255) NOT NULL,
  `fee` decimal(10,2) NOT NULL,
  `payment_status` enum('Pending','Paid','Failed','Refunded') DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled','Expired') DEFAULT 'Pending',
  `admin_remark` text DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_no` (`application_no`),
  KEY `student_id` (`student_id`),
  KEY `route_id` (`route_id`),
  CONSTRAINT `bus_passes_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bus_passes_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pass_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_status` enum('Pending','Success','Failed') DEFAULT 'Pending',
  `payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pass_id` (`pass_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`pass_id`) REFERENCES `bus_passes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

