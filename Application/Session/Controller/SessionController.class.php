<?php
/**
 * 临时会话
 * @author yangdong
 *
 */
namespace Session\Controller;
use Admin\Controller\CommonController;
use Session\Model\SessionModel;
use User\Model\UserModel;
use Think\Model;
class SessionController extends CommonController {
	private $session_db;
	private $modelMe;
	
	function _initialize(){
		parent::_initialize();
		$this->session_db = new SessionModel();
		$this->modelMe = new Model();
	}
	/**
	 * 会话列表
	 * @param number $page
	 * @param number $rows
	 * @param unknown $search
	 */
	function index($page=1, $rows=10, $search = array()){
	    
		if (IS_POST) {
		   
			$map   = array();
			if ($search) {
				if ($search['name']){
					$map['name'] = array('like','%'.$search['name'].'%');
				}
			}
			$limit = ($page - 1) * $rows . "," . $rows;
			$total = $this->session_db->where($map)->count();
			$list  = $total ? $this->session_db->public_list(0, $map, $limit) : array();
			
			foreach ($list as $key=>$value){
				//格式化时间
				$list[$key]['createtime'] = date('Y-m-d H:i', $value['createtime']);
				
				$strNames="";
				
				$listUser = $this->modelMe->table("tc_session_user")->where(array("sessionid"=>$list[$key]['id']))->select();
				$list[$key]['countt']= count($listUser);
				
				foreach ($listUser as $i=>$v){
				    $strNames.=  $this->modelMe->table("tc_user")->where(array("sessionid"=>$listUser[$i]['uid']))->field("nickname")->find()['nickname'];
				}
				
				$list[$key]['names']= count($listUser);
				
			}
			$this->ajaxReturn(array('total'=>$total, 'rows'=>$list));
		}else {
		   // echo "<script>alert(1)</script>";
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->display('list');
		}
	}
	/**
	 * 编辑
	 * @param number $id
	 */
	function edit($id=0){
		if (IS_POST) {
			$return = $this->session_db->editSession(I('uid'), $id);
			if ($return['code']) {
				$this->error($return['msg']);
			}else {
				$this->success('修改成功');
			}
		}else {
			$this->info = $this->session_db->public_list(0, array('id'=>$id), 1);
			$this->rand = I('rand');
			$this->display('edit');
		}
	}
	/**
	 * 删除
	 * @param number $id
	 */
	function delete($id=0){
		$return = $this->session_db->deleteSession(I('uid'), $id);
		if ($return['code']) {
			$this->error($return['msg']);
		}else {
			$this->success($return['msg']);
		}
	}
}