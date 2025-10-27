-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2025 at 09:15 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clinic_booking_system`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateAppointmentID` (IN `appointment_date` DATE, OUT `new_appointment_id` VARCHAR(20))   BEGIN
    DECLARE year_part VARCHAR(4);
    DECLARE month_part VARCHAR(2);
    DECLARE sequence_number INT;
    DECLARE sequence_string VARCHAR(7);
    
    SET year_part = YEAR(appointment_date);
    SET month_part = LPAD(MONTH(appointment_date), 2, '0');
    
    -- Get the next sequence number for this year-month combination
    SELECT COALESCE(MAX(CAST(SUBSTRING(APPT_ID, 10) AS UNSIGNED)), 0) + 1 
    INTO sequence_number
    FROM APPOINTMENT 
    WHERE APPT_ID LIKE CONCAT(year_part, '-', month_part, '-%');
    
    SET sequence_string = LPAD(sequence_number, 7, '0');
    SET new_appointment_id = CONCAT(year_part, '-', month_part, '-', sequence_string);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `APPT_ID` varchar(20) NOT NULL,
  `APPT_DATE` date NOT NULL,
  `APPT_TIME` time NOT NULL,
  `APPT_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `APPT_UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `PAT_ID` int(11) NOT NULL,
  `DOC_ID` int(11) NOT NULL,
  `SERV_ID` int(11) NOT NULL,
  `STAT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `DOC_ID` int(11) NOT NULL,
  `DOC_FIRST_NAME` varchar(50) NOT NULL,
  `DOC_LAST_NAME` varchar(50) NOT NULL,
  `DOC_MIDDLE_NAME` varchar(50) DEFAULT NULL,
  `DOC_CONTACT_NUM` varchar(20) NOT NULL,
  `DOC_EMAIL` varchar(100) NOT NULL,
  `DOC_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `DOC_UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `SPEC_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `doctor_appointments_view`
