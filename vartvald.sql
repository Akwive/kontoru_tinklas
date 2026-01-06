--
-- Database: `vartvald`
--
-- Table structure for table `users`
--
CREATE TABLE `users` (
  `username` varchar(30) NOT NULL,
  `password` varchar(32) DEFAULT NULL,
  `userid` varchar(32) NOT NULL,
  `userlevel` tinyint(1) UNSIGNED DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `users` (`username`, `password`, `userid`, `userlevel`, `email`, `timestamp`) VALUES
-- Administrator (level 9)
('adminas', 'f8b8c5d5e8e9e3f3b5c8a8f5e9e8', 'ad1e8f5e9e8c5d5f8b8c5d5e8e9e3f3', 9, 'admin@test.lt', NOW()),
-- Specialist (level 5)
('specialistas', '5c8f5e9e8c5d5f8b8c5d5e8e9e3f3b', 'sp1e8f5e9e8c5d5f8b8c5d5e8e9e3f3', 5, 'specialistas@test.lt', NOW()),
-- Client (level 4)
('klientas', 'b8c5d5e8e9e3f3b5c8f5e9e8c5d5f8', 'cl1e8f5e9e8c5d5f8b8c5d5e8e9e3f3', 4, 'klientas@test.lt', NOW());

ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`);

