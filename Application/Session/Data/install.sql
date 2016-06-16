DROP TABLE IF EXISTS `tc_session`;
CREATE TABLE IF NOT EXISTS `tc_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `createtime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `tc_session_user`;
CREATE TABLE IF NOT EXISTS `tc_session_user` (
  `sessionid` int(11) unsigned NOT NULL DEFAULT '0',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `role` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-成员 1-创建者',
  `getmsg` tinyint(1) DEFAULT '1' COMMENT '0-不接收 1-接收',
  `nickname` varchar(20) NOT NULL DEFAULT '' COMMENT '群昵称',
  `addtime` int(11) NOT NULL DEFAULT '0',
  KEY `sessionid` (`sessionid`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;