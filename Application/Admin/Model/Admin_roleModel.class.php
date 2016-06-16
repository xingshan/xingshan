<?php
namespace Admin\Model;
use Think\Model;
class Admin_roleModel extends Model {
	protected $tableName = 'admin_role';
	protected $pk        = 'roleid';
	
	/**
	 * 获取角色列表，键值为角色ID
	 */
	public function getList(){
		$arr=$this->order('`listorder` ASC')->select();
		$new_arr = array();
		foreach ($arr as $k=>$arr){
			$new_arr[$arr['roleid']]=$arr;
		}
		return $new_arr;
	}
	
	/**
	 * 检查角色名称重复
	 * @param $name 角色组名称
	 */
	public function checkName($name) {
		$info = $this->field('`roleid`')->where(array('rolename'=>$name))->find();
		if($info['roleid']){
			return true;
		}
		return false;
	}
	
	/**
	 * 获取角色中文名称
	 * @param int $roleid 角色ID
	 */
	public function getRoleName($roleid) {
		$roleid = intval($roleid);
		$info = $this->field('`rolename`')->where(array('roleid'=>$roleid))->find();
		return $info['rolename'];
	}
}