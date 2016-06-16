<?php
/**
 * 临时会话
 * @author yangdong
 *
 */
namespace Session\Model;
use Think\Model;
use User\Model\UserModel;
use Message\Core\Openfire;
class SessionModel extends Model {
	protected $tableName 	= 'session';
	protected $pk        	= 'id';
	protected $sessionUser  = 'session_user';
	protected $tablePrefix;
	
	function _initialize(){
		$this->tablePrefix	= C('DB_PREFIX');
	}
	/**
	 * 临时会话数据格式化
	 * @param unknown $list
	 * @param number $uid
	 */
	private function _format($list, $uid=0){
		$_list = array();
		if ($list) {
			foreach ($list as $k=>$v){
				$tmp['id']			= $v['id'];
				$tmp['name']		= $v['name'];
				$tmp['count']	    = $v['count'];
				$tmp['uid']			= $v['uid'];
				$tmp['creator']	    = $v['creator'];
				$tmp['mynickname']  = $v['nickname'] ? $v['nickname'] :$v['mynickname'];
				
				if (isset($v['role'])) {
					if (is_null($v['role'])) {	//表示用户与这个群没有任何的关系
						$tmp['isjoin'] = 0;		//未加入群
						$tmp['role']   = -1;	//没有角色
						$tmp['getmsg'] = -1;	//没有加入群，所以不会接收消息
					}else {
						$tmp['isjoin'] = 1;				//加入了群
						$tmp['role']   = $v['role'];	//0-member 1-owner
						$tmp['getmsg'] = $v['getmsg'];	//0-不接收 1-接收
					}
				}
				
				$tmp['createtime']  = $v['createtime'];
				
				$_list[] = $tmp;
			}
		}
		return $_list;
	}
	/**
	 * 获取当前用户是否是创建者
	 * @param unknown $uid
	 * @param unknown $sessionid
	 */
	private function _checkisSessionCreator($uid, $sessionid){
		return M($this->sessionUser)->where(array('sessionid'=>$sessionid, 'uid'=>$uid))->getField('role');
	}
	/**
	 * 获取用户在临时会话中的昵称
	 * @param unknown $uid
	 * @param unknown $sessionid
	 */
	private function _getUserSessionNickname($uid, $sessionid){
		return M($this->sessionUser)->where(array('sessionid'=>$sessionid, 'uid'=>$uid))->getField('nickname');
	}
	/**
	 * 临时会话会话列表
	 * @param unknown $uid
	 * @param unknown $map
	 * @param unknown $limit
	 * @param string $order
	 */
	function public_list($uid, $map, $limit, $order='createtime desc'){
		$creator  = '(SELECT nickname FROM `'.$this->tablePrefix.'user` WHERE uid=s.uid) as creator';
		$nickname = '(SELECT nickname FROM `'.$this->tablePrefix.'user` WHERE uid='.$uid.') as mynickname';
		$mbCount  = '(SELECT count(*) FROM `'.$this->tablePrefix.$this->sessionUser.'` WHERE sessionid=s.id) as count';
		$join     = 'left join `'.$this->tablePrefix.$this->sessionUser.'` su on su.sessionid=s.id and su.uid='.$uid;
		$field    = 's.*, su.getmsg, su.role, su.nickname,'.$creator.','.$mbCount.','.$nickname;
		$list  	  = $this->alias('s')->field($field)->where($map)->join($join)->order($order)->limit($limit)->select();
		$_list 	  = $this->_format($list,$uid);
		if ($limit == 1) {
			return $_list['0'];
		}else {
			return $_list;
		}
	}
	/**
	 * 添加用户进入到会话
	 * @param int $sessionid;
	 * @param boolean
	 */
	private function addUserToSession($sessionid, $uids, $notice=0){
		$uidsArr = explode(',', $uids);
		$uidsArr = array_unique($uidsArr);
		$data    = array();
		$name 	 = $this->where(array('id'=>$sessionid))->getField('name');
		foreach ($uidsArr as $v){
			if ($v && !M($this->sessionUser)->where(array('sessionid'=>$sessionid, 'uid'=>$v))->count()) {
				$data[] = array(
					'sessionid'  => $sessionid,
					'uid'		 => $v,
					'addtime'	 => NOW_TIME,
				);
				//发送通知
				if ($notice) {
					self::sendNotice($v, $sessionid, 305, '用户[_]加入会话'.$name);
				}
			}
		}
		if ($data) {
			if (M($this->sessionUser)->addAll($data)) {
				return true;
			}else {
				return false;
			}
		}else {
			return true;
		}
	}
	/**
	 * 发送通知
	 * @param int $uid 用户id
	 * @param int $id  群id
	 * @param int $type 通知类型
	 * @param string $content 通知内容
	 */
	private function sendNotice($uid, $id, $type, $content){
		
		$uidsArr  = M($this->sessionUser)->field('uid')->where(array('sessionid'=>$id))->select();
		if ($uidsArr) {
			$openfire = new Openfire();
			$user	  = new UserModel();
			$userinfo = $user->getUserName($uid);//得到用户资料
			$nickname = self::_getUserSessionNickname($uid, $id);
			if ($type == 304) $userinfo['name'] = $nickname;
			$usernickname = $nickname ? $nickname : $userinfo['name'] ;//用户在群组中的昵称
			$to 	  = array('toid'=>$id);
			$session  = $this->find($id);
			$uids = '';
			foreach ($uidsArr as $k=>$v){
				$uids .= $v['uid'].',';
			}
			$uids = substr($uids, 0, -1);
			$to['touid']	= $uids;
			
			$content = str_replace('[_]', $usernickname, $content);//替换
			$openfire->notice($userinfo, $to, $type, $content, $session);
		}
	}
	
