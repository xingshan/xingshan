--
-- 消息列表 
-- 我发的分享被好友赞或者评论过 就要显示出来
-- 好友发过的分享，我赞过的---然后另外的用户（是指我的好友）也来赞这条分享 也要显示
-- 是本地保存，我只是发通知出去
--

--
--朋友圈表 //经纬度 名称 公开-所有人可见 私密-选固定的某些人
--
DROP TABLE IF EXISTS `tc_friend`;
CREATE TABLE IF NOT EXISTS `tc_friend` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `content` varchar(512) NOT NULL DEFAULT '',
  `picture` varchar(1576) NOT NULL DEFAULT '',
  `lng` varchar(10) NOT NULL DEFAULT '' COMMENT '经度',
  `lat` varchar(10) NOT NULL DEFAULT '' COMMENT '纬度',
  `address` varchar(50) NOT NULL DEFAULT '' COMMENT '地区名',
  `visible` text NOT NULL DEFAULT '' COMMENT '可见范围 空-所有人可见 (userid1, userid2)-指定用户12可见',
  `createtime` int(11) NOT NULL,
  KEY (`uid`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT '朋友圈';
--
--用户最新三张图片表
--
DROP TABLE IF EXISTS `tc_friend_user`;
CREATE TABLE IF NOT EXISTS `tc_friend_user` (
	`uid` int(11) NOT NULL,
	`picture1` varchar(100) NOT NULL DEFAULT '',
	`picture2` varchar(100) NOT NULL DEFAULT '',
	`picture3` varchar(100) NOT NULL DEFAULT '',
	`cover` varchar(100) NOT NULL DEFAULT '' COMMENT '相册封面',
	`createtime` int(11) NOT NULL,
	KEY (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '记录用户发布朋友圈最新的三张';
--
--朋友圈评论，回复表
--
DROP TABLE IF EXISTS `tc_friend_reply`;
CREATE TABLE IF NOT EXISTS `tc_friend_reply` (
	`id` bigint(11) NOT NULL AUTO_INCREMENT,
	`fsid` bigint(11) NOT NULL COMMENT '分享记录id',
	`uid` int(11) NOT NULL COMMENT '回复人',
	`fid` int(11) NOT NULL COMMENT '收到回复人',
	`content` varchar(100) NOT NULL DEFAULT '',
	`createtime` int(11) NOT NULL,
	KEY (`fsid`),
	KEY (`uid`),
	PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT '朋友圈评论，回复表';
--
--朋友圈赞表
--
DROP TABLE IF EXISTS `tc_friend_praise`;
CREATE TABLE IF NOT EXISTS `tc_friend_praise` (
	`uid` int(11) NOT NULL,
	`fsid` bigint(11) NOT NULL COMMENT '分享记录id',
	`createtime` int(11) NOT NULL,
	KEY (`uid`),
	KEY (`fsid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '朋友圈赞表';