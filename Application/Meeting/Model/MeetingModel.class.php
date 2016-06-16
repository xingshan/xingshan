<?php
namespace Meeting\Model;
use Think\Model;
use Message\Core\Openfire;
use User\Model\UserModel;
class MeetingModel extends Model {
	protected $tableName = 'meeting';
	protected $pk		 = 'id';
	protected $meetingUser = 'meeting_user';
	
	function _initialize() {
		
	}
	/**
	 * 格式化返回数据
	 * @param unknown $list
	 * @param number $uid
	 */
	private function _format($list, $uid=0){
		$_list = array();
		if ($list) {
			foreach ($list as $k=>$v){
				$tmp = $v;
				$tmp['logolarge']	= str_replace('/s_', '/', $v['logo']);
				if (isset($v['role'])) {
					if (is_null($v['role'])) {	//表示用户与这个群没有任何的关系
						$tmp['isjoin'] = 0;		//未加入群
						$tmp['role']   = -1;	//没有角色
					}else {
						$tmp['isjoin'] = 1;				//加入了群
						$tmp['role']   = $v['role'];	//0-member 1-owner
					}
				}
				$_list[] = $tmp;
			}
		}
		return $_list;
	}
	/**
	 * 公共的分页
	 * @return array
	 */
	private function _list($uid, $map, $order){
		$total = $this->alias('m')->where($map)->count();
		if ($total) {
			$page  = page($total);
			$limit = $page['offset'] . ',' . $page['limit'];
		}else {
			$page  = '';
		}
		$list  = $total ? $this->public_list($uid, $map, $limit, $order) : array();
		return showData($list, '', 0, $page);
	}
	/**
	 * 公共列表
	 * @param unknown $uid
	 * @param unknown $map
	 * @param unknown $limit
	 * @param string $order
	 * @return Ambigous <mixed>|multitype:mixed
	 */
	function public_list($uid, $map, $limit, $order='createtime desc'){
		$creator  = '(SELECT nickname FROM `'.$this->tablePrefix.'user` WHERE uid=m.uid) as creator';
		$count    = '(SELECT COUNT(*) FROM `'.$this->tablePrefix.$this->meetingUser.'` WHERE meetingid=m.id and status=1) as memberCount';
		$applyCount    = '(SELECT COUNT(*) FROM `'.$this->tablePrefix.$this->meetingUser.'` WHERE meetingid=m.id and status=0) as applyCount';
		$join     = 'left join `'.$this->tablePrefix.$this->meetingUser.'` mu on mu.meetingid=m.id and mu.uid='.$uid.' and status=1';
		$field    = 'm.*, mu.role,'.$creator.','.$count.','.$applyCount;
		$list  	  = $this->alias('m')->field($field)->where($map)->join($join)->order($order)->limit($limit)->select();
		$_list 	  = $this->_format($list,$uid);
		if ($limit == 1) {
			return $_list['0'];
		}else {
			return $_list;
		}
	}
	/**
	 * 发送通知
	 * @param unknown $uid
	 * @param unknown $fid
	 * @param unknown $meetingid
	 * @param unknown $type
	 */
	private function sendNotice($uid, $fid, $meetingid, $type, $text=''){
		$openfire = new Openfire();
		$user	  = new UserModel();
		$userinfo = $user->getUserName($uid);//得到用户资料
		$to 	  = array('toid'=>$meetingid);
		$meeting  = $this->find($meetingid);
		switch ($type) {
			case 500:
				$to['touid'] = self::getMeetingCreator($meetingid);//群主
				$content = $userinfo['name'].'申请加入会议['.$meeting['name'].']';
				if ($text) $content .= ',理由：'.$text;
				$openfire->notice($userinfo, $to, $type, $content, $meeting);
				break;
			case 501:
				$to['touid'] = $fid;
				$content = '管理员通过了你加入会议['.$meeting['name'].']的申请';
				$openfire->notice($userinfo, $to, $type, $content, $meeting);
				break;
			case 502:
				$to['touid'] = $fid;
				$content = '管理员拒绝了你加入会议['.$meeting['name'].']的申请';
				$openfire->notice($userinfo, $to, $type, $content, $meeting);
			case 503:
				$to['touid'] = $fid;
				$content = '管理员邀请你加入会议['.$meeting['name'].']';
				return $openfire->notice($userinfo, $to, $type, $content, $meeting);
				break;
			case 504:
				$to['touid'] = self::getMeetingCreator($meetingid);
				$content = $userinfo['name'].'接受了你的邀请，加入了会议['.$meeting['name'].']';
				$openfire->notice($userinfo, $to, $type, $content, $meeting);
			case 505:
				$to['touid'] = self::getMeetingCreator($meetingid);
				$content = $userinfo['name'].'拒绝了加入会议['.$meeting['name'].']的邀请';
				$openfire->notice($userinfo, $to, $type, $content, $meeting);
				break;
			case 506:
				$to['touid'] = $fid;
				$content = '你被管理员从会议['.$meeting['name'].']中踢出了';
				$openfire->notice($userinfo, $to, $type, $content, $meeting);
				break;
			case 507:
				$uids = M($this->meetingUser)->field('uid')->where(array('status'=>1, 'meetingid'=>$meetingid, 'role'=>0))->select();
				if ($uids) {
					$touids = '';
					foreach ($uids as $k=>$v){
						$touids .= $v['uid'] . ',';
					}
					$touids = substr($touids, 0, -1);
					$to['touid'] = $touids;
					$content = $userinfo['name'].'被管理员从会议['.$meeting['name'].']中踢出了';
					$openfire->notice($userinfo, $to, $type, $content, $meeting);
				}
				break;
		}
	}
	/**
	 * 得到一个会议的创建者
	 * @param unknown $uid
	 * @param unknown $meetingid
	 */
	private function getMeetingCreator($meetingid){
		return $this->where(array('id'=>$meetingid))->getField('uid');
	}
	/**
	 * 检查用户是不是创建者
	 * @param unknown $uid
	 * @param unknown $meetingid
	 */
	private function checkIsCreator($uid, $meetingid){
		return M($this->meetingUser)->where(array('uid'=>$uid, 'meetingid'=>$meetingid))->getField('role');
	}
	/**
	 * 创建会议
	 * @param unknown $uid
	 */
	function addMeeting($uid){
		$logo = '';
		if (!empty($_FILES)) {
			$image = upload('/Picture/meeting/', 0, 'meeting');
			if (is_string($image)) {
				return showData(new \stdClass(), $image, 1);
			}else {
				$logo = $image['0']['smallUrl'];
			}
		}
		$data = array(
				'uid'		=> $uid,
				'name'		=> trim(I('name')),
				'logo'		=> $logo,
				'content'	=> trim(I('content')),//会议主题
				'start'		=> trim(I('start')),
				'end'		=> trim(I('end')),
				'createtime'=> NOW_TIME
		);
		//检测会议标题
		if ($data['name']) {
			if ($this->where(array('name'=>$data['name']))->count()) {
				return showData(new \stdClass(), '该会议标题已存在', 1);
			}
		}else {
			return showData(new \stdClass(), '请输入会议标题', 1);
		}
		//开始 结束时间
		if (!$data['start']) return showData(new \stdClass(), '请选择会议开始时间', 1);
		if (!$data['end']) return showData(new \stdClass(), '请选择会议结束时间', 1);
		//会议大纲
		if (!$data['content']) return showData(new \stdClass(), '请输入会议主题', 1);
		$meetingid = $this->add($data);
		if ($meetingid) {
			//加入会议
			$mUser = array(
					'uid'		=> $uid,
					'meetingid' => $meetingid,
					'role'		=> 1,
					'addtime' 	=> NOW_TIME,
					'status'	=> 1
			);
			M($this->meetingUser)->add($mUser);
			return self::detail($meetingid,$uid);
		}else {
			return showData(new \stdClass(), '创建失败', 1);
		}
	}
	/**
	 * 会议id
	 * @param unknown $meetingid
	 */
	function detail($meetingid, $uid){
		$info = self::public_list($uid, array('m.id'=>$meetingid), 1);
		return showData($info);
	}
	/**
	 * 会议列表
	 * @param int $uid
	 * @param int $type 1-进行中 2-往期 3-我的
	 */
	function meetingList($uid){
		$type  = I('type', 1, 'intval');//默认为进行中
		$order = '`start` asc';
		$map   = array();
		if ($type == 1) {
			$map['m.end']   = array('gt',NOW_TIME);
			$order = '`end` asc';
		}elseif ($type == 2){
			$map['m.end'] 	= array('lt',NOW_TIME);
		}else {
			$map['_string'] = 'm.id in (select meetingid from '.$this->tablePrefix.'meeting_user where uid='.$uid.')';
		}		
		$list = self::public_list($uid, $map, 0);
		return showData($list);
	}
	/**
	 * 检查用户在会议中的状态
	 */
	private function checkUserStatus($uid, $meetingid){
		$data = array('uid'=>$uid, 'meetingid'=>$meetingid);
		$info = M($this->meetingUser)->field('status')->where($data)->find();
		if ($info){
			return array('status'=>$info['status']);
		}else {
			return false;
		}
	}
	/**
	 * 申请加入会议 type=500
	 * @param int $uid 申请者
	 * @param int $meetingid
	 * @param int $type 500
	 * @return array|string
	 */
	function apply($uid, $meetingid, $type=500){
		
		$return = self::checkUserStatus($uid, $meetingid);//申请者用会议的关系
		if ($return) {
			if ($return['status']) {
				return showData(new \stdClass(), '你已加入了该会议', 1);
			}else {
				return showData(new \stdClass(), '你的申请暂未处理', 1);
			}
		}else {
			$content = trim(I('content'));
			if (!$content) return showData(new \stdClass(), '请输入申请理由', 1);
			$data = array('uid'=>$uid, 'meetingid'=>$meetingid, 'content'=>$content);
			$data['addtime'] = NOW_TIME;
			if (M($this->meetingUser)->add($data)){
				self::sendNotice($uid, 0, $meetingid, $type, $content);//发送通知
				return showData(new \stdClass(), '申请成功,请等待处理');
			}else {
				return showData(new \stdClass(), '申请失败', 1);
			}
		}
	}
	/**
	 * 同意申请
	 * @param unknown $uid 管理员
	 * @param unknown $fid 申请者
	 * @param unknown $meetingid
	 * @param number $type 501
	 */
	function agreeApply($uid, $fid, $meetingid, $type=501){
		if (self::checkIsCreator($uid, $meetingid)) {
			$return = self::checkUserStatus($fid, $meetingid);//申请者和会议的关系
			if ($return) {
				if ($return['status']) {
					return showData(new \stdClass(), '他已加入了该会议', 1);
				}else {
					$data = array('uid'=>$fid, 'meetingid'=>$meetingid);
					if (M($this->meetingUser)->where($data)->setField('status',1)) {
						self::sendNotice($uid, $fid, $meetingid, $type);//发送通知
						return showData(new \stdClass(), '添加成功');
					}else {
						return showData(new \stdClass(), '添加失败', 1);
					}
				}
			}else {
				return showData(new \stdClass(), '该用户并未发出申请', 1);
			}
		}else {
			return showData(new \stdClass(), '你不是管理员', 1);
		}
	}
	/**
	 * 不同意
	 * @param unknown $uid 会议管理员
	 * @param unknown $fid 申请者
	 * @param unknown $meetingid 会议id
	 * @param number $type 502
	 * @return array
	 */
	function disagreeApply($uid, $fid, $meetingid, $type=502){
		if (self::checkIsCreator($uid, $meetingid)) {
			$return = self::checkUserStatus($fid, $meetingid);
			if ($return) {
				if ($return['status']) {
					return showData(new \stdClass(), '该用户已加入了该会议', 1);
				}else {
					$data = array('uid'=>$fid, 'meetingid'=>$meetingid);
					if (M($this->meetingUser)->where($data)->delete()){
						self::sendNotice($uid, $fid, $meetingid, $type);//发送通知
						return showData(new \stdClass(), '处理成功');
					}else {
						return showData(new \stdClass(), '处理失败', 1);
					}
				}
			}else {
				self::sendNotice($uid, $fid, $meetingid, $type);//发送通知
				return showData(new \stdClass(), '处理成功');
			}
		}else {
			return showData(new \stdClass(), '你不是管理员', 1);
		}
	}
	/**
	 * 邀请用户加入会议
	 * @param unknown $uid	会议管理员
	 * @param unknown $fid	被邀请者
	 * @param unknown $meetingid 会议id
	 * @param number $type 503
	 * @return array
	 */
	function invite($uid, $fid, $meetingid, $type=503){
		if (self::checkIsCreator($uid, $meetingid)) {
			$uids 	  = trim(I('uids'));
			if (!$uids) return showData(new \stdClass(), '请选择要邀请的用户', 1);
			$uidArr   = explode(',', $uids);
			$dataList = array();
			foreach ($uidArr as $v){
				$return = self::checkUserStatus($v, $meetingid);//申请者和会议的关系
				if (!$return) {
					$dataList[] = array(
							'uid'		=> $v, 
							'meetingid'	=> $meetingid,
							'addtime'	=> NOW_TIME,
							'status'	=> 1,
					);
				}else {
					M($this->meetingUser)->where(array('uid'=>$v, 'meetingid'=>$meetingid))->setField('status', 1);
				}
			}
			M($this->meetingUser)->addAll($dataList);
			return showData(new \stdClass(), '邀请成功');
		}else {
			return showData(new \stdClass(), '你不是管理员,不能邀请用户', 1);
		}
	}
	
