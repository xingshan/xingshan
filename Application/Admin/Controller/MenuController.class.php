<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;
class MenuController extends CommonController {
	public function _initialize(){
		parent::_initialize();
	}
	//菜单列表
	function index(){
		if(IS_POST){
			if(S('Menu_index')){
				$data = S('Menu_index');
			}else{
				$data = $this->menu_db->getTree();
				S('Menu_index', $data);
			}
			$this->ajaxReturn($data);
		}else{
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->display('list');
		}
	}
	//添加
	function add($parentid = 0){
		if(IS_POST){
			$data = I('info');
			$data['display'] = $data['display'] ? '1' : '0';
			$id = $this->menu_db->add($data);
			if($id){
				$this->menu_db->clearCatche();
				$this->success('添加成功');
			}else {
				$this->error('添加失败');
			}
		}else{
			$this->assign('parentid', $parentid);
			$this->display();
		}
	}
	//编辑
	function edit($id = 0){
		if(!$id) $this->error('未选择菜单');
		if(IS_POST){
			$data = I('info');
			$data['display'] = $data['display'] ? '1' : '0';
			$result = $this->menu_db->where(array('id'=>$id))->save($data);
			if($result){
				$this->menu_db->clearCatche();
				$this->success('修改成功');
			}else {
				$this->error('修改失败');
			}
		}else{
			$data = $this->menu_db->where(array('id'=>$id))->find();
			$this->assign('data', $data);
			$this->display();
		}
	}
	//删除
	function delete($id = 0){
		if($id && IS_POST){
			$result = $this->menu_db->where(array('id'=>$id))->delete();
			if($result){
				$this->menu_db->clearCatche();
				$this->success('删除成功');
			}else {
				$this->error('删除失败');
			}
		}else{
			$this->error('删除失败');
		}
	}
	//排序
	function listorder(){
		if(IS_POST) {
			foreach(I('order') as $id => $listorder) {
				$this->menu_db->where(array('id'=>$id))->save(array('listorder'=>$listorder));
			}
			$this->menu_db->clearCatche();
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}
	
	//下拉列表
	function public_selectTree(){
		if(S('Menu_selectTree')){
			$data = S('Menu_selectTree');
		}else {
			$data = $this->menu_db->getSelectTree();
			$data = array(0=>array('id'=>0,'text'=>'作为一级菜单','children'=>$data));
			S('Menu_selectTree', $data);
		}
		$this->ajaxReturn($data);
	}
	
	//检查菜单名称是否存在
	function public_checkName($name){
		if (I('default') == $name) {
			$this->error('菜单名称相同');
		}
		$exists = $this->menu_db->checkName($name);
		if ($exists) {
			$this->success('菜单名称存在');
		}else{
			$this->error('菜单名称不存在');
		}
	}
}