<?php
return array(
		/* 数据库设置 */
		'DB_TYPE'               => 'mysqli',		// 数据库类型
		'DB_HOST'               => '192.168.0.221',     // 服务器地址
		'DB_NAME'               => 'kuaiduijiang',     // 数据库名
		'DB_USER'               => 'admin',          // 用户名
		'DB_PWD'                => '123456',         // 密码
		'DB_PORT'               => '3306',          // 端口
		'DB_PREFIX'             => 'tc_',       	// 数据库表前缀
		//数据库配置1
		'DB_CONFIG1' => 'mysqli://admin:123456@192.168.0.221:3306/kuaiduijiang',
		//数据库配置2
		'DB_CONFIG2' => 'mysqli://root:123456@127.0.0.1:3306/openfire',
		
		/* 加载额外的配置文件 */
		'LOAD_EXT_CONFIG' 		=> 'module,push',
		
		/* Cookie设置 */
		'COOKIE_EXPIRE'         => 0,                 // Coodie有效期
		'COOKIE_DOMAIN'         => '',                // Cookie有效域名
		'COOKIE_PATH'           => '/',               // Cookie路径
		'COOKIE_PREFIX'         => '',                // Cookie前缀 避免冲突
		
		/* SESSION设置 */
		'SESSION_AUTO_START'    => true,              // 是否自动开启Session
		'SESSION_OPTIONS'       => array(),           // session 配置数组 支持type name id path expire domain 等参数
		'SESSION_TYPE'          => '',                // session hander类型 默认无需设置 除非扩展了session hander驱动
		'SESSION_PREFIX'        => '',                // session 前缀
		
		/* 模板解析设置 */
		'TMPL_PARSE_STRING'     => array(
				'__PUBLIC__' => __ROOT__ . '/Public',
		),
		
		/* URL设置 */
		'URL_CASE_INSENSITIVE'  => false,              // 默认false 表示URL区分大小写 true则表示不区分大小写
		
        'IOS_PUSH_RELEASE'      => true, //推送正式环境
);