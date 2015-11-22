CREATE TABLE `search_history` (
  `shid` int(11) NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL,
  `ip` varchar(255) NOT NULL,
  `query` varchar(255) NOT NULL,
  `f_deleted` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`shid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;