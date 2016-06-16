<?php
namespace Message\Controller;
use Admin\Controller\CommonController;
use Message\Model\MessageModel;
class MessageController extends CommonController {
	private $message_db;
	function _initialize(){
		parent::_initialize();
		$this->message_db = new MessageModel();
	}
	/**
	 * 消息列表
	 */
	function index(){
		if (IS_POST) {
			;
		}else {
			$this->display('list');
		}
	}
}