<?php
namespace User\Model;
use Think\Model;
use Message\Core\Openfire;
use Common\Tools\Pinyin;
class UserModel extends Model {
	protected $tableName 	= 'user';
	protected $pk        	= 'uid';
	protected $friendTable  = 'user_friend';
	protected $userFavorite = 'user_favorite';
	protected $tablePrefix;
	
	private $map = array();
	function _initialize(){
		$this->tablePrefix	= C('DB_PREFIX');
	}
	/**
	 * 用户数据格式化
	 * @param array $user
	 * @param int   $uid
	 */
	private function _format($list, $uid=0){
		$_list = array();
		if ($list) {
			foreach ($list as $k=>$v){
				$tmp['uid'] 			= $v['uid'];
				$tmp['sort']			= $v['sort'];
				$tmp['createadmin']			= $v['createadmin'];
				$tmp['company_id']			= $v['company_id'];
				$tmp['phone']			= $v['phone'];
				unset($tmp['password']);
				if ($uid && ($uid == $v['uid'])){
					$tmp['password'] 	= $v['openfire'];
				}
				$tmp['nickname'] 		= $v['nickname'];
				$tmp['headsmall'] 		= $v['headsmall'];
				$tmp['headlarge'] 		= str_replace('/s_', '/', $v['headsmall']);
				$tmp['gender']			= $v['gender'];//0-男 1-女 2-未填写
				$tmp['sign'] 			= $v['sign'];
				$tmp['province']		= $v['province'];
				$tmp['city']			= $v['city'];
				$num = $v['ufd'] + ($v['fud'] ? 2 : 0);
				$tmp['isfriend']		= in_array($num, array('1','3')) ? 1 : 0;
				$tmp['isblack']			= $v['isblack'];
				$tmp['verify']			= is_null($v['verify']) ? '' : $v['verify'];
				$tmp['isstar']			= is_null($v['isstar']) ? '0' : $v['isstar'];
				
				$tmp['remark']			= is_null($v['remark']) ? '' : $v['remark'];
				$tmp['getmsg']			= is_null($v['getmsg']) ? '0' : $v['getmsg'];
				$tmp['fauth1']			= is_null($v['fauth1']) ? '0' : $v['fauth1'];
				$tmp['fauth2']			= is_null($v['fauth1']) ? '0' : $v['fauth2'];
				$tmp['picture1']		= is_null($v['picture1']) ? '' : $v['picture1'];
				$tmp['picture2']		= is_null($v['picture2']) ? '' : $v['picture2'];
				$tmp['picture3']		= is_null($v['picture3']) ? '' : $v['picture3'];
				$tmp['cover']			= is_null($v['cover']) ? '' : $v['cover'];
				if ($v['remark']) {//备注名
					$py   = new Pinyin();
					$tmp['sort'] = $py->pinyin($v['remark'], 1, 1);
				}
				$tmp['createtime'] 		= $v['createtime'];
				
				$_list[] = $tmp;
			}
		}
		return $_list;
	}
	/**
	 * 检查是否是好友
	 * @param unknown $uid
	 * @param unknown $fid
	 * @return boolean 0-没有关系 1-关注 2-被关注 3-相互关注
	 */
	private function _checkisFriend($uid, $fid){
		/* $count = M($this->friendTable)->where("(uid=$uid and fid=$fid) or (uid=$fid and fid=$uid)")->count();
		if ($count) {
			return array('status'=>1);
		}else {
			return array('status'=>0, 'user'=>self::getUserName($uid));
		}
		 */
		$uf    = M( $this->friendTable )->where(array('uid'=>$uid, 'fid'=>$fid))->count();//uid关注fid
		$fu    = M( $this->friendTable )->where(array('uid'=>$fid, 'fid'=>$uid))->count();//fid关注uid
		$count = $uf + ($fu ? 2 : 0);
		if ($count) {
			return array('status'=>$count);
		}else {
			return array('status'=>0, 'user'=>self::getUserName($uid));
		}
	}
	/**
	 * 公共的用户列表
	 */
	private function _list($map,$uid){
		$total = $this->alias('u')->where($map)->count();
		if ($total) {
			$page  = page($total);
			$limit = $page['offset'] .','. $page['limit'];
		}else {
			$page  = '';
		}
		$list = $total ? $this->public_list($uid, $map, $limit) : array();
		return showData($list, '', 0, $page);
	}
	/**
	 * 用户列表
	 * @param unknown $map
	 * @param unknown $limit
	 * @param string $order
	 * @param number $uid
	 * @return unknown
	 */
	function public_list($uid, $map, $limit, $order='u.sort asc'){
		//isfriend = '(SELECT COUNT(*) FROM `'.$this->tablePrefix.$this->friendTable.'` where (uid='.$uid.' and fid=u.uid) or (uid=u.uid and fid='.$uid.')) as isfriend';
		$ufd = '(SELECT COUNT(*) FROM `'.$this->tablePrefix.$this->friendTable.'` where (uid='.$uid.' and fid=u.uid)) as ufd';
		$fud = '(SELECT COUNT(*) FROM `'.$this->tablePrefix.$this->friendTable.'` where (fid='.$uid.' and uid=u.uid)) as fud';
		//$remark   = '(SELECT remark FROM `'.$this->tablePrefix.$this->friendTable.'` WHERE uid='.$uid.' and fid=u.uid) as remark';
		$isblack  = '(SELECT COUNT(*) FROM `'.$this->tablePrefix.'blacklist` where uid='.$uid.' and fid=u.uid) as isblack';
		$field    = 'u.*,uf.remark,uf.isstar,uf.getmsg,uf.fauth1,uf.fauth2,fu.picture1,fu.picture2,fu.picture3,fu.cover,'.$ufd.','.$fud.','.$isblack;
		$remark   = 'LEFT JOIN `'.$this->tablePrefix.$this->friendTable.'` uf ON uf.uid='.$uid.' and fid=u.uid';
		$picture  = 'LEFT JOIN `tc_friend_user` fu ON fu.uid=u.uid';
		$list  = $this->alias('u')->field($field)->where($this->map)->where($map)->join($remark)->join($picture)->order($order)->limit($limit)->select();
		$_list = $this->_format($list,$uid);
		if ($limit == 1) {
			return $_list['0'];
		}else {
			return $_list;
		}
	}
	/**
	 * 取得用户的uid,name headsmall
	 * @param int|string $uid
	 * @return array
	 */
	public function getUserName($uid){
		return $this->field('uid, nickname as name, headsmall')->where(array('uid'=>$uid))->find();
	}
	/**
	 * 系统用户名
	 */
	public function systemUserName(){
		return array('uid'=>'admin', 'name'=>'admin', 'url'=>'');
	}
	/**
	 * 获取用户的详细资料
	 * @param int $uid
	 * @param int $fid
	 * @return array
	 */
	function user($uid, $fid=0){
		if ($fid && $fid != $uid) {
			return showData($this->public_list($uid, array('u.uid'=>$fid), 1));
		}else {
			return showData($this->public_list($uid, array('u.uid'=>$uid), 1));
		}
	}
	/**
	 * 用户注册
	 * @param string $phone;
	 * @param string $nickname;
	 */
	function regist($data=array()){
		if ($data) {
			;
		}else {
			$data = array(
				'phone'		=> trim(I('phone')),
				'password'	=> trim(I('password')),
				'createtime'=> NOW_TIME,
				'openfire'	=> rand(100000, 999999),
			);
		}
		//检测手机号
		if ($data['phone']){
			if (strlen($data['phone']) == 11 && is_numeric($data['phone'])) {
				if ($this->where(array('phone'=>$data['phone']))->count()) {
					return showData(new \stdClass(), '该手机号已注册', 1);
				}
			}else {
				return showData(new \stdClass(), '手机号格式错误', 1);
			}
		}else {
			return showData(new \stdClass(), '请输入手机号', 1);
		}
		//md5加密
		if (!$data['password']) {
			return showData(new \stdClass(), '请输入密码', 1);
		}else {
			$data['password'] = md5($data['password']);
		}
		
		$uid = $this->add($data);
		if ($uid) {
			$openfire = new Openfire();
			$ret = $openfire->regist($uid, $data['openfire']);
			if ($ret == 0){
				$this->delete($uid);
				return showData(new \stdClass(), '注册失败', 1, '', 'openfire注册失败');
			}else {
				return $this->user($uid);
			}
		}else {
			return showData(new \stdClass(), '注册失败', 1);
		}
	}
	/**
	 * 用户登录
	 * @param string $username; 用户名
	 * @param string $password; 密码
	 */
	function login(){
		$phone	  = trim(I('phone'));
		$password = trim(I('password'));
	
		if (!$phone) return showData(new \stdClass(), '请输入手机号', 1);
		if (!$password) return showData(new \stdClass(), '请输入密码', 1);
	
		$user = $this->field('uid,phone,password')->where(array('phone'=>$phone))->find();
		//echo $user['password']."=".md5($password)."===========";
		if ($user) {
			if ($user['password'] == md5($password)) {
				return $this->user($user['uid']);
			}else {
				return showData(new \stdClass(), '密码错误', 1);
			}
		}else {
			return showData(new \stdClass(), '该帐号不存在', 1);
		}
	}
	/**
	 * 编辑用户资料
	 */
	function edit($uid){
		$headsmall = '';
		if (!empty($_FILES)) {
			$image = upload('/Picture/avatar/', $uid, 'user');
			if (is_string($image)) {
				return showData(new \stdClass(), $image, 1);
			}else {
				$headsmall = $image['0']['smallUrl'];
			}
		}
		$nickname 	= trim(I('nickname'));
		$gender 	= trim(I('gender',''));
		$province 	= trim(I('province'));
		$city 		= trim(I('city'));
		$sign 		= trim(I('sign', ''));
		
		$data = array();
		if ($headsmall) $data['headsmall'] = $headsmall;
		if ($nickname) {
			if ($this->where(array('nickname'=>$nickname,'uid'=>array('neq', $uid)))->count()) {
				return showData(new \stdClass(), '该昵称已存在', 1);
			}else {
				$data['nickname'] = $nickname;
				$py = new Pinyin();
				$data['sort'] = $py->pinyin($nickname, 1, 1);
			}
		}else {
			return showData(new \stdClass(), '请输入昵称', 1);
		}
		if ( $gender !== '') $data['gender'] = $gender;
		if ($province){
			$data['province'] = $province;
			if ($city) {
				$data['city'] = $city;
			}else {
				return showData(new \stdClass(), '请选择市', 1);
			}
		}
		$data['sign'] = $sign;
		if (count($data)) {
			$data['uid'] = $uid;
			if ($this->save($data) !== false) {
				return $this->user($uid);
			}else {
				return showData(new \stdClass(), '修改失败', 1);
			}
		}else {
			return showData(new \stdClass(), '未填写任何资料', 1);
		}
	}
	
	
	function add(){
	    
	}
	
