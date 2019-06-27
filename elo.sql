
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Table structure for table `elo_attachment`
--
drop table `elo_attachment`;

CREATE TABLE `elo_attachment` (
  `attachment_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `attachment_filename` varchar(50) NOT NULL,
  `user_id` int(8) unsigned NOT NULL,
  `attachment_time` datetime,
  PRIMARY KEY (`attachment_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;



-- --------------------------------------------------------

--
-- Table structure for table `elo_config`
--

CREATE TABLE `elo_config` (
  `config_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `config_name` varchar(40) NOT NULL,
  `config_value` text NOT NULL,
  PRIMARY KEY (`config_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;

--
-- Dumping data for table `elo_config`
--

INSERT INTO `elo_config` (`config_id`, `config_name`, `config_value`) VALUES
(1, 'abc2ps', '/kunden/planetblacknwhite.de/software/abcm2ps/abcm2ps'),
(2, 'abc2midi', '/kunden/planetblacknwhite.de/software/abcmidi/abc2midi'),
(3, 'ps2pdf', '/usr/bin/ps2pdf'),
(4, 'abc2abc', '/kunden/planetblacknwhite.de/software/abcmidi/abc2abc'),
(5, 'convert', '/usr/bin/convert'),
(6, 'params4img', '-s 1 -w 600'),
(7, 'file_folder', 'files/'),
(8, 'params4ps', ''),
(9, 'date_format', 'd.m.Y, H:i'),
(10, 'params4png', '-density 150 -geometry 100%'),
(12, 'from_email', 'elo@christoph-pimpl.de'),
(13, 'from_name', 'Christoph Pimpl'),
(14, 'smtp_server', 'smtp.christoph-pimpl.de'),
(15, 'smtp_port', '25'),
(16, 'smtp_username', 'elo@christoph-pimpl.de'),
(17, 'smtp_password', 'rghqJ5S<jswe'),
(18, 'max_edit_time', '3600'),
(19, 'url', 'http://elo.christoph-pimpl.de/'),
(21, 'max_filesize', '2048'),
(22, 'min_length_topic_title', '5'),
(23, 'min_length_topic_text', '5');

-- --------------------------------------------------------

--
-- Table structure for table `elo_cron`
--

CREATE TABLE `elo_cron` (
  `cron_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `cron_time` int(13) unsigned NOT NULL,
  PRIMARY KEY (`cron_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2083 ;

-- --------------------------------------------------------

--
-- Table structure for table `elo_emailtext`
--

CREATE TABLE `elo_emailtext` (
  `emailtext_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `emailtext_text` text NOT NULL,
  `emailtext_key` varchar(50) NOT NULL,
  `lang_id` int(8) NOT NULL,
  PRIMARY KEY (`emailtext_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `elo_emailtext`
--


-- --------------------------------------------------------

--
-- Table structure for table `elo_group`
--

CREATE TABLE `elo_group` (
  `group_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(50) NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `elo_group`
--

INSERT INTO `elo_group` (`group_id`, `group_name`) VALUES
(1, 'Anonyme Alkoholiker'),
(2, 'Anonyme Gagger'),
(3, 'Gaggende Anonymiker'),
(4, 'Die Gebrüder Wurst'),
(5, 'Elo Kritik');

-- --------------------------------------------------------

--
-- Table structure for table `elo_group_user`
--

CREATE TABLE `elo_group_user` (
  `gu_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(8) unsigned NOT NULL,
  `user_id` int(8) unsigned NOT NULL,
  PRIMARY KEY (`gu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `elo_group_user`
--


-- --------------------------------------------------------

--
-- Table structure for table `elo_lang`
--

CREATE TABLE `elo_lang` (
  `lang_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `lang_name` varchar(25) NOT NULL,
  `lang_code` varchar(10) NOT NULL,
  PRIMARY KEY (`lang_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `elo_lang`
--

INSERT INTO `elo_lang` (`lang_id`, `lang_name`, `lang_code`) VALUES
(1, 'Deutsch', 'de'),
(2, 'English', 'en');

-- --------------------------------------------------------

--
-- Table structure for table `elo_music`
--

CREATE TABLE `elo_music` (
  `music_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `music_text` text NOT NULL,
  PRIMARY KEY (`music_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `elo_music`
--

INSERT INTO `elo_music` (`music_id`, `music_text`) VALUES
(1, 'X: 1\r\nT: Cooley''s\r\nM: 4/4\r\nL: 1/8\r\nR: reel\r\nK: Emin\r\n|:D2|EB{c}BA B2 EB|~B2 AB dBAG|FDAD BDAD|FDAD dAFD|\r\nEBBA B2 EB|B2 AB defg|afe^c dBAF|DEFD E2:|\r\n|:gf|eB B2 efge|eB B2 gedB|A2 FA DAFA|A2 FA defg|\r\neB B2 eBgB|eB B2 defg|afe^c dBAF|DEFD E2:|'),
(2, 'acde|fgab|'),
(3, 'gag gay fag'),
(4, 'X: 1\r\nT: Cooley''s\r\nM: 4/4\r\nL: 1/8\r\nR: reel\r\nK: Emin\r\n|FDAA BDAD|FDAD dAFD|'),
(5, 'X:1\r\nT:Alle meine Entchen\r\nM:2/4\r\nL:1/8\r\nK:C\r\nCDEF|G2G2|AAAA|G4|\r\nFFFF|E2E2|GGGG|C4|]');

-- --------------------------------------------------------

--
-- Table structure for table `elo_reply`
--

CREATE TABLE `elo_reply` (
  `reply_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(8) NOT NULL,
  `topic_id` int(8) NOT NULL,
  `reply_date` int(13) unsigned NOT NULL,
  `reply_text` text NOT NULL,
  PRIMARY KEY (`reply_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=25 ;

--
-- Dumping data for table `elo_reply`
--

INSERT INTO `elo_reply` (`reply_id`, `user_id`, `topic_id`, `reply_date`, `reply_text`) VALUES
(1, 1, 1, 1369061019, 'halloooo'),
(2, 1, 2, 1369515645, 'asdfasdf'),
(3, 1, 2, 1369515709, 'next day'),
(5, 2, 1, 1370739933, 'ziemlich cool, das man die Noten einfach so eingeben kann.'),
(7, 1, 2, 1373539796, 'test'),
(8, 1, 2, 1373539943, 'music'),
(9, 2, 4, 1374934237, ''),
(10, 2, 5, 1374934342, 'this is one!!!!'),
(11, 4, 4, 1374934586, 'Diese Gruppe seh ''ich auf der Haptseite nicht. Nur unter Admin panel. Aber ich bin doch ein fucking admin!\r\n\r\nKann man user permanent in eine Gruppe einteilen oder muss ich das bei einem neuen erstellten Topic immer neu zu ordnen?'),
(12, 2, 6, 1374934963, ''),
(13, 2, 7, 1374935225, 'Herzlich Willkommen zu der Gebrüder Wurst Mainbase! Hier findet ihr nützliche Sachen um eure Skills zu verbessern, yo! '),
(14, 2, 7, 1374936786, 'Video Game Soundtrack Multitracking\r\n\r\n[youtube]vn0nZW4W-6Y[/youtube]'),
(15, 2, 7, 1374936839, 'Winter is coming.\r\n\r\n[youtube]yj7YNeutcxM[/youtube]'),
(16, 2, 8, 1374937764, 'Vollbild Youtube Videos nicht möglich. Generell bei BB code nicht möglich?'),
(17, 2, 8, 1374937943, 'Topics löschen noch nicht möglich?'),
(18, 2, 8, 1374937974, 'User im Nachhinein zu Gruppen/Topics hinzufügen möglich?'),
(19, 2, 7, 1374938213, '20 Minuten Routine- Täglich üben!!'),
(20, 2, 7, 1374938262, 'Kurzzeit Routine - Wenn man mal nicht zu viel Zeit hat!'),
(21, 2, 7, 1374938562, 'Lied auswendig lernen und jeden Tag in einer anderen Tonart!!'),
(23, 1, 8, 1375170307, '- Youtube-Vollbild: Generell möglich, muss im Code noch etwas ändern. \r\n\r\n- Für Topics löschen müssen im Moment alle Antworten gelöscht werden.\r\n\r\n- User im Nachhinein hinzufügen geht unter Edit und dann am Ende der Seite.'),
(24, 1, 8, 1379627190, 'Vollbild geht jetzt');

-- --------------------------------------------------------

--
-- Table structure for table `elo_reply_attachment`
--

CREATE TABLE `elo_reply_attachment` (
  `ra_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `reply_id` int(8) unsigned NOT NULL,
  `attachment_id` int(8) unsigned NOT NULL,
  PRIMARY KEY (`ra_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

--
-- Dumping data for table `elo_reply_attachment`
--

INSERT INTO `elo_reply_attachment` (`ra_id`, `reply_id`, `attachment_id`) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 3),
(6, 7, 6),
(7, 9, 7),
(8, 10, 8),
(9, 19, 9),
(10, 20, 10);

-- --------------------------------------------------------

--
-- Table structure for table `elo_reply_music`
--

CREATE TABLE `elo_reply_music` (
  `rm_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `reply_id` int(8) unsigned NOT NULL,
  `music_id` int(8) unsigned NOT NULL,
  PRIMARY KEY (`rm_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `elo_reply_music`
--

INSERT INTO `elo_reply_music` (`rm_id`, `reply_id`, `music_id`) VALUES
(1, 1, 1),
(2, 2, 2),
(4, 8, 4),
(5, 21, 5);

-- --------------------------------------------------------

--
-- Table structure for table `elo_right`
--

CREATE TABLE `elo_right` (
  `right_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `right_name` varchar(50) NOT NULL,
  `right_key` varchar(20) NOT NULL,
  PRIMARY KEY (`right_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Dumping data for table `elo_right`
--

INSERT INTO `elo_right` (`right_id`, `right_name`, `right_key`) VALUES
(1, 'Create new users', 'CREATE_NEW_USER'),
(2, 'Create new rights', 'CREATE_NEW_RIGHT'),
(3, 'Attachments', 'CREATE_ATTACHMENTS'),
(4, 'Create topics', 'CREATE_TOPICS'),
(5, 'ABC music sheets', 'CREATE_SHEETS'),
(8, 'Add groups', 'CREATE_GROUPS'),
(9, 'Allow HTML', 'ALLOW_HTML'),
(10, 'Change own replys', 'CHANGE_OWN_REPLYS'),
(11, 'Delete own replys', 'DELETE_OWN_REPLYS'),
(12, 'Is Administrator', 'IS_ADMIN'),
(13, 'Can add users to topic', 'ADD_USER_TO_TOPIC'),
(14, 'Can add groups to topic', 'ADD_GROUP_TO_TOPIC'),
(15, 'Can reply to topics', 'CAN_REPLY');

-- --------------------------------------------------------

--
-- Table structure for table `elo_right_user`
--

CREATE TABLE `elo_right_user` (
  `ru_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(8) unsigned NOT NULL,
  `right_id` int(8) unsigned NOT NULL,
  PRIMARY KEY (`ru_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=25 ;

--
-- Dumping data for table `elo_right_user`
--

INSERT INTO `elo_right_user` (`ru_id`, `user_id`, `right_id`) VALUES
(6, 1, 5),
(8, 1, 3),
(11, 1, 2),
(13, 1, 4),
(14, 1, 9),
(15, 1, 12),
(16, 1, 1),
(17, 2, 5),
(18, 2, 8),
(19, 2, 9),
(20, 2, 3),
(21, 2, 10),
(22, 2, 1),
(23, 2, 4),
(24, 2, 12);

-- --------------------------------------------------------

--
-- Table structure for table `elo_topic`
--

CREATE TABLE `elo_topic` (
  `topic_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `topic_title` text NOT NULL,
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

--
-- Dumping data for table `elo_topic`
--

INSERT INTO `elo_topic` (`topic_id`, `topic_title`) VALUES
(1, 'Test für uns beide :)'),
(2, 'Next week'),
(3, 'Ein Gay auf der Suche nach einem Loch.'),
(4, 'Gagging around the world!'),
(5, 'wtf is anonymiker?!'),
(6, 'Django stole my bike!'),
(7, 'Die Gebrüder Wurst Main Base'),
(8, 'Fragen, Anregungen, Bugreport, etc.'),
(9, 'test');

-- --------------------------------------------------------

--
-- Table structure for table `elo_topic_group`
--

CREATE TABLE `elo_topic_group` (
  `tg_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(8) unsigned NOT NULL,
  `topic_id` int(8) unsigned NOT NULL,
  PRIMARY KEY (`tg_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `elo_topic_group`
--

INSERT INTO `elo_topic_group` (`tg_id`, `group_id`, `topic_id`) VALUES
(1, 2, 4),
(2, 3, 5),
(3, 3, 6),
(5, 4, 7);

-- --------------------------------------------------------

--
-- Table structure for table `elo_topic_user`
--

CREATE TABLE `elo_topic_user` (
  `tu_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(8) unsigned NOT NULL,
  `topic_id` int(8) unsigned NOT NULL,
  PRIMARY KEY (`tu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=29 ;

--
-- Dumping data for table `elo_topic_user`
--

INSERT INTO `elo_topic_user` (`tu_id`, `user_id`, `topic_id`) VALUES
(1, 1, 1),
(2, 2, 1),
(3, 1, 2),
(4, 2, 3),
(5, 5, 4),
(6, 4, 4),
(7, 1, 4),
(8, 1, 5),
(9, 3, 5),
(10, 2, 5),
(11, 3, 6),
(12, 1, 6),
(13, 4, 6),
(14, 2, 6),
(20, 2, 8),
(21, 1, 8),
(22, 1, 9),
(23, 1, 7),
(24, 9, 7),
(25, 2, 7),
(26, 8, 7),
(27, 6, 7),
(28, 7, 7);

-- --------------------------------------------------------


CREATE TABLE `elo_user_login` (
  `ul_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(6) unsigned NOT NULL,
  `user_login` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ul_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

--
-- Table structure for table `elo_user`
--

CREATE TABLE `elo_user` (
  `user_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `user_password` varchar(100) NOT NULL,
  `lang_id` int(8) NOT NULL,
  `user_picture` varchar(100) NOT NULL,
  `user_registration` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

--
-- Dumping data for table `elo_user`
--

INSERT INTO `elo_user` (`user_id`, `user_name`, `user_email`, `user_password`, `lang_id`, `user_lastvisit`) VALUES
(1, 'Jakob test', 'jw@envire.de', '$P$BsZgZ43VxjNPZ3V4p5eqskHa5PaDWw/', 2, 1380638758),
(2, 'Christoph Pimpl', 'ich@christoph-pimpl.de', '$P$BGVpXbUzntkaf.OzpqHKA/iwTc7mIi1', 0, 1375193388),
(3, 'Gag Test', 'sl1der@web.de', '$P$BuRfns/7niA2gs6wvToYwlbzHsujGJ.', 1, 1374933061),
(4, 'Soy Test', '', '$P$B18tLHZamAtMfjcsFfip64Ti1OB4MT/', 1, 1374934630),
(5, 'Homo Test', 'soy@homo.gag', '$P$B7Kl2QnTUwCGRDDfP70G0WWN8otcU90', 2, 1374933138),
(6, 'Hans Wurst', 'hanswurst@sausage.net', '$P$BOlf492ufabEqC7W1/eeIYKuF3QuNo1', 1, 1374935085),
(7, 'Peter Wurst', 'peterwurst@sausage.net', '$P$B0FecEMBqkvO8DysGUEs1Ezwm8xQyi0', 1, 1374935097),
(8, 'Georg Wurst', 'georgwurst@sausage.net', '$P$BccBzAzkXb5oCUKqC3f2kE.ONhOrDe1', 1, 1374935112),
(9, 'Bruns WUrst', 'brunswurst@sausage.net', '$P$BpN.dl0txp3hMvx9FWqMOjtCCArWM60', 1, 1374935127);
