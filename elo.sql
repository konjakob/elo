
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

CREATE TABLE IF NOT EXISTS `elo_settinggroup` (
  `settinggroupid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` char(100) NOT NULL DEFAULT '',
  `displayorder` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`settinggroupid`)
) DEFAULT CHARSET=utf8;

INSERT INTO `elo_settinggroup` (`settinggroupid`, `title`, `displayorder`) VALUES
(1, 'Email', 2),
(2, 'Settings', 1),
(3, 'Validation', 2);

--
-- Table structure for table `elo_config`
--

CREATE TABLE IF NOT EXISTS `elo_config` (
  `settingid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `settinggroupid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `varname` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `value` mediumtext CHARACTER SET utf8 NOT NULL,
  `description` mediumtext CHARACTER SET utf8 NOT NULL,
  `optioncode` int(2) NOT NULL DEFAULT '0',
  `displayorder` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`settingid`)
)  DEFAULT CHARSET=utf8;

--
-- Dumping data for table `elo_config`
--

INSERT INTO `elo_config` (`settingid`, `settinggroupid`, `title`, `varname`, `value`, `description`) VALUES
(1, 2, 'Path to abc2ps', 'abc2ps', '/kunden/planetblacknwhite.de/software/abcm2ps/abcm2ps', 'To create the PDF from the note sheets, it is necessary to have the program abc2ps. It can be downloaded from <a href="http://moinejf.free.fr/" target="_blank">http://moinejf.free.fr/</a>. If the command is not globally available, please specify the exact path, e.g. /local/software/abcm2ps/abcm2ps.'),
(2, 2, 'Path to abc2midi', 'abc2midi', '/kunden/planetblacknwhite.de/software/abcmidi/abc2midi', 'To create the midi files from an abc file, the program abc2midi can be use. It can be downloaded from <a href="https://github.com/leesavide/abcmidi" target="_blank">https://github.com/leesavide/abcmidi</a>. If the command is not globally available, please specify the exact path, e.g. /local/software/abc2midi/abc2midi.'),
(3, 2, 'Path to ps2pdf', 'ps2pdf', '/usr/bin/ps2pdf', 'To convert PS files to PDF files, the program ps2pdf from the ghostscript can be used. If the command is not globally available, please specify the exact path, e.g. /local/software/ps2pdf/ps2pdf.'),
(4, 2, 'Path to abc2abc', 'abc2abc', '/kunden/planetblacknwhite.de/software/abcmidi/abc2abc', 'To check the syntax of the ABC files, the program abc2abc can be used.  It can be downloaded from <a href="https://github.com/leesavide/abcmidi" target="_blank">https://github.com/leesavide/abcmidi</a>. If the command is not globally available, please specify the exact path, e.g. /local/software/abc2abc/abc2abc.'),
(5, 2, 'Path to convert', 'convert', '"C:\\Program Files\\ImageMagick-7.0.8-Q16\\magick.exe" convert', ''),
(6, 2, 'Parameters for convert', 'params4img', '-s 1 -w 600', ''),
(7, 2, 'File folder', 'file_folder', 'files/', 'The folder where files are stored.'),
(8, 2, 'Parameters for ps', 'params4ps', '', ''),
(9, 2, 'Date format', 'date_format', 'd.m.Y, H:i', 'To format the dates, the formating available in PHP can be used. Please see <a href="https://www.php.net/manual/en/function.date.php" target="_blank">https://www.php.net/manual/en/function.date.php</a>.'),
(10, 2, 'Parameters for png', 'params4png', '-density 150 -geometry 100%', 'Parameters for generating the PNG files. For all options, please see <a href="https://imagemagick.org/script/command-line-options.php" target="_blank">https://imagemagick.org/script/command-line-options.php</a>.'),
(12, 1, 'From email', 'from_email', 'root@localhost', 'When sending emails out, following email will be set as from email.'),
(13, 1, 'From name', 'from_name', 'Jakob Wankel', 'When sending emails out, following name will be set as from name.'),
(14, 1, 'SMTP server', 'smtp_server', 'localhost', 'Address of the SMTP server for sending emails.'),
(15, 1, 'SMTP port', 'smtp_port', '25', 'Port of the smtp server for sending emails.'),
(16, 1, 'SMTP username', 'smtp_username', 'root@localhost', 'Username for the SMTP server for sending emails.'),
(17, 1, 'SMTP password', 'smtp_password', '', 'Password for the SMTP server for sending emails.'),
(18, 2, 'Maximum time for editing posts', 'max_edit_time', '3600', 'Maximum time a user can edit his post.'),
(19, 2, 'URL to page', 'url', 'http://localhost/elo/src/', ''),
(21, 2, 'Maximum file size', 'max_filesize', '2048', ''),
(22, 3, 'Minimum topic title length', 'min_length_topic_title', '5', ''),
(23, 3, 'Minimum topic/reply text length', 'min_length_topic_text', '5', ''),
(24, 3, 'Minimum user name length', 'min_length_username', '5', ''),
(25, 3, 'Date format for start and end date of topics', 'format_dates_topic', 'd.m.yy', 'To format the dates, the formating available in PHP can be used. Please see <a href="https://api.jqueryui.com/datepicker/#utility-formatDate" target="_blank">https://api.jqueryui.com/datepicker/#utility-formatDate</a>.');

update `elo_config` set displayorder=1;
-- --------------------------------------------------------

--
-- Table structure for table `elo_cron`
--

CREATE TABLE `elo_cron` (
  `cron_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `cron_time` int(13) unsigned NOT NULL,
  PRIMARY KEY (`cron_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
(1, 'Brass Band'),
(2, 'Bläser'),
(3, 'Kleines Orchester'),
(4, 'Phil. Orchester'),
(5, 'Test Gruppe');

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
(1, 'German', 'de_DE'),
(2, 'English', 'en_US'),
(3, 'Spanish', 'es_ES');

-- --------------------------------------------------------

--
-- Table structure for table `elo_music`
--

CREATE TABLE `elo_music` (
  `music_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `music_text` text NOT NULL,
  PRIMARY KEY (`music_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

----------------------------------------------------

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
(15, 'Can reply to topics', 'CAN_REPLY'),
(16, 'Can delete topics', 'CAN_DELETE_TOPICS'),
(17, 'Can modify users', 'CAN_MODIFY_USERS'),
(18, 'Can delete groups', 'CAN_DELETE_GROUPS'),
(19, 'Can add users to groups', 'CAN_ADD_USERS_TO_GROUPS'),
(20, 'Can remove rights from users', 'CAN_DELETE_USER_RIGHTS'),
(21, 'Can remove users from groups', 'CAN_DELETE_USER_FROM_GROUP');

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

-- --------------------------------------------------------

--
-- Table structure for table `elo_topic`
--

CREATE TABLE `elo_topic` (
  `topic_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `topic_title` text NOT NULL,
  `visible_from` datetime NOT NULL,
  `visible_till` datetime NOT NULL,
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

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



-- --------------------------------------------------------

CREATE TABLE `elo_pass_request` (
  `pr_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(6) unsigned NOT NULL,
  `pr_code` varchar(100) NOT NULL,
  `pr_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pr_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

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


