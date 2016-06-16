<?php
/**
 * 临时会话接口类
 * @author yangdong
 *
 */
namespace Session\Controller;
use Api\Controller\BaseApiController;
use Session\Model\SessionModel;
class ApiController extends BaseApiController {
	private $session_db;
	private $sessionid;
	private $mid;
	
	function _initialize() {
		parent::_initialize();
		$this->mid		  = $this->_initUser(I('uid'));
		$this->session_db = new SessionModel();
		if (!in_array(ACTION_NAME, array('add','userSessionList'))) {
			 $sessionid = I('sessionid');
			if ($this->session_db->where('id='.$sessionid)->count()) {
				$this->sessionid = $sessionid;
			}else {
				$data = showData(new \stdClass(), '该会话不存在', 1);
				$this->jsonOutput($data);
			}
		}
	}
	/**
	 * 创建会话并加人
	 */
	function add(){
		$return = $this->session_db->sessionAdd($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 添加用户到会话
	 */
	function addUserToSession(){
		$return = $this->session_db->addUser($this->mid, $this->sessionid);
		$this->jsonOutput($return);
	}
	/**
	 * 可加入的联系人列表
	 */
	function contactList(){
		$return = $this->session_db->contactList($this->mid, $this->sessionid);
		$this->jsonOutput($return);
	}
	/**
	 * 会话详细
	 */
	function detail(){
		$return = $this->session_db->detail($this->mid, $this->sessionid);
		$this->jsonOutput($return);
	}
	/**
	 * 删除用户
	 */
	function remove(){
		$fid    = $this->_initUser(I('fuid'));//要删除的用户
		$return = $this->session_db->removeUser($this->mid, $this->sessionid, $fid);
		$this->jsonOutput($return);
	}
	/**
	 * 退出会话
	 */
	function quit(){
		$return = $this->session_db->quitSession($this->mid, $this->sessionid);
		$this->jsonOutput($return);
	}
	/**
	 * 编辑会话
	 */
	function edit(){
		$return = $this->session_db->editSession($this->mid, $this->sessionid);
		$this->jsonOutput($return);
	}
	/**
	 * 设置是否接收消息
	 */
	function getmsg(){
		$return = $this->session_db->getmsg($this->mid, $this->sessionid);
		$this->jsonOutput($return);
	}
	/**
	 * 管理删除会话
	 */
	function delete(){
		$return = $this->session_db->deleteSession($this->mid, $this->sessionid);
		$this->jsonOutput($return);
	}
	/**
	 * 用户群聊列表
	 */
	function userSessionList(){
		$return = $this->session_db->userSessionList($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 设置用户的群昵称
	 */
	function setNickname(){
		$return = $this->session_db->setNickname($this->mid, $this->sessionid);
		$this->jsonOutput($return);
	}
	/**
	 * 用户加入会话
	 */
	function join(){
		$return = $this->session_db->joinSession($this->mid, $this->sessionid);
		$this->jsonOutput($return);
	}
}