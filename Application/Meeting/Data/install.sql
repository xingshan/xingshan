DROP TABLE IF EXISTS `tc_meeting`;
CREATE TABLE IF NOT EXISTS `tc_meeting` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NOT NULL COMMENT '创建者',
  `name` varchar(100) NOT NULL COMMENT '会议的标题',
  `logo` varchar(255) DEFAULT '0' COMMENT '会议的logo图片',
  `content` text NOT NULL COMMENT '会议主题',
  `start` bigint(20) NOT NULL COMMENT '开始时间',
  `end` bigint(20) NOT NULL COMMENT '结束时间',
  `createtime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `tc_meeting_user`;
CREATE TABLE IF NOT EXISTS `tc_meeting_user`(
	`meetingid` bigint(20) not null,
	`uid` bigint(20) not null,
	`role` tinyint(1) not null default '0',
	`addtime` int(11) not null,
	`status` tinyint(1) not null default '0' comment '0-申请，1-已是成员',
	`content` varchar(255) not null default '' comment '申请理由',
	key (`meetingid`),
	key (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

