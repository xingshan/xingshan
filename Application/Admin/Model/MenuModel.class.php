<?php
namespace Admin\Model;
use Think\Model;
class MenuModel extends Model {
	protected $tableName = 'menu';
	protected $pk        = 'id';
	
	function _initialize(){
		parent::_initialize();
		$this->admin_role_priv_db = M('admin_role_priv');
	}
	
	/**
	 * 当前位置
	 * @param $id 菜单id
	 */
	public function currentPos($id) {
		$r = $this->where(array('id'=>$id))->find(array('id','name','parentid'));
		$str = '';
		if($r['parentid']) {
			$str = $this->currentPos($r['parentid']);
		}
		return $str.$r['name'].' &gt; ';
	}
	
	/**
	 * 按父ID查找菜单子项
	 * @param integer $parentid   父菜单ID  
	 * @param integer $with_self  是否包括他自己
	 */
	public function getMenu($parentid, $with_self = 0) {
		$parentid = intval($parentid);
		$result = $this->where(array('parentid'=>$parentid,'display'=>1))->order('`listorder` ASC')->limit(1000)->select();
		if (!is_array($result)) $result=array();
		if($with_self) {
			$result2[0] = $this->where(array('id'=>$parentid))->find();
			$result = array_merge($result2,$result);
		}
		
		//权限检查
		if(session('roleid') == 1) return $result;
		$array = array();
		foreach($result as $v) {
			$action = $v['a'];
			if(preg_match('/^public_/',$action)) {
				$array[] = $v;
			} else {
				if(preg_match('/^ajax_(\w+)_/',$action,$_match)) $action = $_match[1];
				$r = $this->admin_role_priv_db->where(array('m'=>$v['m'],'a'=>$action,'roleid'=>session('roleid')))->find();
				if($r) $array[] = $v;
			}
		}
		return $array;
	}
	//菜单列表
	public function getTree($parentid = 0) {
		$field = array('id','`name`','listorder','`id` as `operateid`');
		$order = '`listorder` ASC,`id` DESC';
		$data = $this->field($field)->where(array('parentid'=>$parentid))->order($order)->select();
		if (is_array($data)){
			foreach ($data as &$arr){
				$arr['id_sort'] = implode('_', array($arr['id'], $arr['listorder']));
				//$arr['state']   = 'closed';
				$arr['children'] = $this->getTree($arr['id']);
			}
		}
		return $data;
	}
	
	//菜单下拉列表
	public function getSelectTree($parentid = 0){
		$field = array('id','`name` as `text`');
		$order = '`listorder` ASC,`id` DESC';
		$data = $this->field($field)->where(array('parentid'=>$parentid))->order($order)->select();
		if (is_array($data)){
			foreach ($data as &$arr){
				$arr['children'] = $this->getSelectTree($arr['id']);
			}
		}
		return $data;
	}
	
	//权限管理列表
	public function getRoleTree($parentid = 0, $roleid = 0){
		$field = array('id','`name` as `text`','m','a');
		$order = '`listorder` ASC,`id` DESC';
		$data = $this->field($field)->where(array('parentid'=>$parentid))->order($order)->select();
		if (is_array($data)){
			foreach ($data as &$arr){
				$arr['attributes']['parent'] = $this->getParentIds($arr['id'], 1);
				$arr['children'] = $this->getRoleTree($arr['id'], $roleid);
				if(is_array($arr['children']) && !empty($arr['children']) ){
					$arr['state'] = 'closed';
				}else{
					//勾选默认菜单
					$r = $this->admin_role_priv_db->where(array('m'=>$arr['m'],'a'=>$arr['a'],'roleid'=>$roleid))->find();
					if($r) $arr['checked'] = true;
				}
			}
		}
		return $data;
	}
	
	//获取菜单父级id
	public function getParentIds($id, $type = 0){
		$result = array();
		$parentid = $this->where(array('id'=>$id))->getField('parentid');
		if($parentid){
			$result[] = $parentid;
			$result[] = $this->getParentIds($parentid);
		}
		return $type ? $result : $parentid;
	}
	
	//检查菜单名称是否存在
	public function checkName($name){
		$name =  trim($name);
		if ($this->where(array('name'=>$name))->field('id')->find()){
			return true;
		}
		return false;
	}
	
	//清除菜单相关缓存
	public function clearCatche(){
		S('Menu_selectTree', null);	//菜单下拉列表缓存
		S('Menu_index', null);
	}
	
	// 清除某个模块的相关菜单
	public function delModuleMenus($modulename) {
		$result = $this->where(Array('module'=>$modulename))->delete();
		if($result)
			return true;
		
		return false;
	}
}