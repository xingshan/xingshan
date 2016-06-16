<?php
namespace Friend\Model;
use Think\Model;
use Message\Core\Openfire;
use User\Model\UserModel;
class FriendModel extends Model {
	protected $tableName 		= 'friend';
	protected $pk		 		= 'id';
	protected $friendPraise 	= 'friend_praise';  //赞
	protected $friendReply  	= 'friend_reply';	//回复
	protected $friendFavorite  	= 'friend_favorite';	//收藏
	
	function _initialize(){
		
	}
	/**
	 * 分享内容格式化
	 * @param array $list
	 * @param number $uid
	 * @return multitype:
	 */
	private function _format($list, $uid=0){
		$_list = array();
		if ($list) {
			foreach ($list as $k=>$v){
				$tmp['id']			= $v['id'];
				$tmp['uid']			= $v['uid'];
				$tmp['nickname']	= $v['nickname'];
				$tmp['headsmall']	= $v['headsmall'];
				if ($v['createtime'] >= 1410513900) {
					$tmp['content'] 	= json_decode($v['content']);
				}else {
					$tmp['content'] 	= $v['content'];
				}
				//图片
				if ($v['picture']){
					$tmp['picture'] = json_decode($v['picture'], true);
				}else {
					$tmp['picture'] = array();
				}
				$tmp['lng']			= $v['lng'];
				$tmp['lat']			= $v['lat'];
				$tmp['address']		= $v['address'];
				$tmp['praises']		= $v['praises'];
				$tmp['replys']		= $v['replys'];
				$tmp['ispraise']	= $v['ispraise'];
				$tmp['createtime'] 	= $v['createtime'];
				
				$_list[] = $tmp;
			}
		}
		return $_list;
	}
	/**
	 * 分享列表
	 * @param array $map
	 * @param int $uid
	 * @return array
	 */
	private function _list($map,$uid){
		$sinceID = I('sinceID', 0, 'intval');
		if ($sinceID) $map['f.id'] = array('gt',$sinceID);
		$maxID = I('maxID', 0, 'intval');
		if ($maxID) $map['f.id'] = array('lt',$maxID);
		//分页
		$total = $this->alias('f')->where($map)->count();
		if ($total){
			$page  = page($total);
			$limit = $page['offset'] . ',' . $page['limit'];
		}else {
			$page  = '';
		}
		$list  = $total ? $this->public_list($uid, $map, $limit) : array();
		return showData($list, '', 0, $page);
	}
	/**
	 * 朋友圈分享记录
	 * @param unknown $uid
	 * @param unknown $map
	 * @param unknown $limit
	 * @param string $order
	 */
	function public_list($uid, $map, $limit, $order='createtime desc'){
		//是否赞
		$ispraise = '(SELECT COUNT(*) FROM '.$this->tablePrefix.$this->friendPraise.' WHERE uid='.$uid.' and fsid=f.id) as ispraise';
		//获取赞的数量
		$praises  = '(SELECT COUNT(*) FROM '.$this->tablePrefix.$this->friendPraise.' WHERE ((uid IN(SELECT fid FROM `'.$this->tablePrefix.'user_friend` WHERE uid='.$uid.')) or (uid='.$uid.')) and fsid=f.id) as praises';
		//获取回复数量
		$replys   = '(SELECT COUNT(*) FROM '.$this->tablePrefix.$this->friendReply.' WHERE ((uid IN(SELECT fid FROM `'.$this->tablePrefix.'user_friend` WHERE uid='.$uid.')) or (uid='.$uid.')) and fsid=f.id) as replys';
		$join     = 'LEFT JOIN `'.$this->tablePrefix.'user` u ON u.uid=f.uid';
		$field 	  = 'f.*,u.nickname,u.headsmall,'.$ispraise.','.$praises.','.$replys;
		$list  	  = $this->alias('f')->field($field)->join($join)->where($map)->order($order)->limit($limit)->select();
		$_list 	  = $this->_format($list,$uid);
		if ($limit == 1) {
			return $_list['0'];
		}else {
			return $_list;
		}
	}
	/**
	 * 发送通知
	 */
	private function sendNotice($uid, $fsid, $type, $content, $table, $fid=0){
		$openfire = new Openfire();
		$user     = new UserModel();
		//接收通知的人 全是当前这个用户的好友
		//获取点赞的人 再把这当中的不是好友的排除掉
		$map['_string'] = '(uid in(select fid from `'.$this->tablePrefix.'user_friend` where uid='.$uid.' and fid<>'.$fid.')) and fsid='.$fsid.' and uid<>(select uid from `'.$this->tablePrefix.$this->tableName.'` where id='.$fsid.')' ;
		$list = M($table)->field('DISTINCT uid')->where($map)->select();
		$uids = '';
		if ($list) {
			foreach ($list as $k=>$v){
				$uids .= $v['uid'] . ',';
			}
		}
		$creatoruid = $this->where(array('id'=>$fsid))->getField('uid');//该条分享的发布者
		if ($uid == $creatoruid) {
			$uids .= $uid;
		}else {
			$uids .= $uid.','.$creatoruid;
		}
		if ($uids) {
			$to 	= array('toid'=>$fsid, 'touid'=>$uids);
			$from   = $user->getUserName($uid);
			$other['share']  = $this->find($fsid);
			$other['share']['content'] = json_decode($other['share']['content']);
			if ($fid) {
				$other['touser'] = $user->getUserName($fid);
			}
			return $openfire->notice($from, $to, $type, $content, $other);
		}else {
			return true;
		}
	}
	/**
	 * 用户最新的三张图片
	 */
	private function lastThreePicture($uid, $image){
		$count = count($image);
		$table = 'friend_user';
		$db    = M($table);
		switch ($count) {
			case 1:
				if ($db->where(array('uid'=>$uid))->count()) {
					$db->where(array('uid'=>$uid))->save(array('picture1'=>$image['0']['smallUrl']));
				}else {
					$db->add(array('uid'=>$uid, 'createtime'=>NOW_TIME, 'picture1'=>$image['0']['smallUrl']));
				}
				break;
			case 2:
				if ($db->where(array('uid'=>$uid))->count()) {
					$db->where(array('uid'=>$uid))->save(array('picture1'=>$image['0']['smallUrl'],'picture2'=>$image['1']['smallUrl']));
				}else {
					$db->add(array('uid'=>$uid, 'createtime'=>NOW_TIME, 'picture1'=>$image['0']['smallUrl'], 'picture2'=>$image['1']['smallUrl']));
				}
				break;
			case 3:
			case 4:
			case 5:
			case 6:
				if ($db->where(array('uid'=>$uid))->count()) {
					$db->where(array('uid'=>$uid))->save(array('picture1'=>$image['0']['smallUrl'],'picture2'=>$image['1']['smallUrl'],'picture3'=>$image['2']['smallUrl']));
				}else {
					$db->add(array('uid'=>$uid, 'createtime'=>NOW_TIME, 'picture1'=>$image['0']['smallUrl'], 'picture2'=>$image['1']['smallUrl'],'picture3'=>$image['2']['smallUrl']));
				}
				break;
			default:
				;
			break;
		}
	}
	/**
	 * 发布一条分享
	 * @param unknown $uid
	 */
	function shareAdd($uid){
		if (!empty($_FILES)) {
			if (count($_FILES) > 6) {
				return showData(new \stdClass(), '一次最多可以上传6张图片', 1);
			}
			$image = upload('/Picture/share/', 0, 'share');
			if (is_string($image)) {
				return showData(new \stdClass(), $image, 1);
			}else {
				array_multisort($image, SORT_DESC);
				$picture = json_encode($image);
			}
		}else {
			$picture = '';
		}
		//分享
		$data = array(
			'uid'		=> $uid,
			'content'	=> json_encode(trim(I('content', '', ''))),
			'picture'	=> $picture,
			'lng'		=> trim(I('lng')),
			'lat'		=> trim(I('lat')),
			'address'	=> trim(I('address')),
			'visible'	=> trim(I('visible')),
			'createtime'=> NOW_TIME,
		);
		if (($data['content'] == '') && ($data['picture'] == '')) return showData(new \stdClass(),'说点什么吧！', 1);
		if ($this->add($data)){
			if ($picture){
				self::lastThreePicture($uid, $image);
			}
			return showData(new \stdClass(), '分享成功');
		}else {
			return showData(new \stdClass(), '分享失败', 1);
		}
	}
	/**
	 * 一条分享详细
	 * @param unknown $uid
	 * @param unknown $fsid
	 */
	function shareDetail($uid, $fsid){
		$info = self::public_list($uid, array('f.id'=>$fsid), 1);
		$info['data']['replylist'] = self::replyList($uid, $fsid);
		$info['data']['praiselist'] = self::praiseList($uid, $fsid);
		return showData($info);
	}
	/**
	 * 分享删除
	 */
	function shareDelete($uid, $fsid){
		if ($this->where(array('uid'=>$uid, 'id'=>$fsid))->count()) {
			if ($this->delete($fsid)) {
				//收藏 直接把内容给写到收藏表
				M($this->friendPraise)->where(array('fsid'=>$fsid))->delete();//删除赞
				M($this->friendReply)->where(array('fsid'=>$fsid))->delete();// 回复
				return showData(new \stdClass(), '删除成功');
			}else {
				return showData(new \stdClass(), '删除失败，请稍侯再试！', 1);
			}
		}else {
			return showData(new \stdClass(), '这不是你的分享，删除失败', 1);
		}
	}
	/**
	 * 朋友圈列表
	 * @param unknown $uid
	 */
	function shareList($uid){
		$visible = "((f.visible='') and (f.uid in (SELECT fid FROM `".$this->tablePrefix."user_friend` WHERE uid=".$uid."))) or (f.visible like '%".$uid."%') or (f.uid = ".$uid.")";
		//不看他
		$string1 = 'f.uid in(SELECT fid FROM `'.$this->tablePrefix.'user_friend` WHERE uid='.$uid.' and fauth1=0)';
		//他不让我看
		$string2 = 'f.uid in(SELECT uid FROM `'.$this->tablePrefix.'user_friend` WHERE fid='.$uid.' and fauth2=0)';
		//朋友权限设定过滤
		$map['_string'] = '('.$visible.') and ('.$string1.') and ('.$string2.') or f.uid='.$uid;
		
		$list = self::_list($map, $uid);
		if ($list) {
			foreach ($list['data'] as $k=>$v){
				$list['data'][$k]['replylist'] = self::replyList($uid, $v['id']);
				$list['data'][$k]['praiselist'] = self::praiseList($uid, $v['id']);
			}
		}
		return $list;
	}
	/**
	 * 用户相册列表
	 * @param unknown $uid
	 * @param number $fuid
	 * @return array
	 */
	function userAlbum($uid, $fuid=0){
		$map['_string'] = "((f.visible='') and (f.uid in (SELECT fid FROM `".$this->tablePrefix."user_friend` WHERE uid=".$uid."))) or (f.visible like '%".$uid."%') or (f.uid = ".$uid.")";
		if ($fuid && $fuid != $uid) {
			$id = $fuid;
		}else {
			$id = $uid;
		}
		$map['f.uid']   = $id;
		return self::_list($map, $uid);
	}
	/**
	 * 朋友圈赞
	 * @param unknown $uid 用户id
	 * @param unknown $fsid 分享id
	 * @param int $type 400-通知类型
	 */
	function sharePraise($uid, $fsid, $type=400){
		$data = array('uid'=>$uid, 'fsid'=>$fsid);
		if (M($this->friendPraise)->where($data)->count()) {
			//已收赞 删除赞
			if (M( $this->friendPraise )->where($data)->delete()){
				self::sendNotice($uid, $fsid, $type+1, '取消赞', $this->friendPraise);//发送通知
				return showData(new \stdClass(), '取消赞成功');
			}else {
				return showData(new \stdClass(), '取消赞失败，请稍侯再试', 1);
			}
		}else {
			//未收赞 添加赞
			$data['createtime'] = NOW_TIME;
			if (M( $this->friendPraise )->add($data)) {
				self::sendNotice($uid, $fsid, $type, '添加赞', $this->friendPraise);//发送通知
				return showData(new \stdClass(), '赞成功');
			}else {
				return showData(new \stdClass(), '赞失败，请稍侯再试', 1);
			}
		}
	}
	/**
	 * 赞列表
	 * @param unknown $uid 当前用户
	 * @param unknown $fsid 分享id
	 */
	private function praiseList($uid, $fsid){
		$map['_string'] = '((fp.uid IN(SELECT fid FROM `'.$this->tablePrefix.'user_friend` WHERE uid='.$uid.')) or (fp.uid='.$uid.')) and fp.fsid='.$fsid;
		$join   = 'LEFT JOIN `'.$this->tablePrefix.'user` u ON u.uid=fp.uid';
		$list   = M($this->friendPraise)->alias('fp')->field('fp.*,u.nickname,u.headsmall')->join($join)->where($map)->order('fp.createtime desc')->select();
		$_names = array();
		if ($list) {
			foreach ($list as $k=>$v){
				$_names[] = array('uid'=>$v['uid'],'nickname'=>$v['nickname'],'headsmall'=>$v['headsmall']);
			}
		}
		return $_names;
	}
	/**
	 * 朋友圈回复
	 * @param number $uid 当前用户
	 * @param number $fid 当前用户评论给哪个用户
	 * @param number $fsid 分享id
	 * @param number $type 通知类型
	 */
	function shareReply($uid, $fid, $fsid, $type=402){
		$data = array(
				'uid'		=> $uid,
				'fid'		=> $fid,
				'fsid'		=> $fsid,
				'content'	=> json_encode(trim(I('content'))),
				'createtime'=> NOW_TIME
		);
		if (!$data['content']) return showData(new \stdClass(), '说点什么吧！', 1);
		if (M($this->friendReply)->add($data)) {
			self::sendNotice($uid, $fsid, $type, json_decode($data['content']), $this->friendReply, $fid);
			return showData(new \stdClass(), '回复成功');
		}else {
			return showData(new \stdClass(), '回复失败', 1);
		}
	}
	/**
	 * 删除回复
	 * @param unknown $uid	用户id
	 * @param unknown $replyid 回复Id
	 */
	function deleteReply($uid){
		$replyid = I('replyid');
		if (!$replyid) return showData(new \stdClass(), '未选择回复内容', 1);
		if (M($this->friendReply)->delete($replyid)) {
			return showData(new \stdClass(), '删除成功');
		}else {
			return showData(new \stdClass(), '删除失败', 1);
		}
	}
	/**
	 * 回复列表
	 * @param unknown $uid
	 * @param unknown $fsid
	 */
	private function replyList($uid, $fsid){
		$map['_string'] = '((fp.uid IN(SELECT fid FROM `'.$this->tablePrefix.'user_friend` WHERE uid='.$uid.')) or (fp.uid='.$uid.')) and fp.fsid='.$fsid;
		$nickname  = '(SELECT nickname FROM `'.$this->tablePrefix.'user` WHERE uid=fp.uid) as nickname';
		$fnickname = '(SELECT nickname FROM `'.$this->tablePrefix.'user` WHERE uid=fp.fid) as fnickname';
		//$field = 'fp.id,fp.uid,'.$nickname.',fp.fid,'.$fnickname.',fp.content,fp.createtime';
		$field = 'fp.id,fp.uid,u.nickname,u.headsmall,fp.fid,'.$fnickname.',fp.content,fp.createtime';
		$user  = 'LEFT JOIN '.$this->tablePrefix.'user u ON u.uid=fp.uid';
		$list = M($this->friendReply)->alias('fp')->field($field)->join($user)->where($map)->order('fp.createtime desc')->select();
		if ($list) {
			foreach ($list as $k=>$v){
				$list[$k]['content'] = json_decode($v['content']);
			}
			return $list;
		}else {
			return array();
		}
	}
	/**
	 * 设置朋友圈权限
	 * @param unknown $uid
	 * @param unknown $fid
	 * @param fauth 0-看 1-不看
	 */
	function setFriendCircleAuth($uid, $fid){
		$type = I('type', 1);
		$db   = M('user_friend');
		$map  = array('uid'=>$uid, 'fid'=>$fid);
		//2.不让他（她）看我的朋友圈
		if ($type == 2) {
			$set = $db->where($map)->getField('fauth2');
			if ($set){
				$ret = $db->where($map)->setField('fauth2', 0);
			}else {
				$ret = $db->where($map)->setField('fauth2', 1);
			}
		}else {
		//1.不看他（她）的朋友圈
			$set = $db->where($map)->getField('fauth1');
			if ($set) {
				$ret = $db->where($map)->setField('fauth1', 0);
			}else {
				$ret = $db->where($map)->setField('fauth1', 1);
			}
		}
		if ($ret) {
			return showData(new \stdClass(), '设置成功');
		}else {
			return showData(new \stdClass(), '设置失败', 1);
		}
	}
	/**
	 * 设置朋友圈封面
	 */
	function setCover($uid){
		if (!empty($_FILES)) {
			$image = upload('/Picture/cover/', 0, 'cover');
			if (is_string($image)) {
				return showData(new \stdClass(), $image, 1);
			}else {
				$data = array('cover'=>$image['originUrl']);
				if (M('friend_user')->where(array('uid'=>$uid))->count()) {
					if (M('friend_user')->where(array('uid'=>$uid))->save($data)) {
						return showData($data, '设置成功');
					}else {
						return showData(new \stdClass(), '设置失败', 1);
					}
				}else {
					$data['uid'] = $uid;
					if (M('friend_user')->add($data)) {
						return showData($data, '设置成功');
					}else {
						return showData(new \stdClass(), '设置失败', 1);
					}
				}
			}
		}else {
			return showData(new \stdClass(), '请上传一张图片', 1);
		}
	}
}