<?php
namespace Depart\Controller;
use Admin\Controller\CommonController;
use Depart\Model\DepartModel;
use Company\Model\CompanyModel;

class DepartController extends CommonController {
    protected  $depart_db;
    protected  $company_db;
    function _initialize() {
        parent::_initialize();
        
        $this->depart_db=  new DepartModel();
        $this->company_db = new CompanyModel();
    }
    
    public function index($page=1, $rows=10, $search=array()){            
    	if (IS_POST) {
    	    $map   = array();
			$map['deleted'] = 1;
			
			if ($search) {
				if ($search['name']){
					$map['name'] = array('like','%'.$search['name'].'%');
				}
				
				if($search['company'] && $search['company']!=0){
				    $map['company_id'] = $search['company'];
				}	
			}

			
			
			$limit = ($page - 1) * $rows . "," . $rows;		
			$total = count($this->depart_db->where($map));		    
	       	$list  = $total ? $this->depart_db->public_list(0, $map, $limit) : array();
	       	
	       	if(count($list)>0){
	       	
	       	foreach ($list as $key=>$value){
				//格式化时间
				$list[$key]['edittime'] = date('Y-m-d H:i', $value['edittime']);
            }
	       	}
			$this->ajaxReturn(array('total'=>$total, 'rows'=>$list));
			
			
        }else {
            
            $selectCompanyList="";
            
            $list = $this->company_db->where(array("deleted"=>1))->select();
            
            foreach ($list as $i=>$value){
                $selectCompanyList.='<option value="'.$list[$i]['id'].'"  >'.$list[$i]['name'].'</option>';
            }
            
            
            $this->assign("selectCompanyList",$selectCompanyList);
            $this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->display('list');
        }
    }
    
    public function add(){
        if (IS_POST) {
            //$this->success('添加成功');
        }else{
            $reStr = '';
            if(session('roleid')!=1){
                $this->assign("company_id",session("company_id"));
                $selectStr = "<select><option>".session(("company_name"))."</option></select>".$this->getDepartList(0,session("company_id")); 
                 
            }else{                
                $list = $this->company_db->where(array("deleted"=>1))->select();
                foreach ($list as $i=>$value){
                      $reStr.='<option value="'.$list[$i]['id'].'"  >'.$list[$i]['name'].'</option>';
                }
                
                $selectStr = "<select onchange='companyChange(this);'><option value='0'>未选择</option>".$reStr."</select>";
                
            } 
            $this->assign("selectStr",$selectStr);            
            $this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
            $this->display('add');
        }
    }
    
    public function edit($id){
        if (IS_POST) {
            //$this->success('添加成功');
        }else{
            $reStr = '';
            if(session('roleid')!=1){
                $this->assign("company_id",session("company_id"));
                $selectStr = "<select><option>".session(("company_name"))."</option></select>".$this->getDepartList(0,session("company_id"));
                 
            }else{
                $list = $this->company_db->where(array("deleted"=>1))->select();
                foreach ($list as $i=>$value){
                    $reStr.='<option value="'.$list[$i]['id'].'"  >'.$list[$i]['name'].'</option>';
                }    
                $selectStr = "<select onchange='companyChange(this);'><option value='0'>未选择</option>".$reStr."</select>";    
            }
            $this->assign("tname",$this->depart_db->where(array("id"=>$id))->find()['name']);
            $this->assign("edit_id",$id);
            $this->assign("selectStr",$selectStr);
            $this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
            $this->display('edit');
        }
    }
    
    
    public function addinfo($parentId,$tname,$com){
        $arr = array();
        $arr['name']=$tname;
        $arr['parentId']=$parentId;
        $arr['remark']="-";
        $arr['edittime']=NOW_TIME;
        $arr['editadmin']=session("realname");
        $arr['parent_name']=$parentId==0?'':$this->depart_db->where(array("id"=>$parentId))->find()['name'];        
        $arr['company_id']=$com;
        $arr['company_name']=$this->company_db->where(array("id"=>$com))->find()['name'];        
        $id = $this->depart_db->add($arr);
        if($id>0){
            //$this->success('添加成功');
        }else{
           // $this->error('添加失败');
        } 
        $this->ajaxReturn(array('str'=>"abc"));
    }
    
