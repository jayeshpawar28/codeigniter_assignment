-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 16, 2026 at 10:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ci_assignment`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('employee','dealer') NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `user_type`, `city`, `state`, `zip`, `created_at`) VALUES
(1, 'Jayesh', 'Pawar', 'jayesh281103@gmail.com', '$2y$10$5EEDIPCMoJxqp1m73XuJm.tnrWAk6.qzV49Qf0PZB3Jz1jX/OxCsa', 'employee', NULL, NULL, NULL, '2026-04-16 23:59:28'),
(2, 'Pankaj', 'Deore', 'jayesh281103@gail.com', '$2y$10$undlmpxfNPPHGq1PdLSgMelk3/HmCRGJV6SCLFOxVpIYqdPWMEosq', 'dealer', 'Sinnar, Nashik', 'Maharashtra', '423204', '2026-04-17 00:01:18'),
(3, 'Bunty', 'Patil', 'bunty@gmail.com', '$2y$10$mQBccQJGpdNGQX.XjVgPROEx/EtE6RczO6iattZkbdmTiwME0wdWG', 'dealer', 'Pune', 'Maharashtra', '98765', '2026-04-17 00:14:54'),
(4, 'Jitesh', 'Pawar', 'jit@mail.com', '$2y$10$97zsKQE/wldNiE/A4/1tReeKn4FejWuEMPzD4aXHFm2gehoojyRVO', 'dealer', 'Raigad', 'Maharashtra', '423203', '2026-04-17 01:01:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
