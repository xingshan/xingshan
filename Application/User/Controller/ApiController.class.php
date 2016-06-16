<?php
namespace User\Controller;
use Api\Controller\BaseApiController;
use User\Model\UserModel;
use Message\Model\MessageModel;
use User\Model\PushModel;
class ApiController extends BaseApiController {
	private $user_db;
	private $msg_db;
	private $mid;
	function _initialize(){
		parent::_initialize();
		if (!in_array(ACTION_NAME, array('regist','login'))) {
			$this->mid = $this->_initUser(I('uid'));
		}
		$this->user_db = new UserModel();
	}
	/**
	 * 聊天
	 */
	function sendMessage(){
		$msg = new MessageModel();
		$return = $msg->message($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 注册
	 */
	function regist(){
		$return = $this->user_db->regist();
		$this->jsonOutput($return);
	}
	/**
	 * 登陆
	 */
	function login(){
		$return = $this->user_db->login();
		$this->jsonOutput($return);
	}
	/**
	 * 编辑
	 */
	function edit(){
		$return = $this->user_db->edit($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 获取用户的详细信息
	 * 当是自己的时候直接获取用户资料，如果不是自己的时候就获取别人的资料
	 */
	function detail(){
		$fid = I('fuid');
		if ($fid) {
			$fid = $this->_initUser($fid);
		}else {
			$fid = 0;
		}
		$return = $this->user_db->user($this->mid, $fid);
		$this->jsonOutput($return);
	}
	/**
	 * 搜索用户
	 */
	function search(){
		$return = $this->user_db->search($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 用户的好友列表
	 */
	function friendList(){
		$return = $this->user_db->friendlist($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 申请加好友
	 */
	function applyAddFriend(){
		$fid 	= $this->_initUser(I('fuid'));
		$return = $this->user_db->applyAddFriend($this->mid, $fid);
		$this->jsonOutput($return);
	}
	/**
	 * 同意加好友
	 */
	function agreeAddFriend(){
		$fid 	= $this->_initUser(I('fuid'));
		$return = $this->user_db->agreeAddFriend($this->mid, $fid);
		$this->jsonOutput($return);
	}
	/**
	 * 拒绝加好友
	 */
	function refuseAddFriend(){
		$fid 	= $this->_initUser(I('fuid'));
		$return = $this->user_db->refuseAddFriend($this->mid, $fid);
		$this->jsonOutput($return);
	}
	/**
	 * 删除好友
	 */
	function deleteFriend(){
		$fid 	= $this->_initUser(I('fuid'));
		$return = $this->user_db->deleteFriend($this->mid, $fid);
		$this->jsonOutput($return);
	}
	/**
	 * 设置备注名
	 */
	function remark(){
		$fid 	= $this->_initUser(I('fuid'));
		$return = $this->user_db->userRemark($this->mid, $fid);
		$this->jsonOutput($return);
	}
	/**
	 * 添加 删除黑名单
	 */
	function black(){
		$fid 	= $this->_initUser(I('fuid'));
		$return = $this->user_db->black($this->mid, $fid);
		$this->jsonOutput($return);
	}
	/**
	 * 黑名单列表
	 */
	function blackList(){
		$return = $this->user_db->blackList($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 导入手机联系人
	 */
	function importContact(){
		$return = $this->user_db->importContact($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 新的朋友
	 */
	function newFriend(){
		$return = $this->user_db->newFriend($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 收藏
	 */
	function favorite(){
		$fid 	= $this->_initUser(I('fuid'));
		$return = $this->user_db->favorite($this->mid, $fid);
		$this->jsonOutput($return);
	}
	/**
	 * 收藏列表
	 */
	function favoriteList(){
		$return = $this->user_db->favoriteList($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 删除收藏
	 */
	function deleteFavorite(){
		$return = $this->user_db->deleteFavorite($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 设置星标朋友
	 */
	function setStar(){
		$fid 	= $this->_initUser(I('fuid'));
		$return = $this->user_db->setStar($this->mid, $fid);
		$this->jsonOutput($return);
	}
	/**
	 * 修改密码
	 */
	function editPassword(){
		$return = $this->user_db->editPassword($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 反馈意见
	 */
	function feedback(){
		$return = $this->user_db->feedback($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 设置加朋友时是否需要验证
	 */
	function setVerify(){
		$return = $this->user_db->setVerify($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 设置是否接收消息
	 */
	function setGetmsg(){
		$fid 	= $this->_initUser(I('fuid'));
		$return = $this->user_db->setGetmsg($this->mid, $fid);
		$this->jsonOutput($return);
	}
	/**
	 * 接收推送消息
	 */
	function addNoticeHostForIphone(){
		$push = new PushModel();
		$return = $push->addNoticeHostForIphone($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 取消接收推送消息
	 */
	function removeNoticeHostForIphone(){
		$push = new PushModel();
		$return = $push->removeNoticeHostForIphone($this->mid);
		$this->jsonOutput($return);
	}
}