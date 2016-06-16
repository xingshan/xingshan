<?php
namespace User\Controller;
use Api\Controller\BaseApiController;
use User\Model\AreaModel;
use User\Model\CodeModel;
class ApiotherController extends BaseApiController {
	protected $area_db;
	function _initialize() {
		parent::_initialize();
		$this->area_db = new AreaModel();
	}
	/**
	 * 区域列表
	 */
	function areaList(){
		$area 	= new AreaModel();
		$return = $area->areaList();
		$this->jsonOutput($return);
	}
	/**
	 * 获取验证码
	 */
	function getCode(){
		$code   = new CodeModel();
		$return = $code->getCode();
		$this->jsonOutput($return);
	}
	/**
	 * 验证验证码
	 */
	function checkCode(){
		$code	= new CodeModel();
		$return = $code->checkCode();
		$this->jsonOutput($return);
	}
	/**
	 * 帮助中心
	 */
	function help(){
		$this->text = '';
		if (S('User_help')) $this->text = htmlspecialchars_decode(S('User_help'));
		$this->display('help');
	}
	/**
	 * 注册协议
	 */
	function regist(){
		$this->text = '';
		if (S('User_regist')) $this->text = htmlspecialchars_decode(S('User_regist'));
		$this->display('regist');
	}
}