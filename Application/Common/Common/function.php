<?php
use Org\Util\String;
use Think\Image;
use Think\Upload;
/**
 * 删除文件目录
 * 
 * @param unknown $dir        	
 * @return boolean
 */
function deldir($dir) {
	if (file_exists ( $dir )) {
		// 先删除目录下的文件：
		$dh = opendir ( $dir );
		while ( $file = readdir ( $dh ) ) {
			if ($file != "." && $file != "..") {
				$fullpath = $dir . "/" . $file;
				if (! is_dir ( $fullpath )) {
					unlink ( $fullpath );
				} else {
					deldir ( $fullpath );
				}
			}
		}
		closedir ( $dh );
		// 删除当前文件夹：
		if (rmdir ( $dir )) {
			return true;
		} else {
			return false;
		}
	}
}
/**
 * 编辑排序
 * @param unknown $a
 * @param unknown $b
 * @return number
 */
function cmp_func($a, $b) {
	global $order;
	if ($a['is_dir'] && !$b['is_dir']) {
		return -1;
	} else if (!$a['is_dir'] && $b['is_dir']) {
		return 1;
	} else {
		if ($order == 'size') {
			if ($a['filesize'] > $b['filesize']) {
				return 1;
			} else if ($a['filesize'] < $b['filesize']) {
				return -1;
			} else {
				return 0;
			}
		} else if ($order == 'type') {
			return strcmp($a['filetype'], $b['filetype']);
		} else {
			return strcmp($a['filename'], $b['filename']);
		}
	}
}
/**
 * 对用户的密码进行加密
 * @param $password
 * @param $encrypt //传入加密串，在修改密码时做认证
 * @return array/password
 */
function password($password, $encrypt='') {
	//import('ORG.Util.String');
	$pwd = array();
	$pwd['encrypt'] =  $encrypt ? $encrypt : String::randString(6);
	$pwd['password'] = md5(md5(trim($password)).$pwd['encrypt']);
	return $encrypt ? $pwd['password'] : $pwd;
}
/**
 * 取得文件扩展
 * @param $filename 文件名
 * @return 扩展名
 */
function fileext($filename) {
	return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
}
/**
 * 解析多行sql语句转换成数组
 * @param string $sql
 * @return string;
 */
function sql_split($sql) {
	$sql = str_replace("\r", "\n", $sql);
	$ret = array();
	$num = 0;
	$queriesarray = explode(";\n", trim($sql));
	unset($sql);
	foreach($queriesarray as $query) {
		$ret[$num] = '';
		$queries = explode("\n", trim($query));
		$queries = array_filter($queries);
		foreach($queries as $query) {
			$str1 = substr($query, 0, 1);
			if($str1 != '#' && $str1 != '-') $ret[$num] .= $query;
		}
		$num++;
	}
	return($ret);
}

/**
 * 文件扫描
 * @param $filepath     目录
 * @param $subdir       是否搜索子目录
 * @param $ex           搜索扩展
 * @param $isdir        是否只搜索目录
 * @param $md5			是否生成MD5验证码
 * @param $enforcement  强制更新缓存
 */
function scan_file_lists($filepath, $subdir = 1, $ex = '', $isdir = 0, $md5 = 0, $enforcement = 0) {
	static $file_list = array();
	if ($enforcement) $file_list = array();
	$flags = $isdir ? GLOB_ONLYDIR : 0;
	$list = glob($filepath.'*'.(!empty($ex) && empty($subdir) ? '.'.$ex : ''), $flags);
	if (!empty($ex)) $ex_num = strlen($ex);
	foreach ($list as $k=>$v) {
		$v1 = str_replace(SITE_DIR, '', $v);
		if ($subdir && is_dir($v)) {
			scan_file_lists($v.DIRECTORY_SEPARATOR, $subdir, $ex, $isdir, $md5);
			continue;
		}
		if (!empty($ex) && strtolower(substr($v, -$ex_num, $ex_num)) == $ex) {
			if ($md5) {
				$file_list[$v1] = md5_file($v);
			} else {
				$file_list[] = $v1;
			}
			continue;
		} elseif (!empty($ex) && strtolower(substr($v, -$ex_num, $ex_num)) != $ex) {
			unset($list[$k]);
			continue;
		}
	}
	return $file_list;
}
/**
 * 生成CNZZ统计代码
 */
