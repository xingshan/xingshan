<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;
use Admin\Model\AdminModel;
use Admin\Model\Admin_roleModel;
use Company\Model\CompanyModel;
class AdminController extends CommonController {
	function _initialize() {
		parent::_initialize();
		$this->admin_db = new AdminModel();
		$this->admin_role_db = new Admin_roleModel();
		$this->admin_role_priv_db = M('admin_role_priv');
		

		
	}
	//管理员列表
	public function index($sort = 'userid', $order = 'asc'){
	    $this->assign('roleid',session('roleid'));

		if(IS_POST){
			$total = $this->admin_db->count();
			$order = $sort.' '.$order;
			$list = $this->admin_db->table(C('DB_PREFIX').'admin A')
					->join(C('DB_PREFIX').'admin_role AR on AR.roleid = A.roleid')
					->field("A.userid,A.username,A.lastloginip,A.email,A.realname,A.phone,A.roleid,AR.rolename,A.company_id,FROM_UNIXTIME(A.lastlogintime, '%Y-%m-%d %H:%i:%s') as lastlogintime")
					->where(" A.roleid >=" .session('roleid'))
				    ->order($order)
					->select();
			
		
			$company = new CompanyModel();
			
			$company_data = $company->select();
			$company_data_size = count($company_data);
			
			for($i = 0;$i<count($list);$i++){			    
			    $isDel=0;
			    if($list[$i]['roleid']>session('roleid')){
			        $isDel=1;
			    }			    
			    $list[$i]['id_roleid'] =  $list[$i]['userid'].'_'.$isDel;
			    
			    //$list[$i]['companyname'] = $company_data[$i]['name'];
			    
			    
			    for($ii = 0;$ii<$company_data_size;$ii++){
			      
			        if($list[$i]['company_id']==$company_data[$ii]['id']){
			            $list[$i]['companyname'] = $company_data[$ii]['name'];
			            break;
			        }
			    }
			    
			    			    
			}
			
			
			
			$data = array('total'=>$total, 'rows'=>$list);
			$this->ajaxReturn($data);
		}else {
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->display('admin_list');
		}
	}
	
	//添加管理员
	public function add(){
	    $this->assign('roleid',session('roleid'));
	    $this->assign('company_id',session('company_id'));
		if(IS_POST){
			$data = I('info');
			$data['pwdconfirm'] = '';
			if($this->admin_db->where(array('username'=>$data['username']))->field('username')->find()){
				$this->error('管理员名称已经存在');
			}
			$passwordinfo = password($data['password']);
			$data['password'] = $passwordinfo['password'];
			$data['encrypt'] = $passwordinfo['encrypt'];
	
			$id = $this->admin_db->add(array_filter($data));
			if($id){
				$this->success('添加成功');
			}else {
				$this->error('添加失败');
			}
		}else {
			$rolelist = $this->admin_role_db->field('roleid,rolename')->where(array('disabled'=>'0'))->select();
			$this->assign('rolelist', $rolelist);
			$this->display('admin_add');
		}
	}
	
	//编辑管理员
	public function edit($id){
		if(IS_POST){
			$data = I('info');
			$data['pwdconfirm'] = '';
			if($data['password']){
				$passwordinfo = password($data['password']);
				$data['password'] = $passwordinfo['password'];
				$data['encrypt'] = $passwordinfo['encrypt'];
			}else{
				unset($data['password']);
			}
			
			$result = $this->admin_db->where(array('userid'=>$id))->save(array_filter($data));
			if($result){
				$this->success('修改成功');
			}else {
				$this->error('修改失败');
			}
		}else {
			$this->info = $this->admin_db->where(array('userid'=>$id))->find();
			$this->rolelist = $this->admin_role_db->field('roleid,rolename')->where(array('disabled'=>'0'))->select();
			$this->display('admin_edit');
		}
	}
	
	// 删除管理员
	public function delete($id) {
		if($id == '1') $this->error('该对象不能被删除');
		$result = $this->admin_db->where(array('userid'=>$id))->delete();
		if ($result){
			$this->success('删除成功');
		}else {
			$this->error('删除失败');
		}
	}
	
	//角色列表
	public function roleList($sort = 'listorder', $order = 'asc'){
		if(IS_POST){
			$total = $this->admin_role_db->count();
			$order = $sort.' '.$order;
			$list = $this->admin_role_db->field('*,roleid as id,concat(roleid,\'_\',listorder) as id_order')->order($order)->select();
			$data = array('total'=>$total, 'rows'=>$list);
			$this->ajaxReturn($data);
		}else {
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->display('role_list');
		}
	}
	
	//角色添加
	public function roleAdd(){
		if(IS_POST){
			$data = I('info');
			if($this->admin_role_db->where(array('rolename'=>$data['rolename']))->field('rolename')->find()){
				$this->error('角色名称已存在');
			}
			$id = $this->admin_role_db->add($data);
			if($id){
				$this->success('添加成功');
			}else {
				$this->error('添加失败');
			}
		}else {
			$this->display('role_add');
		}
	}
	
