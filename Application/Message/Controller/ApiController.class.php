<?php
namespace Message\Controller;
use Api\Controller\BaseApiController;
use Message\Model\MessageModel;
class ApiController extends BaseApiController {
	private $mid;
	private $message_db;
	function _initialize() {
		parent::_initialize();
		$this->mid 			= $this->_initUser(I('uid'));
		$this->message_db 	= new MessageModel();
	}
	/**
	 * 发送消息
	 */
	function sendMessage(){
		$return = $this->message_db->message($this->mid);
		$this->jsonOutput($return);
	}
}