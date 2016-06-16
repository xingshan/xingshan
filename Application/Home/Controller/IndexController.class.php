<?php
namespace Home\Controller;
use Think\Controller;
use Common\Tools\Pinyin;
class IndexController extends Controller {
	function _initialize() {
	}
	public function index() {
		header('Content-Type:text/html; charset=utf-8');
		$this->redirect('Admin/Index/Index');
		
	}
	function test(){
		$string = '"{\n  \"content\" : \"\ud83d\ude04\ud83d\ude03\ud83d\ude00\",\n  \"typefile\" : \"1\"\n}"';
		echo json_decode($string);
		die;
		$py = new Pinyin();
		echo $py->pinyin('在大111', 1, 1);
		
		/*
		$data[0] = array(
			'uid'	=> 1,
			'name'  => 'yangdong',
			'head'  => 'http://www.baidu.com',
		);
		$data[1] = array(
			'uid'	=> 2,
			'name'  => 'yangd2g',
			'head'  => 'http://www.baidu.com2',
		);
		foreach ($data as $k=>$v){
			$keys = array_keys($v) ;
			foreach ($keys as $v2){
				echo 'key:'.$v2 .'__'. $v[$v2] . '<br>';
			}
		} */
	}
	function add(){
		$image = upload('/Picture/share/', 0, 'share');
		p($image);
	}
}