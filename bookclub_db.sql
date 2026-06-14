-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 14, 2026 at 06:34 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bookclub_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` date NOT NULL,
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_at`, `created_by`) VALUES
(1, 'June Book Club Selection Revealed! 📖', 'We are officially diving into the psychological mystery of **The Silent Patient** by Alex Michaelides for the month of June. Grab your copies and start reading! Our first virtual review session is scheduled for Friday, June 26th at 7:00 PM EST.', '2026-06-02', 1),
(2, 'Author Q&A and Coffee Morning ☕', 'Excited to announce we have a virtual meet-and-greet session set up next Saturday. Standard video details will be posted shortly. Bring your favorite beverage and your burning questions about world-building and narrative design!', '2026-06-08', 1),
(3, 'Reading Progress Trackers Now Active! ✨', 'We have updated our BookClub Portal! You can now catalog your own additions, rate books you have completed, keep comprehensive notes of your chapters, and instantly see dynamic club-wide statistics. Happy reading, everyone!', '2026-06-13', 2);

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `genre` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `cover_url` varchar(500) DEFAULT NULL,
  `published_year` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `genre`, `description`, `cover_url`, `published_year`, `created_by`, `created_at`) VALUES
(1, 'The Midnight Library', 'Matt Haig', 'Fantasy', 'Between life and death there is a library, and within that library, the shelves go on forever. Every book provides a chance to try another life you could have lived.', 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&q=80&w=400', 2020, 1, '2026-06-14 16:25:41'),
(2, 'Dune', 'Frank Herbert', 'Science Fiction', 'A mythic and emotionally charged hero’s journey, Dune tells the story of Paul Atreides, a brilliant and gifted young man born into a great destiny beyond his understanding.', 'https://images.unsplash.com/photo-1447069387593-a5de0862481e?auto=format&fit=crop&q=80&w=400', 1965, 1, '2026-06-14 16:25:41'),
(3, 'Educated', 'Tara Westover', 'Biography', 'An unforgettable memoir about a young girl who, kept out of school, leaves her survivalist family and goes on to earn a PhD from Cambridge University.', 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?auto=format&fit=crop&q=80&w=400', 2018, 2, '2026-06-14 16:25:41'),
(4, 'Project Hail Mary', 'Andy Weir', 'Science Fiction', 'Ryland Grace is the sole survivor on a desperate, last-chance mission to save humanity and the earth itself. Except that right now, he doesn’t even know his own name.', 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&q=80&w=400', 2021, 3, '2026-06-14 16:25:41'),
(5, 'The Silent Patient', 'Alex Michaelides', 'Mystery', 'Alicia Berenson’s life is seemingly perfect. A famous painter married to an in-demand fashion photographer, she lives in a grand house in one of London’s most desirable areas.', 'https://images.unsplash.com/photo-1516979187457-637abb4f9353?auto=format&fit=crop&q=80&w=400', 2019, 4, '2026-06-14 16:25:41'),
(6, 'Atomic Habits', 'James Clear', 'Self-Help', 'No matter your goals, Atomic Habits offers a proven framework for improving—every day. James Clear, one of the world’s leading experts on habit formation, reveals practical strategies.', 'https://images.unsplash.com/photo-1531988042231-d39a9cc12a9a?auto=format&fit=crop&q=80&w=400', 2018, 1, '2026-06-14 16:25:41');

-- --------------------------------------------------------

--
-- Table structure for table `reading_list`
--

CREATE TABLE `reading_list` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reading_list`
--

INSERT INTO `reading_list` (`id`, `user_id`, `book_id`, `status`, `rating`, `notes`, `updated_at`) VALUES
(1, 4, 1, 'Completed', 5, 'Absolutely magical. Cried at the chapter about her sibling.', '2026-04-20'),
(2, 4, 5, 'Reading', NULL, 'So tense. Can not trust any of these characters!', '2026-05-15'),
(3, 4, 3, 'Completed', 4, 'Inspiring journey, written with incredible emotion.', '2026-05-01'),
(4, 2, 3, 'Completed', 5, 'One of my favorite essays about truth and learning.', '2026-03-10'),
(5, 2, 6, 'Completed', 4, 'Practical tips on stacking small changes in the morning.', '2026-04-12'),
(6, 2, 2, 'Want to Read', NULL, 'Long sci-fi epic I need to get through this summer.', '2026-05-20'),
(7, 3, 4, 'Completed', 5, 'Unbelievably good sci-fi survival. Science is detailed but extremely fun.', '2026-04-01'),
(8, 3, 2, 'Completed', 5, 'One of the greatest planetary ecological tales ever penned.', '2026-03-25'),
(9, 3, 6, 'Reading', NULL, 'Starting the 1% compound rules.', '2026-06-01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'member',
  `joined_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `joined_at`) VALUES
(1, 'admin@rabbi.com', '$2y$10$yCqqshSeIO1Hq7mrRumS4equ.bdm5fhgR7wSet5brTF8V1eOKmn0a', 'admin', '2026-01-15'),
(2, 'mahidul@mail.com', '$2y$10$yCqqshSeIO1Hq7mrRumS4equ.bdm5fhgR7wSet5brTF8V1eOKmn0a', 'member', '2026-02-18'),
(3, 'islam@mail.com', '$2y$10$yCqqshSeIO1Hq7mrRumS4equ.bdm5fhgR7wSet5brTF8V1eOKmn0a', 'member', '2026-03-05'),
(4, 'rabbi@seu.edu.bd', '$2y$10$yCqqshSeIO1Hq7mrRumS4equ.bdm5fhgR7wSet5brTF8V1eOKmn0a', 'member', '2026-04-12'),
(5, '2022100010020@gmail.com', '$2y$10$yCqqshSeIO1Hq7mrRumS4equ.bdm5fhgR7wSet5brTF8V1eOKmn0a', 'member', '2026-06-14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `reading_list`
--
ALTER TABLE `reading_list`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_book_unique` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reading_list`
--
ALTER TABLE `reading_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reading_list`
--
ALTER TABLE `reading_list`
  ADD CONSTRAINT `reading_list_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reading_list_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
