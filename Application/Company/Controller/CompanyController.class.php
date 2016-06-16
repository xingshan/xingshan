<?php
namespace Company\Controller;
use Admin\Controller\CommonController;
use Company\Model\CompanyModel;
use Admin\Model\AdminModel;
class CompanyController extends CommonController {
    protected  $company_db;
    function _initialize() {
        parent::_initialize();
        
        $this->company_db=  new CompanyModel();
    }
    
    public function index($page=1, $rows=10, $search=array()){
       
        
    	if (IS_POST) {
    	  	//echo "<script>alert(1);</script>";
    	  	
    	    $map   = array();
			$map['deleted'] = 1;
			if ($search) {
				if ($search['name']){
					$map['name'] = array('like','%'.$search['name'].'%');
				}
			}
			$limit = ($page - 1) * $rows . "," . $rows;
		
			$total = count($this->company_db->where($map));		    
			
	       	$list  = $total ? $this->company_db->public_list(0, $map, $limit) : array();
	       	
	      
			$Admin = new AdminModel();
			
			foreach ($list as $key=>$value){
				//格式化时间
				$list[$key]['edittime'] = date('Y-m-d H:i', $value['edittime']);
	            
				$mapW = array();
				$mapW['deleted'] = 1;
				$mapW['roleid'] = 2;
				$mapW['company_id'] = $value['id'];
				
				$adminData = $Admin->where($mapW)->select();
				
				if(count($adminData)>0){
				    $list[$key]['adminname'] = $adminData[0]['username'];
				    $list[$key]['adminrealname'] = $adminData[0]['realname'];
				    $list[$key]['adminphone'] = $adminData[0]['phone'];
				}else{
				    $list[$key]['adminname'] = '-';
				    $list[$key]['adminphone'] = '-';
				    $list[$key]['adminrealname'] = "-";
				}
			}
			
			
			$this->ajaxReturn(array('total'=>$total, 'rows'=>$list));
			//$this->display('list');
		    
        }else {
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->display('list');
		

        }
    }
    
    public function add(){
        if (IS_POST) {
            //echo "<script>alert('b');</script>";
            $Admin = new AdminModel();
            $data = I('info');
      
            $name = $data['ausername'];
            $exists = $Admin->where(array('username'=>$name))->field('username')->find();
            if($exists){
                $this->error('超级管理员账号重复，请重新填写');
                return;
            }
            
            
            $arr = array();
            
            $arr['name']=$data['name'];
            $arr['address']=$data['address'];
            $arr['boss']=$data['boss'];
            $arr['phone']=$data['phone'];
            $arr['createtime']=NOW_TIME;
            $arr['createadmin']=session('realname');
            $arr['edittime']=NOW_TIME;
            $arr['editadmin']=session('realname');
            
            $id = $this->company_db->add($arr);
            
            if($id>0){
               
                
                
                $arr2 = array();
                
                $passwordinfo = password($data['apassword']);
               
                $arr2['username']=$data['ausername'];
                $arr2['password']=$passwordinfo['password'];
                $arr2['encrypt'] = $passwordinfo['encrypt'];
                $arr2['realname']=$data['arealname'];
                $arr2['phone']=$data['aphone'];
                $arr2['roleid'] = 2;
                $arr2['company_id'] = $id;
                $arr2['email'] = "-";
                
                
                $Admin->add($arr2);
                
                $this->success('添加成功');
                
            }else{
                $this->error('添加失败');
            }           
            /*
            if ($this->company_db->add($data) !== false) {
                $this->success('添加成功');
            }else {
                $this->error('添加失败');
            }
            */
            
            
            //$this->success($data['name']);
        }else{
            
            $this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
            $this->display('add');
        }
    }
    
    public function edit($id){
        if (IS_POST) {

            $data = I('info');
            $data['edittime'] = NOW_TIME;
            $data['editadmin'] = session('realname');
            $result = $this->company_db->where(array('id'=>$data['id']))->save(array_filter($data));
            if($result){
                $this->success('修改成功');
            }else {
                $this->error('修改失败');
            }
        }else{
    
            $this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
            $this->info = $this->company_db->where(array('id'=>$id))->find();
            $this->display('edit');
        }
    }
    
    public function delete($id=0){
        if ($this->company_db->where(array('id'=>$id))->delete()) {
            $this->success('删除成功');
        }else {
            $this->error('删除失败');
        }
    }
    
}