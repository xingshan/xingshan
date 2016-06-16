<?php
namespace Friend\Controller;
use Api\Controller\BaseApiController;
use Friend\Model\FriendModel;
class ApiController extends BaseApiController {
	protected $friend_db;
	protected $mid;
	protected $fsid;
	
	function _initialize(){
		parent::_initialize();
		$this->mid = $this->_initUser(I('uid'));
		$this->friend_db = new FriendModel();
		if (!in_array(ACTION_NAME, array('add','shareList','userAlbum','favoriteList','setCover','setFriendCircleAuth'))) {
			 $fsid = I('fsid');
			if ($this->friend_db->where('id='.$fsid)->count()) {
				$this->fsid = $fsid;
			}else {
				$data = showData(new \stdClass(), '该分享不存在', 1);
				$this->jsonOutput($data);
			}
		}
	}
	/**
	 * 发布分享
	 */
	function add(){
		$return = $this->friend_db->shareAdd($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 删除分享
	 */
	function delete(){
		$return = $this->friend_db->shareDelete($this->mid, $this->fsid);
		$this->jsonOutput($return);
	}
	/**
	 * 分享详细
	 */
	function detail(){
		$return = $this->friend_db->shareDetail($this->mid, $this->fsid);
		$this->jsonOutput($return);
	}
	/**
	 * 朋友圈列表
	 */
	function shareList(){
		$return = $this->friend_db->shareList($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 用户相册
	 */
	function userAlbum(){
		$fuid = I('fuid', 0);
		if ($fuid) $fuid = $this->_initUser($fuid);
		$return = $this->friend_db->userAlbum($this->mid, $fuid);
		$this->jsonOutput($return);
	}
	/**
	 * 添加赞 取消赞
	 */
	function sharePraise(){
		$return = $this->friend_db->sharePraise($this->mid, $this->fsid);
		$this->jsonOutput($return);
	}
	//赞列表
	/**
	 * 回复
	 */
	function shareReply(){
		$fuid = $this->_initUser(I('fuid'));//被回复人
		$return = $this->friend_db->shareReply($this->mid, $fuid, $this->fsid);
		$this->jsonOutput($return);
	}
	/**
	 * 删除回复
	 */
	function deleteReply(){
		$return = $this->friend_db->deleteReply($this->mid);
		$this->jsonOutput($return);
	}
	//回复列表
	/**
	 * 设置朋友圈权限
	 */
	function setFriendCircleAuth(){
		$fuid = $this->_initUser(I('fuid'));
		$return = $this->friend_db->setFriendCircleAuth($this->mid, $fuid);
		$this->jsonOutput($return);
	}
	/**
	 * 设置朋友圈封面
	 */
	function setCover(){
		$return = $this->friend_db->setCover($this->mid);
		$this->jsonOutput($return);
	}
}

?>