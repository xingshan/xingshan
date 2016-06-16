<?php
namespace User\Controller;
use Admin\Controller\CommonController;
use User\Model\UserModel;
use Company\Model\CompanyModel;
class UserController extends CommonController {
	private $user_db;
	function _initialize(){
		parent::_initialize();
		$this->user_db = new UserModel();
	}
	/**
	 * 用户列表
	 * @param number $page
	 * @param number $rows
	 * @param array  $search
	 */
	function index($page=1, $rows=10, $search = array()){
		if (IS_POST){
			$map   = array();
			
			$map['deleted'] = 1;
			
			if(session('roleid')!=1){
			    $map['company_id'] = session('company_id');
			}
			
			
			if ($search) {
				if ($search['name']){
					$map['phone|nickname'] = array('like','%'.$search['name'].'%');
				}
			}
			$limit = ($page - 1) * $rows . "," . $rows;
			$total = $this->user_db->where($map)->count();
			$list  = $total ? $this->user_db->public_list(0, $map, $limit) : array();
			
			$company = new CompanyModel();
				
			$company_data = $company->select();
			$company_data_size = count($company_data);
			
			
			foreach ($list as $key=>$value){
				//格式化时间
				$list[$key]['createtime'] = date('Y-m-d H:i', $value['createtime']);
				$list[$key]['departname'] = "-";
				
			     for($ii = 0;$ii<$company_data_size;$ii++){
			      
			        if($list[$key]['company_id']==$company_data[$ii]['id']){
			            $list[$key]['companyname'] = $company_data[$ii]['name'];
			            break;
			        }
			    }
			}
			$this->ajaxReturn(array('total'=>$total, 'rows'=>$list));
		}else {
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->display('list');
		}
	}
	/**
	 * 编辑
	 * @param unknown $uid
	 */
	function edit($uid=0){
		if (IS_POST) {
			$data = I('info');
			if ($this->user_db->where(array('uid'=>$uid))->save($data)){
				$this->success('编辑成功');
			}else {
				$this->error('编辑失败');
			}
		}else {
			$this->info = $this->user_db->public_list($uid, array('u.uid'=>$uid), 1);
			$this->rand = I('rand');
			$this->display('edit');
		}
	}
	/**
	 * 删除
	 * @param unknown $uid
	 */
	function delete($uid){
		//$this->error('用户uid='.$uid);
		
		if($this->user_db->where(array('uid'=>$uid))->delete()){
		    $this->success('删除成功');
		}else{
		    $this->error('删除失败');
		}
		
	}
	/**
	 * 注册协议
	 */
	function regist(){
		if (IS_POST) {
			if (!trim(I('text'))) $this->error('请输入内容');
			if (S('User_regist', I('text'))) {
				$this->success('提交成功');
			}else {
				$this->error('提交失败');
			}
		}else {
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->text = '';
			if (S('User_regist')) $this->text = S('User_regist');
			$this->display('regist');
		}
	}
	/**
	 * 帮助中心
	 */
	function help(){
		if (IS_POST) {
			if (!trim(I('text'))) $this->error('请输入内容');
			if (S('User_help', I('text'))) {
				$this->success('提交成功');
			}else {
				$this->error('提交失败');
			}
		}else {
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->text = '';
			if (S('User_help')) $this->text = S('User_help');
			$this->display('help');
		}
	}
	/**
	 * 昵称相同
	 * @param unknown $email
	 */
	function public_checkNickname($nickname){
		if (I('default') == $nickname) {
			$this->error('昵称相同');
		}
		$exists = $this->user_db->where(array('nickname'=>$nickname))->field('nickname')->find();
		if ($exists) {
			$this->success('昵称存在');
		}else{
			$this->error('昵称不存在');
		}
	}
	/**
	 * 上传头像
	 * @param unknown $uid
	 */
	function public_uploadHead($uid=0){
		$data = upload('/Picture/avatar/', $uid, 'user');
		if (is_string($data)) {
			$this->ajaxReturn(array('info'=>$data, 'status'=>0));
		}else {
			$this->ajaxReturn(array('info'=>'上传成功', 'url'=>$data['0']['smallUrl'], 'status'=>1));
		}
	}
	
	function add(){
	    $this->ajaxReturn(array('info'=>'OK', 'url'=>"", 'status'=>1));
	}
}