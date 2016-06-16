<?php
namespace User\Controller;
use Think\Controller;
class IndexController extends Controller {

	public function _initialize(){
		parent::_initialize();

	}
	//首页
	function index() {
		$this->display('index');
	}
	
}