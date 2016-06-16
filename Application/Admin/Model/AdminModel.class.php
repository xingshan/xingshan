<?php
namespace Admin\Model;
use Think\Model;
class AdminModel extends Model {
	protected $tableName = 'admin';
	protected $pk        = 'userid';
	/*
	 * 修改密码
	 */
	public function editPassword($userid, $password){
		$userid = intval($userid);
		if($userid < 1) return false;
		$passwordinfo = password($password);
		return $this->where(array('userid'=>$userid))->save($passwordinfo);
	}
	/*
	 * 检查用户名重名
	 */	
	public function checkName($username) {
		$username =  trim($username);
		if ($this->where(array('username'=>$username))->field('userid')->find()){
			return true;
		}
		return false;
	}
}