	// 不用
	/**
	 * 同意邀请加入会议
	 * @param unknown $uid 被邀请者
	 * @param unknown $meetingid
	 * @param number $type 504
	 */
	function agreeInvite($uid, $meetingid, $type=504){
		$return = self::checkUserStatus($uid, $meetingid);//申请者和会议的关系
		if ($return){
			if ($return['status']) {
				return showData(new \stdClass(), '你已加入了该会议', 1);
			}else {
				return showData(new \stdClass(), '已在申请列表中,等待处理', 1);
			}
		}else {
			$data = array('uid'=>$uid, 'meetingid'=>$meetingid);
			$data['addtime'] = NOW_TIME;
			if (M($this->meetingUser)->add($data)) {
				self::sendNotice($uid, 0, $meetingid, $type);
				return showData(new \stdClass(), '加入成功');
			}else {
				return showData(new \stdClass(), '加入失败', 1);
			}
		}
	}
	//不用
	/**
	 * 拒绝加入会议
	 * @param unknown $uid
	 * @param unknown $meetingid
	 * @param number $type
	 */
	function disagreeInvite($uid, $meetingid, $type=505){
		if (self::sendNotice($uid, 0, $meetingid, $type)){
			return showData(new \stdClass(), '发送请求成功');
		}else {
			return showData(new \stdClass(), '发送请求失败', 1);
		}
	}
	/**
	 * 会议用户申请列表
	 * @param unknown $meetingid
	 */
	function meetingApplyList($meetingid){
		$map['_string'] = 'uid in(select uid from `'.$this->tablePrefix.$this->meetingUser.'` where meetingid='.$meetingid.' and status=0)';
		$db    = M('user');
		$total = $db->where($map)->count(); 
		$field = 'uid, nickname, headsmall, (select content from `'.$this->tablePrefix.$this->meetingUser.'` where uid=u.uid and meetingid='.$meetingid.' and status=0) as content';
		$list  = $total ? $db->alias('u')->field($field)->where($map)->select() : array();
		return showData($list);
	}
	/**
	 * 用户活跃度
	 */
	function huoyue($meetingid){
		$map['_string'] = 'uid in(select uid from `'.$this->tablePrefix.$this->meetingUser.'` where meetingid='.$meetingid.' and status=1 and role=0)';
		$order = 'count desc';
		$db    = M('user');
		$total = $db->where($map)->count();
		$field = 'uid, nickname, headsmall, (select count(*) from `'.$this->tablePrefix.'message` where fromid=uid and typechat=500) as count';
		$list  = $total ? $db->field($field)->where($map)->order($order)->select() : array();
		return showData($list);
	}
	/**
	 * 移除用户
	 * @param unknown $uid 会议管理员
	 * @param unknown $fid 成员
	 * @param unknown $meetingid 会议id
	 * @param number $type 506
	 */
	function removeUser($uid, $fid, $meetingid, $type=506){
		if (self::checkIsCreator($uid, $meetingid)) {
			$data = array('uid'=>$fid, 'meetingid'=>$meetingid);
			if (M($this->meetingUser)->where($data)->delete()) {
				self::sendNotice($uid, $fid, $meetingid, $type);//被踢用户收到
				self::sendNotice($uid, $fid, $meetingid, $type+1);//所有用户收到
				return showData(new \stdClass(), '移除成功');
			}else {
				return showData(new \stdClass(), '移除失败', 1);
			}
		}else {
			return showData(new \stdClass(), '你不是管理员不能移除用户', 1);
		}
	}
}