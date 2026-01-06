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
('Administratorius', '16c354b68848cdbd8f54a226a0a55b21', 'a2fe399900de341c39c632244eaf8483', 9, 'demo@ktu.lt', '2018-02-16 16:51:21'),
('rimas', 'c2acd92812ef99acd3dcdbb746b9a434', '689e5b2971577d707becb97405ede951', 9, 'rimas@litnet.lt', '2024-07-02 20:49:33'),
('jonas', '64067822105b320085d18e386f57d89a', '9c5ddd54107734f7d18335a5245c286b', 255, 'rimas@litnet.lt', '2017-05-09 17:10:37'),
('pranas', '16c354b68848cdbd8f54a226a0a55b21', 'aa69001f0ba493bed7dddfd1cdb06591', 4, 'pranas@ltu.ee', '2024-07-02 18:48:36'),
('kostas', '1c37511487d38c3ebc4c59650ce2d65a', '69986045e0925262d43addddaf76094f', 5, 'eeee@ll.lt', '2018-02-16 16:04:35'),
('stud', 'c2acd92812ef99acd3dcdbb746b9a434', 'de2cde3560412df8be3d2773e9f1a8b3', 4, 'stud@ktu.lt', '2024-07-03 11:42:12');

-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`);

