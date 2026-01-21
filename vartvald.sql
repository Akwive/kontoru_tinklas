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
('adminas', '887d86c76cf2f9fb8882a9c00554eb74887d86c76cf2f9fb8882a9c00554eb74', 'ad1e8f5e9e8c5d5f8b8c5d5e8e9e3f3', 9, 'admin@test.lt', NOW()),
-- Specialist (level 5)
('specialistas', '994798271db17e46a4c0911f3a82c529', 'sp1e8f5e9e8c5d5f8b8c5d5e8e9e3f3', 5, 'specialistas@test.lt', NOW()),
-- Client (level 4)
('klientas', 'bdcd27a202eb1c5123576705cca90f832', 'cl1e8f5e9e8c5d5f8b8c5d5e8e9e3f3', 4, 'klientas@test.lt', NOW());

ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`);