    public function editinfo($id,$parentId,$tname,$com){
        $arr = array();
        $arr['name']=$tname;
        $arr['parentId']=$parentId;
        $arr['remark']="-";
        $arr['edittime']=NOW_TIME;
        $arr['editadmin']=session("realname");
        $arr['parent_name']=$parentId==0?'':$this->depart_db->where(array("id"=>$parentId))->find()['name'];
        $arr['company_id']=$com;
        $arr['company_name']=$this->company_db->where(array("id"=>$com))->find()['name'];
        $id2 = $this->depart_db->where(array("id"=>$id))->save(array_filter($arr));
        if($id2){
            //$this->success('修改成功');
        }else{
            //$this->error('修改失败');
        }
        $this->ajaxReturn(array('str'=>"abc"));
    }
    
    public function getcompany($company_id){
        $reStr='';
        $list = $this->company_db->where(array("deleted"=>1))->select();        
        foreach ($list as $i=>$value){
            if($list[$i]['id']==$company_id){
                $reStr.='<option value="'.$list[$i]['id'].'" selected="selected"  >'.$list[$i]['name'].'</option>';
            }else{
                $reStr.='<option value="'.$list[$i]['id'].'" >'.$list[$i]['name'].'</option>';
            }
        }
        $selectStr = "<select onchange='companyChange(this);'><option value='0'>未选择</option>".$reStr."</select>";        
        $selectStr .= $this->getDepartList(0,$company_id);        
        $this->ajaxReturn(array('str'=>$selectStr));
    }
    
    public function getdepart($id,$com){  
        
        $reStr2='';
        $list = $this->company_db->where(array("deleted"=>1))->select();
        
        foreach ($list as $i=>$value){
            if($list[$i]['id']==$com){
                $reStr2.='<option value="'.$list[$i]['id'].'" selected="selected"  >'.$list[$i]['name'].'</option>';
            }else{
                $reStr2.='<option value="'.$list[$i]['id'].'" >'.$list[$i]['name'].'</option>';
            }
        }
        $selectStr = "<select onchange='companyChange(this);'><option value='0'>未选择</option>".$reStr2."</select>";
        
        
        $str =  $selectStr.$this->getDepartList($id,$com);       
        $this->ajaxReturn(array('str'=>$str));
    }
    
    function getDepartList($parentid,$com=0,$nowid=0){ 
        $reStr='';
        $one = $this->depart_db->where(array('id'=>$parentid))->find();
        $reStr.=$this->getDepartListChild($parentid,$com,$nowid);
        if($parentid!=0){ 
            $reStr=$this->getDepartList($one['parentId'],$com,$parentid).$reStr;
        }
        return $reStr;
    }

    
    /**
     * 获得当前分类的子集
     * */
    function getDepartListChild($parentid,$com,$nowid=0){
        $reStr='';
        
        $map = array();
        $map['deleted']=1;
        $map['parentId']=$parentid;
        $map['company_id']=$com;
        
        $listD = $this->depart_db->where($map)->select();
        
        $listDSize = count($listD);
        
        if($listDSize>0){
            $reStr.='<br/><select onchange="departChange(this);"><option value="'.$parentid.'">未选择</option>';
            foreach ($listD as $i=>$value){
                if($listD[$i]['id']==$nowid){
                    $reStr.='<option value="'.$listD[$i]['id'].'"  selected="selected" >'.$listD[$i]['name'].'</option>';
                }else{
                    $reStr.='<option value="'.$listD[$i]['id'].'"  >'.$listD[$i]['name'].'</option>';
                }         
            }
            $reStr.="</select>";
        }
        return $reStr;
    }
    
    public function delete($id=0){
        if ($this->depart_db->where(array('id'=>$id))->delete()) {
            $this->success('删除成功');
        }else {
            $this->error('删除失败');
        }
    }

}