	/**
	 * 创建临时会话
	 * @param int $uid;
	 */
	function sessionAdd($uid){
		$uids = trim(I('uids'));
		$data = array(
			'uid'			=> $uid,
			'name'			=> trim(I('name')),
			'createtime'	=> NOW_TIME,
		);
		if (!$data['name']) return showData(new \stdClass(), '请输入会话名称', 1);
		if (!$uids) return showData(new \stdClass(), '请选择用户', 1);
		$sessionid = $this->add($data);
		if ($sessionid) {
			if (self::addUserToSession($sessionid, $uids)){
				//把自己也加入 并且是创建者
				$where = array('sessionid'=>$sessionid, 'uid'=>$uid);
				if (M($this->sessionUser)->where($where)->count()) {
					M($this->sessionUser)->where($where)->setField('role', 1);
				}else {
					$where['addtime']	= NOW_TIME;
					$where['role']		= 1;
					M($this->sessionUser)->add($where);
				}
				return self::detail($uid, $sessionid);
			}else {
				$this->delete($sessionid);//删除会话
				M($this->sessionUser)->where(array('sessionid'=>$sessionid))->delete();//删除部份加入的人
				return showData(new \stdClass(), '创建会话失败', 1);
			}
		}else {
			return showData(new \stdClass(), '创建会话失败', 1);
		}
	}
	/**
	 * 添加用户到临时会话  所有在这个群里的用户都可以添加
	 * @param unknown $uid
	 * @param unknown $sessionid
	 * @return array
	 */
	function addUser($uid, $sessionid){
		$uids = trim(I('uids'));
		if (!$uids) return showData(new \stdClass(), '请选择用户', 1);
		if (self::addUserToSession($sessionid, $uids, 1)) {
			return showData(new \stdClass(), '添加成功');
		}else {
			return showData(new \stdClass(), '添加失败', 1);
		}
	}
	/**
	 * 临时会话详细
	 * @param unknown $uid
	 * @param unknown $sessionid
	 */
	function detail($uid, $sessionid){
		$map['s.id'] = $sessionid;
		$info = self::public_list($uid, $map, 1);
		$user = new UserModel();
		$where['_string'] = 'u.uid IN (SELECT uid from `'.$this->tablePrefix.$this->sessionUser.'` where sessionid='.$sessionid.')';
		$info['list'] 	  = $user->public_list($uid, $where, 0);
		foreach ($info['list'] as $k=>$v){
			$nickname = M($this->sessionUser)->where(array('sessionid'=>$sessionid, 'uid'=>$v['uid']))->getField('nickname');
			if ($nickname) {
				$info['list'][$k]['nickname'] = $nickname;
			}
		}
		return showData($info);
	}
	/**
	 * 可选联系人列表
	 * @param unknown $uid
	 * @param unknown $sessionid
	 * @return array
	 */
	function contactList($uid, $sessionid){
		$user 	= new UserModel();
		$string = 'u.uid NOT IN (SELECT uid from `'.$this->tablePrefix.$this->sessionUser.'` where sessionid='.$sessionid.')';
		return $user->friendlist($uid, $string);
		
	}
	/**
	 * 退出会话
	 * @param int $uid 当前登陆用户
	 * @param int $session 会话id
	 * @param int type 通知类型
	 */
	function quitSession($uid, $sessionid, $type=300){
		if (self::_checkisSessionCreator($uid, $sessionid)) {
			return showData(new \stdClass(), '你是管理员,不能退出', 1);
		}else {
			if (M($this->sessionUser)->where(array('uid'=>$uid, 'sessionid'=>$sessionid))->delete()){
				self::sendNotice($uid, $sessionid, $type, '[_]退出了该会话');//发送通知
				return showData(new \stdClass(), '退出成功');
			}else {
				return showData(new \stdClass(), '退出失败', 1);
			}
		}
	}
	/**
	 * 删除用户
	 * @param int $uid
	 * @param int $sessionid
	 * @param int $type=31 通知类型
	 */
	function removeUser($uid, $sessionid, $fid, $type=301){
		if (self::_checkisSessionCreator($uid, $sessionid)) {
			if ($uid == $fid) return showData(new \stdClass(), '你是管理员,不能删除自己', 1);
			self::sendNotice($fid, $sessionid, $type, '[_]被管理员踢出了该会话');//发送通知
			if (M($this->sessionUser)->where(array('uid'=>$fid, 'sessionid'=>$sessionid))->delete()){
				return showData(new \stdClass(), '删除用户成功');
			}else {
				return showData(new \stdClass(), '删除失败', 1);
			}
		}else {
			return showData(new \stdClass(), '你不是管理员不能删除成员', 1);
		}
	}
	/**
	 * 编辑会话
	 * @param int $uid
	 * @param int $sessionid
	 * @param int $type=32 通知类型
	 */
	function editSession($uid, $sessionid, $type=302){
		if (self::_checkisSessionCreator($uid, $sessionid)) {
			$name = trim(I('name'));
			if (!$name) return showData(new \stdClass(), '请输入要修改的会话名称', 1);
			if ($this->where(array('id'=>$sessionid))->setField('name', $name)) {
				self::sendNotice($uid, $sessionid, $type, '管理员[_]修改了会话名称');//发送通知
				return showData(new \stdClass(), '修改成功');
			}else {
				return showData(new \stdClass(), '修改失败', 1);
			}
		}else {
			return showData(new \stdClass(), '你不是管理员,不能编辑会话名称', 1);	
		}
	}
	/**
	 * 设置消息是否接收
	 * @param unknown $uid
	 * @param unknown $sessionid
	 */
	function getmsg($uid, $sessionid){
		$db   = M($this->sessionUser);
		$data = array('uid'=>$uid, 'sessionid'=>$sessionid);
		if ($db->where($data)->getField('getmsg')) {
			if ($db->where($data)->setField('getmsg', 0)) {
				return showData(new \stdClass(), '取消设置成功');
			}else {
				return showData(new \stdClass(), '取消设置失败', 1);
			}
		}else {
			if ($db->where($data)->setField('getmsg', 1)) {
				return showData(new \stdClass(), '设置成功');
			}else {
				return showData(new \stdClass(), '设置失败', 1);
			}
		}
	}
	/**
	 * 管理员删除会话
	 * @param int $uid
	 * @param int $sessionid
	 * @param int $type=33 通知类型
	 */
	function deleteSession($uid, $sessionid, $type=303){
		if (self::_checkisSessionCreator($uid, $sessionid)) {
			$name = $this->where(array('id'=>$sessionid))->getField('name');
			self::sendNotice($uid, $sessionid, $type, '管理员[_]解散了会话['.$name.']');//发送通知
			if ($this->delete($sessionid)) {
				M($this->sessionUser)->where(array('sessionid'=>$sessionid))->delete();//删除成员表
				M('message')->where(array('typechat'=>300,'toid'=>$sessionid))->delete();//删除消息
				return showData(new \stdClass(), '删除成功');
			}else {
				return showData(new \stdClass(), '删除失败', 1);
			}
		}else {
			return showData(new \stdClass(), '你不是管理员，不能删除临时会话', 1);
		}
	}
	/**
	 * 用户会话列表
	 */
	function userSessionList($uid){
		$map['_string'] = 's.id IN(SELECT sessionid FROM `'.$this->tablePrefix.$this->sessionUser.'` WHERE uid='.$uid.')';
		$list = self::public_list($uid, $map, 0);
		$user = new UserModel();
		if ($list) {
			foreach ($list as $k=>$v){
				$where['_string'] = 'uid IN (SELECT uid from `'.$this->tablePrefix.$this->sessionUser.'` where sessionid='.$v['id'].')';
				//$userList = $user->public_list($uid, $where, 0);
				$userList = M('user')->field('uid, nickname, headsmall')->where($where)->select();
				foreach ($userList as $k1=>$v1){
					$nickname = M($this->sessionUser)->where(array('sessionid'=>$v['id'], 'uid'=>$v1['uid']))->getField('nickname');
					if ($nickname) {
						$userList[$k1]['nickname'] = $nickname;
					}
				}
				$list[$k]['list'] = $userList;
			}
		}
		return showData($list);
	}
	/**
	 * 设置用户自己的群昵称
	 * @param unknown $uid
	 */
	function setNickname($uid, $sessionid, $type=304){
		$nickname = trim(I('mynickname'));
		if (!$nickname) return showData(new \stdClass(), '请输入昵称', 1);
		$name = self::_getUserSessionNickname($uid, $sessionid);//得到修改之间的昵称
		$user = new UserModel();
		$info = $user->getUserName($uid);
		$str  = $name ? $name : $info['name'];
		if (M($this->sessionUser)->where(array('uid'=>$uid,'sessionid'=>$sessionid))->setField('nickname', $nickname)){
			self::sendNotice($uid, $sessionid, $type, '用户'.$str.'修改在自己的昵称为['.$nickname.']');//发送通知
			return showData(new \stdClass(), '设置成功');
		}else {
			return showData(new \stdClass(), '设置失败', 1);
		}
	}
	/**
	 * 用户加入群
	 * @param unknown $uid
	 * @param unknown $sessionid
	 */
	function joinSession($uid, $sessionid, $type=305){
		$count = M($this->sessionUser)->where(array('sessionid'=>$sessionid, 'uid'=>$uid))->count();
		if ($count) {
			return showData(new \stdClass(), '你已是该群成员', 1);
		}else {
			$data = array(
					'sessionid'  => $sessionid,
					'uid'		 => $uid,
					'addtime'	 => NOW_TIME,
			);
			$name = $this->where(array('id'=>$sessionid))->getField('name');
			self::sendNotice($uid, $sessionid, $type, '用户[_]加入会话'.$name);
			if (M($this->sessionUser)->add($data)){
				return self::detail($uid, $sessionid);
			}else {
				return showData(new \stdClass(), '加入失败', 1);
			}
		}
	}
}