function tjcode($type='1') {
	if(!S('cnzz')) return false;
	$config = S('cnzz');
	if (empty($config)) {
		return false;
	} else {
		if(!$type) $type=1;
		return '<script src=\'http://pw.cnzz.com/c.php?id='.$config['siteid'].'&l='.$type.'\' language=\'JavaScript\' charset=\'gb2312\'></script>';
	}
}
/**
 * 打印函数
 * @param array $array
 */
function p($array) {
	dump($array,1,'<pre>',0);
}
/**
 * 返回毫秒
 * @return number
 */
function getMillisecond()
{
	list($t1, $t2) = explode(' ', microtime());
	return (float)sprintf('%.0f',(floatval($t1) + floatval($t2)) * 1000);
}
/**
 * curl post 方法
 * @param unknown $post_url
 * @param unknown $string
 * @return mixed
 */
function curl_post($url, $string){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);

	curl_setopt($ch, CURLOPT_POSTFIELDS, $string);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}
/**
 * unicode 转中文
 * @param string $str
 * @param string $encoding
 * @return mixed
 */
function unicodeString($str, $encoding=null) {
	return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/u', create_function('$match', 'return mb_convert_encoding(pack("H*", $match[1]), "utf-8", "UTF-16BE");'), $str);
}
/**
 * 返回数据
 * @param unknown $data
 * @param string $msg
 * @param number $code
 * @param string $page
 * @param string $debugMsg
 * @return array
 */
function showData($data, $msg='', $code=0, $page='', $debugMsg = '' ){
	return array(
			'data'		 	=> $data,
			'msg'  			=> $msg,
			'debugMsg'  	=> $debugMsg,
			'code' 			=> $code,
			'page' 			=> $page
	);
}
/**
 * 分页函数
 * @param int $total
 * @return array
 */
function page($total) {
	$return 		= array ();
	$count 			= I('pageSize','0','intval') ? I('pageSize','0','intval') : 20;
	$page_count 	= max ( 1, ceil ( $total / $count ) );
	$page 			= I('page','0','intval') ? I('page','0','intval') :1;
	$page_next 		= min ( $page + 1, $page_count );
	$page_previous 	= max ( 1, $page - 1 );
	$offset 		= max ( 0, ( int ) (($page - 1) * $count) );

	$return = array (
			'total' 		=> (int) $total,
			'count' 		=> $count,
			'pageCount' 	=> $page_count,
			'page' 			=> $page,
			'page_next' 	=> $page_next,
			'page_previous' => $page_previous,
			'offset' 		=> $offset,
			'limit' 		=> $count
	);
	return $return;
}
/**
 * 上传文件命令规则
 * @param string $str
 * @return string
 */
function fileSaveName($str=''){
	return md5(microtime(true).'_'.String::uuid());
}
/**
 * 上传文件
 * @param unknown $savePath	保存目录
 * @param unknown $uid		用户id
 * @param unknown $type		类型user group share
 * @return string|array
 */