-- (See below for the actual view)
--
CREATE TABLE `doctor_appointments_view` (
`APPT_ID` varchar(20)
,`APPT_DATE` date
,`APPT_TIME` time
,`PAT_FIRST_NAME` varchar(50)
,`PAT_LAST_NAME` varchar(50)
,`SERV_NAME` varchar(100)
,`STAT_NAME` varchar(50)
,`DOC_FIRST_NAME` varchar(50)
,`DOC_LAST_NAME` varchar(50)
,`SPECIALIZATION` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `medical_record`
--

CREATE TABLE `medical_record` (
  `MED_REC_ID` int(11) NOT NULL,
  `MED_REC_DIAGNOSIS` text DEFAULT NULL,
  `MED_REC_PRESCRIPTION` text DEFAULT NULL,
  `MED_REC_VISIT_DATE` date NOT NULL,
  `MED_REC_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `MED_REC_UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `APPT_ID` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `PAT_ID` int(11) NOT NULL,
  `PAT_FIRST_NAME` varchar(50) NOT NULL,
  `PAT_MIDDLE_NAME` varchar(50) DEFAULT NULL,
  `PAT_LAST_NAME` varchar(50) NOT NULL,
  `PAT_DOB` date DEFAULT NULL,
  `PAT_GENDER` enum('Male','Female','Other') DEFAULT NULL,
  `PAT_CONTACT_NUM` varchar(20) NOT NULL,
  `PAT_EMAIL` varchar(100) NOT NULL,
  `PAT_ADDRESS` text DEFAULT NULL,
  `PAT_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `PAT_UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `patient_appointments_view`
-- (See below for the actual view)
--
CREATE TABLE `patient_appointments_view` (
`APPT_ID` varchar(20)
,`APPT_DATE` date
,`APPT_TIME` time
,`DOC_FIRST_NAME` varchar(50)
,`DOC_LAST_NAME` varchar(50)
,`DOCTOR_SPECIALIZATION` varchar(100)
,`SERV_NAME` varchar(100)
,`STAT_NAME` varchar(50)
,`PAYMENT_METHOD` varchar(50)
,`PAYMENT_STATUS` varchar(50)
,`PAYMENT_AMOUNT` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PAYMENT_ID` int(11) NOT NULL,
  `PAYMENT_AMOUNT` decimal(10,2) NOT NULL,
  `PAYMENT_DATE` timestamp NOT NULL DEFAULT current_timestamp(),
  `PAYMENT_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `PAYMENT_UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `APPT_ID` varchar(20) NOT NULL,
  `PYMT_METH_ID` int(11) NOT NULL,
  `PYMT_STAT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_method`
--

CREATE TABLE `payment_method` (
  `PYMT_METH_ID` int(11) NOT NULL,
  `PYMT_METH_NAME` varchar(50) NOT NULL,
  `PYMT_METH_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `PYMT_METH_UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_method`
--

INSERT INTO `payment_method` (`PYMT_METH_ID`, `PYMT_METH_NAME`, `PYMT_METH_CREATED_AT`, `PYMT_METH_UPDATED_AT`) VALUES
(1, 'Cash', '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(2, 'Debit Card', '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(3, 'Credit Card', '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(4, 'Bank Transfer', '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(5, 'Mobile Payment', '2025-10-27 03:09:24', '2025-10-27 03:09:24');

-- --------------------------------------------------------

--
-- Table structure for table `payment_status`
--

CREATE TABLE `payment_status` (
  `PYMT_STAT_ID` int(11) NOT NULL,
  `PYMT_STAT_NAME` varchar(50) NOT NULL,
  `PYMT_STAT_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `PYMT_STAT_UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_status`
--

INSERT INTO `payment_status` (`PYMT_STAT_ID`, `PYMT_STAT_NAME`, `PYMT_STAT_CREATED_AT`, `PYMT_STAT_UPDATED_AT`) VALUES
(1, 'Paid', '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(2, 'Pending', '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(3, 'Refunded', '2025-10-27 03:09:24', '2025-10-27 03:09:24');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `SCHED_ID` int(11) NOT NULL,
  `SCHED_DAYS` varchar(50) NOT NULL,
  `SCHED_START_TIME` time NOT NULL,
  `SCHED_END_TIME` time NOT NULL,
  `SCHED_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `SCHED_UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `DOC_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `SERV_ID` int(11) NOT NULL,
  `SERV_NAME` varchar(100) NOT NULL,
  `SERV_DESCRIPTION` text DEFAULT NULL,
  `SERV_PRICE` decimal(10,2) DEFAULT NULL,
  `SERV_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `SERV_UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`SERV_ID`, `SERV_NAME`, `SERV_DESCRIPTION`, `SERV_PRICE`, `SERV_CREATED_AT`, `SERV_UPDATED_AT`) VALUES
(1, 'Consultation', 'General medical consultation', 500.00, '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(2, 'Laboratory Test', 'Basic laboratory tests and analysis', 1200.00, '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(3, 'Vaccination', 'Vaccine administration', 800.00, '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(4, 'Health Check-up', 'Comprehensive health examination', 2500.00, '2025-10-27 03:09:24', '2025-10-27 03:09:24');

-- --------------------------------------------------------

--
-- Table structure for table `specialization`
--

CREATE TABLE `specialization` (
  `SPEC_ID` int(11) NOT NULL,
  `SPEC_NAME` varchar(100) NOT NULL,
  `SPEC_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `SPEC_UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specialization`
--

INSERT INTO `specialization` (`SPEC_ID`, `SPEC_NAME`, `SPEC_CREATED_AT`, `SPEC_UPDATED_AT`) VALUES
(1, 'Family Medicine', '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(2, 'Internal Medicine', '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(3, 'Pediatrics', '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(4, 'Cardiology', '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(5, 'Dermatology', '2025-10-27 03:09:24', '2025-10-27 03:09:24');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `STAFF_ID` int(11) NOT NULL,
  `STAFF_FIRST_NAME` varchar(50) NOT NULL,
  `STAFF_LAST_NAME` varchar(50) NOT NULL,
  `STAFF_EMAIL` varchar(100) NOT NULL,
  `STAFF_POSITION` varchar(50) DEFAULT NULL,
  `STAFF_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `STAFF_UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `STAT_ID` int(11) NOT NULL,
  `STAT_NAME` varchar(50) NOT NULL,
  `STAT_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `STAT_UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`STAT_ID`, `STAT_NAME`, `STAT_CREATED_AT`, `STAT_UPDATED_AT`) VALUES
(1, 'Scheduled', '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(2, 'Completed', '2025-10-27 03:09:24', '2025-10-27 03:09:24'),
(3, 'Cancelled', '2025-10-27 03:09:24', '2025-10-27 03:09:24');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `USER_ID` int(11) NOT NULL,
  `USER_NAME` varchar(100) NOT NULL,
  `USER_PASSWORD` varchar(255) NOT NULL,
  `USER_IS_SUPERADMIN` tinyint(1) DEFAULT 0,
  `USER_LAST_LOGIN` timestamp NULL DEFAULT NULL,
  `USER_CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `PAT_ID` int(11) DEFAULT NULL,
  `STAFF_ID` int(11) DEFAULT NULL,
  `DOC_ID` int(11) DEFAULT NULL
) ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`USER_ID`, `USER_NAME`, `USER_PASSWORD`, `USER_IS_SUPERADMIN`, `USER_LAST_LOGIN`, `USER_CREATED_AT`, `PAT_ID`, `STAFF_ID`, `DOC_ID`) VALUES
(1, 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NULL, '2025-10-27 03:09:24', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure for view `doctor_appointments_view`
--
DROP TABLE IF EXISTS `doctor_appointments_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `doctor_appointments_view`  AS SELECT `a`.`APPT_ID` AS `APPT_ID`, `a`.`APPT_DATE` AS `APPT_DATE`, `a`.`APPT_TIME` AS `APPT_TIME`, `p`.`PAT_FIRST_NAME` AS `PAT_FIRST_NAME`, `p`.`PAT_LAST_NAME` AS `PAT_LAST_NAME`, `s`.`SERV_NAME` AS `SERV_NAME`, `st`.`STAT_NAME` AS `STAT_NAME`, `d`.`DOC_FIRST_NAME` AS `DOC_FIRST_NAME`, `d`.`DOC_LAST_NAME` AS `DOC_LAST_NAME`, `spec`.`SPEC_NAME` AS `SPECIALIZATION` FROM (((((`appointment` `a` join `patient` `p` on(`a`.`PAT_ID` = `p`.`PAT_ID`)) join `service` `s` on(`a`.`SERV_ID` = `s`.`SERV_ID`)) join `status` `st` on(`a`.`STAT_ID` = `st`.`STAT_ID`)) join `doctor` `d` on(`a`.`DOC_ID` = `d`.`DOC_ID`)) left join `specialization` `spec` on(`d`.`SPEC_ID` = `spec`.`SPEC_ID`)) ;

-- --------------------------------------------------------

--
-- Structure for view `patient_appointments_view`
--
DROP TABLE IF EXISTS `patient_appointments_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `patient_appointments_view`  AS SELECT `a`.`APPT_ID` AS `APPT_ID`, `a`.`APPT_DATE` AS `APPT_DATE`, `a`.`APPT_TIME` AS `APPT_TIME`, `d`.`DOC_FIRST_NAME` AS `DOC_FIRST_NAME`, `d`.`DOC_LAST_NAME` AS `DOC_LAST_NAME`, `spec`.`SPEC_NAME` AS `DOCTOR_SPECIALIZATION`, `s`.`SERV_NAME` AS `SERV_NAME`, `st`.`STAT_NAME` AS `STAT_NAME`, `pm`.`PYMT_METH_NAME` AS `PAYMENT_METHOD`, `ps`.`PYMT_STAT_NAME` AS `PAYMENT_STATUS`, `pay`.`PAYMENT_AMOUNT` AS `PAYMENT_AMOUNT` FROM (((((((`appointment` `a` join `doctor` `d` on(`a`.`DOC_ID` = `d`.`DOC_ID`)) left join `specialization` `spec` on(`d`.`SPEC_ID` = `spec`.`SPEC_ID`)) join `service` `s` on(`a`.`SERV_ID` = `s`.`SERV_ID`)) join `status` `st` on(`a`.`STAT_ID` = `st`.`STAT_ID`)) left join `payment` `pay` on(`a`.`APPT_ID` = `pay`.`APPT_ID`)) left join `payment_method` `pm` on(`pay`.`PYMT_METH_ID` = `pm`.`PYMT_METH_ID`)) left join `payment_status` `ps` on(`pay`.`PYMT_STAT_ID` = `ps`.`PYMT_STAT_ID`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`APPT_ID`),
  ADD KEY `SERV_ID` (`SERV_ID`),
  ADD KEY `idx_appointment_patient` (`PAT_ID`),
  ADD KEY `idx_appointment_doctor` (`DOC_ID`),
  ADD KEY `idx_appointment_date` (`APPT_DATE`),
  ADD KEY `idx_appointment_status` (`STAT_ID`);

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`DOC_ID`),
  ADD UNIQUE KEY `DOC_CONTACT_NUM` (`DOC_CONTACT_NUM`),
  ADD UNIQUE KEY `DOC_EMAIL` (`DOC_EMAIL`),
  ADD KEY `SPEC_ID` (`SPEC_ID`);

--
-- Indexes for table `medical_record`
--
ALTER TABLE `medical_record`
  ADD PRIMARY KEY (`MED_REC_ID`),
  ADD KEY `idx_medical_record_appointment` (`APPT_ID`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`PAT_ID`),
  ADD UNIQUE KEY `PAT_CONTACT_NUM` (`PAT_CONTACT_NUM`),
  ADD UNIQUE KEY `PAT_EMAIL` (`PAT_EMAIL`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PAYMENT_ID`),
  ADD KEY `APPT_ID` (`APPT_ID`),
  ADD KEY `PYMT_METH_ID` (`PYMT_METH_ID`),
  ADD KEY `PYMT_STAT_ID` (`PYMT_STAT_ID`);

--
-- Indexes for table `payment_method`
--
ALTER TABLE `payment_method`
  ADD PRIMARY KEY (`PYMT_METH_ID`),
  ADD UNIQUE KEY `PYMT_METH_NAME` (`PYMT_METH_NAME`);

--
-- Indexes for table `payment_status`
--
ALTER TABLE `payment_status`
  ADD PRIMARY KEY (`PYMT_STAT_ID`),
  ADD UNIQUE KEY `PYMT_STAT_NAME` (`PYMT_STAT_NAME`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`SCHED_ID`),
  ADD KEY `idx_schedule_doctor` (`DOC_ID`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`SERV_ID`);

--
-- Indexes for table `specialization`
--
ALTER TABLE `specialization`
  ADD PRIMARY KEY (`SPEC_ID`),
  ADD UNIQUE KEY `SPEC_NAME` (`SPEC_NAME`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`STAFF_ID`),
  ADD UNIQUE KEY `STAFF_EMAIL` (`STAFF_EMAIL`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`STAT_ID`),
  ADD UNIQUE KEY `STAT_NAME` (`STAT_NAME`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`USER_ID`),
  ADD KEY `STAFF_ID` (`STAFF_ID`),
  ADD KEY `DOC_ID` (`DOC_ID`),
  ADD KEY `idx_user_roles` (`PAT_ID`,`STAFF_ID`,`DOC_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `DOC_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_record`
--
ALTER TABLE `medical_record`
  MODIFY `MED_REC_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `PAT_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PAYMENT_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_method`
--
ALTER TABLE `payment_method`
  MODIFY `PYMT_METH_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payment_status`
--
ALTER TABLE `payment_status`
  MODIFY `PYMT_STAT_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `SCHED_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service`
--
ALTER TABLE `service`
  MODIFY `SERV_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `specialization`
--
ALTER TABLE `specialization`
  MODIFY `SPEC_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `STAFF_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `STAT_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `USER_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `appointment_ibfk_1` FOREIGN KEY (`PAT_ID`) REFERENCES `patient` (`PAT_ID`),
  ADD CONSTRAINT `appointment_ibfk_2` FOREIGN KEY (`DOC_ID`) REFERENCES `doctor` (`DOC_ID`),
  ADD CONSTRAINT `appointment_ibfk_3` FOREIGN KEY (`SERV_ID`) REFERENCES `service` (`SERV_ID`),
  ADD CONSTRAINT `appointment_ibfk_4` FOREIGN KEY (`STAT_ID`) REFERENCES `status` (`STAT_ID`);

--
-- Constraints for table `doctor`
--
ALTER TABLE `doctor`
  ADD CONSTRAINT `doctor_ibfk_1` FOREIGN KEY (`SPEC_ID`) REFERENCES `specialization` (`SPEC_ID`);

--
-- Constraints for table `medical_record`
--
ALTER TABLE `medical_record`
  ADD CONSTRAINT `medical_record_ibfk_1` FOREIGN KEY (`APPT_ID`) REFERENCES `appointment` (`APPT_ID`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`APPT_ID`) REFERENCES `appointment` (`APPT_ID`),
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`PYMT_METH_ID`) REFERENCES `payment_method` (`PYMT_METH_ID`),
  ADD CONSTRAINT `payment_ibfk_3` FOREIGN KEY (`PYMT_STAT_ID`) REFERENCES `payment_status` (`PYMT_STAT_ID`);

--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`DOC_ID`) REFERENCES `doctor` (`DOC_ID`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`PAT_ID`) REFERENCES `patient` (`PAT_ID`),
  ADD CONSTRAINT `user_ibfk_2` FOREIGN KEY (`STAFF_ID`) REFERENCES `staff` (`STAFF_ID`),
  ADD CONSTRAINT `user_ibfk_3` FOREIGN KEY (`DOC_ID`) REFERENCES `doctor` (`DOC_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