	/**
	 * 搜索用户
	 */
	function search($uid){
		$string = trim(I('search'));
		if (!$string) return showData(new \stdClass(), '请输入搜索用户昵称或手机', 1);
		
		$map['u.phone|u.nickname'] 	= array('like', '%'.$string.'%');
		$map['u.uid'] 				= array('neq', $uid);
		$map['u.nickname'] 			= array('neq','');
		
		return $this->_list($map, $uid);
	}
	/**
	 * 用户的好友列表
	 * @param int $uid;
	 * @param string $string 
	 */
	function friendlist($uid, $string=''){
		$_string = 'u.uid IN (SELECT fid FROM `'.$this->tablePrefix.$this->friendTable.'` WHERE uid='.$uid.' and isblack=0)';
		if ($string) {
			$_string = '('.$_string.') AND ('.$string.')';
		}
		$map['_string'] = $_string;
		$list = $this->public_list($uid, $map, 0);
		return showData($list);
	}
	/**
	 * 申请加好友
	 * @param int|string $uid
	 * @param int|string $fid
	 * @param number $type
	 * @return array
	 */
	function applyAddFriend($uid, $fid, $type=101){
		if ($uid == $fid) return showData(new \stdClass(), '您不能加自己为好友', 1);
		//需要验证
		if ($this->where(array('uid'=>$fid))->getField('verify')) {
			$content = trim(I('content',''));//申请的理由
			$info = self::_checkisFriend($uid, $fid);
			if (in_array($info['status'], array('1','3'))) {
				return showData(new \stdClass(), '你们已经是好友了！', 1);
			}else if ($info['status'] == 0) {
				$user	  = $info['user'];
				$openfire = new Openfire();
				$to['toid']  = $fid;
				$to['touid'] = $fid;
				if ($openfire->notice($user, $to, $type, $content)) {
					return showData(new \stdClass(), '发送请求成功');
				}else {
					return showData(new \stdClass(), '发送请求失败', 1);
				}
			}else if ($info['status'] == 2) {
				if (M($this->friendTable)->add(array('uid'=>$uid, 'fid'=>$fid, 'addtime'=>NOW_TIME))){
					return showData(new \stdClass(), '添加成功');
				}else {
					return showData(new \stdClass(), '添加失败', 1);
				}
			}
		}else {
			return self::agreeAddFriend($uid, $fid);//不需要验证
		}
	}
	/**
	 * 同意加好友
	 */
	function agreeAddFriend($uid, $fid, $type=102){
		if ($uid == $fid) return showData(new \stdClass(), '您不能加自己为好友', 1);
		$info = self::_checkisFriend($uid, $fid);
		if (in_array($info['status'], array('1','3'))) {
			return showData(new \stdClass(), '你们已经是好友了！', 1);
		}else if ($info['status'] == 0) {
			$data['0'] = array('uid'=>$uid, 'fid'=>$fid, 'addtime'=>NOW_TIME);
			$data['1'] = array('uid'=>$fid, 'fid'=>$uid, 'addtime'=>NOW_TIME);
			if (M($this->friendTable)->addAll($data)) {
				$user	   = self::getUserName($uid);
				$openfire  = new Openfire();
				$to['toid']  = $fid;
				$to['touid'] = $fid;
				$openfire->notice($user, $to, $type, $user['name'].'同意加你为好友');//发送通知
				return showData(new \stdClass(), '加好友成功');
			}else {
				return showData(new \stdClass(), '加好友失败', 1);
			}
		}else if ($info['status'] == 2) {
			if (M($this->friendTable)->add(array('uid'=>$uid, 'fid'=>$fid, 'addtime'=>NOW_TIME))){
				return showData(new \stdClass(), '添加成功');
			}else {
				return showData(new \stdClass(), '添加失败', 1);
			}
		}
	}
	/**
	 * 拒绝加好友
	 */
	function refuseAddFriend($uid, $fid, $type=103){
		$openfire = new Openfire();
		$user = self::getUserName($uid);
		$to['toid']  = $fid;
		$to['touid'] = $fid;
		if ($openfire->notice($user, $to, $type, $user['name'].'拒绝加你为好友')) {
			return showData(new \stdClass(), '发送拒绝消息成功');
		}else {
			return showData(new \stdClass(), '发送拒绝消息失败', 1);
		}
	}
	//删除好友 备注名 黑名单 列表 设置 取消
	/**
	 * 删除好友
	 * @param unknown $uid
	 * @param unknown $fid
	 * @param number $type
	 */
	function deleteFriend($uid, $fid, $type=104){
		//$string = '(uid='.$uid.' and fid='.$fid.') or (uid='.$fid.' and fid='.$uid.')';
		$openfire = new Openfire();
		$user     = self::getUserName($uid);
		$to['toid']  = $fid;
		$to['touid'] = $fid;
		if (M($this->friendTable)->where(array('uid'=>$uid, 'fid'=>$fid))->delete() !== false) {
			$openfire->notice($user, $to, $type, $user['name'].'把你从好友中删除了');
			return showData(new \stdClass(), '删除成功');
		}else {
			return showData(new \stdClass(), '删除失败', 1);
		}
	}
	/**
	 * 设置备注名
	 * @param unknown $uid
	 * @param unknown $fid
	 * @return array
	 */
	function userRemark($uid, $fid){
		$remark = trim(I('remark'));
		$map['uid'] = $uid;
		$map['fid'] = $fid;
		if (M($this->friendTable)->where($map)->setField('remark', $remark) !== false) {
			return showData(new \stdClass(), '设置成功');
		}else {
			return showData(new \stdClass(), '修改失败', 1);
		}
	}
	/**
	 * 添加 移除黑名单
	 */
	function black($uid, $fid){
		$data = array('uid'=>$uid, 'fid'=>$fid);
		if ($uid == $fid) return showData(new \stdClass(), '不能添加自己到黑名单', 1);
		if (M('blacklist')->where($data)->count()) {
			if (M('blacklist')->where($data)->delete()) {
				//加入回好友列表
				M($this->friendTable)->where($data)->setField('isblack', 0);
				return showData(new \stdClass(), '移除成功');
			}else {
				return showData(new \stdClass(), '移除失败', 1);
			}
		}else {
			if (M('blacklist')->add($data)) {
				//好友列表进行标识
				M($this->friendTable)->where($data)->setField('isblack', 1);
				return showData(new \stdClass(), '添加成功');
			}else {
				return showData(new \stdClass(), '添加失败', 1);
			}
		}
	}
	/**
	 * 黑名单列表
	 */
	function blackList($uid){
		$_string = 'u.uid IN (SELECT fid FROM `'.$this->tablePrefix.'blacklist` WHERE uid='.$uid.')';
		$map['_string'] = $_string;
		$list = $this->public_list($uid, $map, 0);
		return showData($list);
	}
	/**
	 * 导入手机号码识别关系
	 * @param int $uid
	 * @param string $string 格式 电话1,电话2,电话3
	 * @return number 0-邀请:没有注册 1-添加:已注册 2-已添加:已是朋友
	 */
	function importContact($uid){
		$string = trim(I('phone'));
		if (!$string) return showData(new \stdClass(), '请导入手机通讯录', 1);
		$arr  = explode(',', $string);
		$list = array();
		foreach ($arr as $key=>$v){
			$user = $this->field('uid,verify')->where(array('phone'=>$v))->find();
			if ($user) {//存在
				$list[$key]['phone'] 	= $v;
				$return = self::_checkisFriend($uid, $user['uid']);
				$list[$key]['isfriend'] = in_array($return['status'], array('1','3')) ? 1 : 0;
				$list[$key]['type']  	= 1;
				$list[$key]['uid']  	= $user['uid'];
				$list[$key]['verify']	= $user['verify'];
			}else {//不存在  邀请
				$list[$key]['phone'] 	= $v;
				$list[$key]['isfriend'] = 0;
				$list[$key]['type']  	= 0;
				$list[$key]['uid'] 	    = 0;
			}
		}
		return showData($list);
	}
	/**
	 * 新的朋友
	 * @param int $uid
	 * @param string $string 格式 电话1,电话2,电话3
	 * @return array
	 */
	function newFriend($uid){
		$string = trim(I('phone'));
		if (!$string) return showData(new \stdClass(), '请导入手机通讯录', 1);
		$arr  = explode(',', $string);
		$list = array();
		foreach ($arr as $key=>$v){
			$user     = $this->field('uid, headsmall, nickname as name, verify')->where(array('phone'=>$v,'uid'=>array('neq', $uid)))->find();
			if ($user) {//存在
				$return = self::_checkisFriend($uid, $user['uid']);
				if (!in_array($return['status'], array('1','3'))) {
					$tmp = $user;
					$tmp['phone'] = $v;
					
					$list[] = $tmp;
				}
			}
		}
		return showData($list);
	}
	/**
	 * 用户收藏
	 * @param unknown $uid
	 */
	function favorite($uid, $fid){
		$data = array(
			'uid'		=> $uid,
			'fid'		=> $fid,
			'otherid'	=> I('otherid', 0),
			'content'	=> json_encode(trim(I('content', '', ''))),
		);
		if (!$data['content']) return showData(new \stdClass(), '请输入收藏内容', 1);
		
		if (M($this->userFavorite)->where($data)->count()) {
			return showData(new \stdClass(), '已收藏', 1);
		}else {
			$data['createtime'] = NOW_TIME;
			if (M($this->userFavorite)->add($data)){
				return showData(new \stdClass(), '收藏成功');
			}else {
				return showData(new \stdClass(), '收藏失败', 1);
			}
		}
	}
	/**
	 * 删除收藏
	 * @param unknown $uid
	 * @param unknown $favoriteid
	 */
	function deleteFavorite($uid){
		$favorite_id = I('favoriteid');
		if (!$favorite_id) return showData(new \stdClass(), '请选择一条收藏', 1);
		if (M($this->userFavorite)->delete($favorite_id)) {
			return showData(new \stdClass(), '删除成功');
		}else {
			return showData(new \stdClass(), '删除失败', 1);
		}
	}
	/**
	 * 用户的收藏列表
	 * @param unknown $uid
	 */
	function favoriteList($uid){
		$map = array('f.uid'=>$uid);
		$sinceID = I('sinceID', 0, 'intval');
		if ($sinceID) $map['f.id'] = array('gt',$sinceID);
		$maxID = I('maxID', 0, 'intval');
		if ($maxID) $map['f.id'] = array('lt',$maxID);
		//分页
		$total = M($this->userFavorite)->alias('f')->where($map)->count();
		if ($total){
			$page  = page($total);
			$limit = $page['offset'] . ',' . $page['limit'];
		}else {
			$page  = '';
		}
		$name  = '(SELECT name FROM `'.$this->tablePrefix.'session` WHERE id=f.otherid) as name';
		$field = 'f.*,u.headsmall,u.nickname';
		$join  = 'LEFT JOIN `'.$this->tablePrefix.$this->tableName.'` u ON u.uid=f.fid';
		$list  = $total ? M($this->userFavorite)->alias('f')->field($field)->join($join)->where($map)->limit($limit)->order('f.createtime desc')->select() : array();
		if ($total) {
			foreach ($list as $k=>$v){
				$list[$k]['content'] = json_decode($v['content']);
			}
		}
		return showData($list, '', 0, $page);
	}
	/**
	 * 设置星标朋友
	 * @param unknown $uid
	 * @param unknown $sessionid
	 */
	function setStar($uid, $fid){
		$db  = M($this->friendTable);
		$map = array('uid'=>$uid, 'fid'=>$fid);
		if ($db->where($map)->getField('isstar')) {
			if ($db->where($map)->setField('isstar', 0)) {
				return showData(new \stdClass(), '取消星标朋友成功');
			}else {
				return showData(new \stdClass(), '取消星标朋友失败', 1);
			}
		}else {
			if ($db->where($map)->setField('isstar', 1)) {
				return showData(new \stdClass(), '设置星标朋友成功');
			}else {
				return showData(new \stdClass(), '设置星标朋友失败', 1);
			}
		}
	}
	/**
	 * 修改密码
	 * @param unknown $uid
	 */
	function editPassword($uid){
		$oldpassword = I('oldpassword');
		$newpassword = I('newpassword');
		if (!$oldpassword) return showData(new \stdClass(), '新输入旧密码', 1);
		if (!$newpassword) return showData(new \stdClass(), '新输入新密码', 1);
		if ($oldpassword == $newpassword) return showData(new \stdClass(), '旧密码和新密码不能相同', 1);
		
		$info = $this->where(array('uid'=>$uid))->getField('password');
		if ($info == md5($oldpassword)) {
			if ($this->where(array('uid'=>$uid))->setField('password', md5($newpassword))){
				return showData(new \stdClass(), '修改成功');
			}else {
				return showData(new \stdClass(), '修改失败', 1);
			}
		}else {
			return showData(new \stdClass(), '旧密码错误', 1);
		}
	}
	/**
	 * 反馈意见
	 * @param unknown $uid
	 */
	function feedback($uid){
		$content = I('content');
		if (!$content) return showData(new \stdClass(), '请输入反馈意见', 1);
		$data = array(
			'uid'	  	 => $uid,
			'content' 	 => $content,
			'createtime' => NOW_TIME
		);
		if (M('user_feedback')->add($data)) {
			return showData(new \stdClass(), '谢谢你的反馈');
		}else {
			return showData(new \stdClass(), '反馈失败', 1);
		}
	}
	/**
	 * 设置加好友的状态 0-不验证 1-验证
	 * @param unknown $uid
	 * @return array
	 */
	function setVerify($uid){
		$data   = array('uid'=>$uid);
		$verify = $this->where($data)->getField('verify');
		if ($verify) {
			$ret = $this->where($data)->setField('verify', 0);
		}else {
			$ret = $this->where($data)->setField('verify', 1);
		}
		if ($ret) {
			return showData(new \stdClass(), '设置成功');
		}else {
			return showData(new \stdClass(), '设置失败', 1);
		}
	}
	/**
	 * 设置是否接收用户消息
	 * @param unknown $uid
	 * @param unknown $fid
	 */
	function setGetmsg($uid, $fid){
		$data   = array('uid'=>$uid, 'fid'=>$fid);
		$getmsg = M($this->friendTable)->where($data)->getField('getmsg');
		if ($getmsg) {
			$ret = M($this->friendTable)->where($data)->setField('getmsg', 0);
		}else {
			$ret = M($this->friendTable)->where($data)->setField('getmsg', 1);
		}
		if ($ret) {
			return showData(new \stdClass(), '设置成功');
		}else {
			return showData(new \stdClass(), '设置失败', 1);
		}
	}
}