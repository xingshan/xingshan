<?php
/**
 * openfire 交互类
 * @author yangdong
 * 创建时间: 2014-6-14
 * 通过userService插件的http方式与openfire通讯
 */
namespace Message\Core;
use Think\Log;
use Admin;
use Admin\Controller\AuthController;

class Openfire {
	private $OP_URL;	//openfire 访问地址
	private $secret = "7ih1mIbF";
	private $OP_AS  = 'beautyas';  //小助手id
	
	function __construct(){
		if (C('OP_HOST')) {
			$this->OP_URL = C('OP_HOST');
		}else {
			$this->OP_URL = 'http://localhost:9090';
		}
	}
	/**
	 * curl post 方法
	 * @param unknown $url
	 * @param unknown $string
	 * @return mixed
	 */
	function curl_post($url, $string){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
	
		curl_setopt($ch, CURLOPT_POSTFIELDS, $string);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
	/**
	 * 用户注册
	 * @param string|int $username
	 * @param string $password
	 * @return boolean|string 1-注册成功 0-注册失败 -1-用户已注册
	 */
	public function regist($username, $password){
		$url 	= $this->OP_URL . '/plugins/userService/userservice';
		$param 	= 'type=add&secret='.$this->secret.'&username='.$username.'&password='.$password;
		if (APP_DEBUG) Log::write($url.'?'.$param, 'DEBUG');
		$result = self::curl_post($url, $param);
		if (preg_match('/ok/', $result)) {
			return 1;
		}else if (preg_match('/UserAlreadyExistsException/', $result)){
			return -1;
		}else {
			return 0;
		}
	}
	/**
	 * 用户修改密码
	 * @param string|int $username
	 * @param string $password
	 * @return boolean|string 1-修改成功 0-修改失败 -1-用户不存在
	 */
	public function editPasswd($username, $password){
		$url 	= $this->OP_URL . '/plugins/userService/userservice';
		$param  = 'type=update&secret='.$this->secret.'&username='.$username.'&password='.$password;
		if (APP_DEBUG) Log::write($url.'?'.$param, 'DEBUG');
		$result = self::curl_post($url, $param);
		if (preg_match('/ok/', $result)) {
			return 1;
		}else if (preg_match('/UserNotFoundException/', $result)) {
			return -1;
		}else {
			return 0;
		}
	}
	/**
	 * 用户删除
	 * @param string|int $username
	 * @return boolean|string 1-删除成功 0-删除失败 -1-用户不存在
	 */
	public function delete($username){
		$url 	= $this->OP_URL . '/plugins/userService/userservice';
		$param  = 'type=delete&secret='.$this->secret.'&username='.$username;
		if (APP_DEBUG) Log::write($url.'?'.$param, 'DEBUG');
		$result = self::curl_post($url, $param);
		if (preg_match('/ok/', $result)) {
			return 1;
		}else if (preg_match('/UserNotFoundException/', $result)) {
			return -1;
		}else {
			return 0;
		}
	}
	/**
	 * 发送消息
	 * @param unknown $from	发送者
	 * @param unknown $to	接收者
	 * @param array $body	消息体
	 * @param string $action 类型 个人:person 所有人:allusers 所有在线用户:allonlineusers 指定用户:batchusers
	 */
	public function message($from, $to, $body, $action='batchusers'){
		header("Content-Type:text/html; charset=utf-8");
		$url 	= $this->OP_URL . '/plugins/sendmsg/sendservlet';
		$param 	= 'from='.$from.'&to='.$to.'&body='.urlencode(json_encode($body)).'&action='.$action;
		if (APP_DEBUG) Log::write($url.'?'.$param, 'DEBUG');
		$result = self::curl_post($url, $param);
		if (preg_match('/ok/', $result)) {
			return true;
		}else {
			return false;
		}
	}
	/**
	 * 通知
	 * @param unknown $from 发送者
	 * @param unknown $to	接收者
	 * @param number $type	消息类型
	 * @param number $tid
	 * @param string $content
	 * @param unknown $other
	 */
	public function notice($from, $to, $type=1, $content='',$other=''){
		//通知消息体
		$notice = array(
				'user' 	 => $from,
				'content'=> $content,
				'type' 	 => $type,
				'other'   => $other ? $other :new \stdClass(),
				'time'	 => getMillisecond()
		);
		$result = self::message($this->OP_AS, $to['touid'], $notice);
		if (APP_DEBUG){//debug模式下写入数据库
			$data = array(
					'type'		=> $type,
					'uid'		=> $from['uid'],
					'toUid'		=> $to['toid'],
					'content'	=> $notice['content'],
					'createtime'=> NOW_TIME,
					'status'	=> $result ? 1 : 0
			);
			M('notice')->add($data);
		}
		if ($result) {
			return true;
		}else {
			return false;
		}
	}
	
	/**
	 * 检测授权
	 */
	private function checkAuth() {
		
		$auth = new AuthController();
		if(!$auth)
			die("auth fail!");
		
		if($auth->hasAuth() == false)
			die($auth->tipWithoutAuth());
	}
}