	//角色编辑
	public function roleEdit($id){
		if(IS_POST){
			$data = I('info');
			$id = $this->admin_role_db->where(array('roleid'=>$id))->save($data);
			if($id){
				$this->success('修改成功');
			}else {
				$this->error('修改失败');
			}
		}else {
			$info = $this->admin_role_db->where(array('roleid'=>$id))->find();
			$this->assign('info', $info);
			$this->display('role_edit');
		}
	}
	
	// 删除管理员
	public function roleDelete($id) {
		if($id == '1') $this->error('该对象不能被删除');
		$result = $this->admin_role_db->where(array('roleid'=>$id))->delete();
		if ($result){
			$this->success('删除成功');
		}else {
			$this->error('删除失败');
		}
	}
	
	//角色排序
	public function roleOrder(){
		if(IS_POST) {
			foreach(I('order') as $roleid=>$listorder) {
				$this->admin_role_db->where(array('roleid'=>$roleid))->save(array('listorder'=>$listorder));
			}
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}
	
	//权限设置
	public function roleSet($id){
		if(IS_POST) {
			//保存权限设置
			if (isset($_REQUEST['dosubmit'])){
				$this->admin_role_priv_db->where(array('roleid'=>$id))->delete();
				$menuids = explode(',', I('menuids'));				
				$menuids = array_unique($menuids);//移除重复的值
				$menuids = array_filter($menuids);//移除空值
				if(!empty($menuids)){
					$menuList = array();
					$menuinfo = $this->menu_db->field(array('id','m','a','data'))->select();
					foreach ($menuinfo as $v) $menuList[$v['id']] = $v;					
					foreach ($menuids as $menuid){
						$info = $menuList[$menuid];
						$info['roleid'] = $id;
						$info['id'] = '';
						$this->admin_role_priv_db->add(array_filter($info));
					}
				}
				$this->success('权限设置成功');
				//获取列表数据
			}else{
				$data = $this->menu_db->getRoleTree(0, $id);
				$this->ajaxReturn($data);
			}
		} else {
			$this->assign('id', $id);
			$this->display('role_set');
		}
	}
	
	//修改密码
	public function public_editPwd(){
		$userid = session('userid');
		if (IS_POST){
			$r = $this->admin_db->where(array('userid'=>$userid))->field('password,encrypt')->find();
			if(password(I('old_password'), $r['encrypt']) !== $r['password'] ) $this->error('旧密码输入错误');
			if(I('new_password')) {
				$state = $this->admin_db->editPassword($userid, I('new_password'));
				if(!$state) $this->error('密码修改失败');
			}
			$this->success('密码修改该成功,请使用新密码重新登录', U('Index/public_logout'));
		}else {
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->info = $this->admin_db->where(array('userid'=>$userid))->find();
			$this->display('public_password');
		}
	}
	
	//修改个人信息
	public function public_editInfo($info = array()){
		$userid = $_SESSION['userid'];
		if (IS_POST){
			$fields = array('email','realname');
			foreach ($info as $k=>$value) {
				if (!in_array($k, $fields)){
					unset($info[$k]);
				}
			}
			$state = $this->admin_db->where(array('userid'=>$userid))->save($info);
			$state ? $this->success('修改成功') : $this->error('修改失败');
		}else {
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->info = $this->admin_db->where(array('userid'=>$userid))->find();
			$this->display('public_info');
		}
	}
	
	//验证用户名
	public function public_checkName($name){
		if (I('default') == $name) {
			$this->error('用户名相同');
		}
		$exists = $this->admin_db->where(array('username'=>$name))->field('username')->find();
		if ($exists) {
			$this->success('用户名存在');
		}else{
			$this->error('用户名不存在');
		}
	}
	
	//验证密码
	public function public_checkPassword($password = 0){
		$userid = session('userid');
		$r = array();
		$r = $this->admin_db->where(array('userid'=>$userid))->field('password,encrypt')->find();
		if (password($password, $r['encrypt']) == $r['password'] ) {
			$this->success('验证通过');
		}else {
			$this->error('验证失败');
		}
	}
	
	//验证邮箱是否存在
	public function public_checkEmail($email = 0){
		if (I('default') == $email) {
			$this->error('邮箱相同');
		}
		$exists = $this->admin_db->where(array('email'=>$email,'userid'=>array('neq',session('userid'))))->field('email')->find();
		if ($exists) {
			$this->success('邮箱存在');
		}else{
			$this->error('邮箱不存在');
		}
	}
	
	//验证角色名称是否存在
	public function public_checkRoleName($rolename){
		if (I('default') == $rolename) {
			$this->error('角色名称相同');
		}
		$exists = $this->admin_role_db->where(array('rolename'=>$rolename))->field('rolename')->find();
		if ($exists) {
			$this->success('角色名称存在');
		}else{
			$this->error('角色名称不存在');
		}
	}
}