function upload($savePath, $uid, $type){
	//头像 100  聊天小图 200 分享图片 160
	$ROOT_PATH  = SITE_DIR.UPLOADS;					//保存物理地址
	$URL_PATH 	= SITE_PROTOCOL.SITE_URL.UPLOADS;	//URL根目录
	$upload 	= new Upload();
	$upload->exts 	   = array('jpg','png','gif');	// 设置附件上传类型
	$upload->rootPath  = SITE_DIR.UPLOADS;
	$upload->replace   = true;
	$upload->savePath  = $savePath; 				// 设置附件上传目录
	$upload->saveName  = array('fileSaveName','');
	//设置缩略图
	$thumbWith 		   = 100;
	$thumbHeight 	   = 100;
	switch ($type) {
		case 'user':
			$upload->subName   = $uid;
			if (!$uid) $upload->savePath  = $savePath.'0/'; // 设置附件上传目录
			break;
		case 'cover':
			$upload->subName   = $uid;
			break;
		case 'group':
			$upload->subName   = array('date','Ymd');
			break;
		case 'share':
			$upload->subName   = array('date','Ymd');
			$thumbWith		= 160;
			$thumbHeight	= 160;
			break;
		default:
			$upload->subName   = array('date','Ymd');
			break;
	}
	$info   =   $upload->upload();
	if(!$info) {
		return $upload->getError();// 上传错误提示错误信息
	}else{// 上传成功
		$SAVE_PATH_HEAD = SITE_DIR.UPLOADS;//保存物理地址
		$image = new Image();
		$data  = array();
		foreach($info as $file){
			$originUrl =  $file['savepath'].$file['savename'];		//原图
			$smallUrl  =  $file['savepath'].'s_'.$file['savename'];	//小图
			if ($type == 'cover') {
				$data = array('originUrl'=>$URL_PATH.ltrim($originUrl,'/'));
			}else {
				$image->open($SAVE_PATH_HEAD.$originUrl);//打开图片
				$image->thumb($thumbWith, $thumbHeight)->save($SAVE_PATH_HEAD.$smallUrl);//切小图
				//user
				$data[] = array(
						'key'		=> $file['key'],
						'originUrl'	=> $URL_PATH.ltrim($originUrl,'/'),
						'smallUrl'	=> $URL_PATH.ltrim($smallUrl,'/'),
				);
			}
		}
		return $data;
	}
}

/**
 * 修改CONF_PATH配置文件  如果$config_file为空，则修改config.php
 * @param unknown $new_config
 * @param string $config_file
 * @return boolean
 */
function update_config($new_config, $config_file = '') {
	!is_file($config_file) && $config_file = CONF_PATH . '/config.php';
	if (is_writable($config_file)) {
		$config = require $config_file;
		$config = array_merge($config, $new_config);

		file_put_contents($config_file, "<?php \nreturn " . stripslashes(var_export($config, true)) . ";", LOCK_EX);
		@unlink(RUNTIME_FILE);
		return true;
	} else {
		return false;
	}
}

//================================= 数组相关操作 =============================
/**
 * 移除数组$arr中值为$element的元素，比如
 * {'test', 'show'}移除'test'，则返回{'show'}
 * @param unknown $arr
 * @param unknown $value
 */
function deleteArrayEelement($arr, $element) {
	$returnarr = array();
	
	foreach ($arr as $key=>$value ) {
		if($value != $element)
			$returnarr[$key] = $value;
	}
	
	return $returnarr;
}

/**
 * unicode转utf8
 * @param  $c
 */
function unicode_utf8($c) {
	$str = '';
	if($c < 0x80) {
		$str .= $c;
	} elseif($c < 0x800) {
		$str .= (0xC0 | $c >> 6);
		$str .= (0x80 | $c & 0x3F);
	} elseif($c < 0x10000) {
		$str .= (0xE0 | $c >> 12);
		$str .= (0x80 | $c >> 6 & 0x3F);
		$str .= (0x80 | $c & 0x3F);
	} elseif($c < 0x200000) {
		$str .= (0xF0 | $c >> 18);
		$str .= (0x80 | $c >> 12 & 0x3F);
		$str .= (0x80 | $c >> 6 & 0x3F);
		$str .= (0x80 | $c & 0x3F);
	}
	return $str;
}
/**
 * utf8转unicode
 * @param  $c
 */
function utf8_unicode($c) {
	switch(strlen($c)) {
		case 1:
			return ord($c);
		case 2:
			$n = (ord($c[0]) & 0x3f) << 6;
			$n += ord($c[1]) & 0x3f;
			return $n;
		case 3:
			$n = (ord($c[0]) & 0x1f) << 12;
			$n += (ord($c[1]) & 0x3f) << 6;
			$n += ord($c[2]) & 0x3f;
			return $n;
		case 4:
			$n = (ord($c[0]) & 0x0f) << 18;
			$n += (ord($c[1]) & 0x3f) << 12;
			$n += (ord($c[2]) & 0x3f) << 6;
			$n += ord($c[3]) & 0x3f;
			return $n;
	}
}