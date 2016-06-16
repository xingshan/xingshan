<?php
namespace Friend\Controller;
use Admin\Controller\CommonController;
use Friend\Model\FriendModel;
class FriendController extends CommonController {
	protected $friend_db;
	function _initialize(){
		parent::_initialize();
		$this->friend_db = new FriendModel();
	}
	/**
	 * 朋友圈列表
	 * @param number $page
	 * @param number $rows
	 * @param unknown $search
	 */
	function index($page=1, $rows=10, $search=array()){
	    
		if (IS_POST) {
			$map   = array();
			if ($search) {
				if ($search['name']){
					$map['f.content|u.nickname'] = array('like','%'.$search['name'].'%');
				}
			}
			$limit = ($page - 1) * $rows . "," . $rows;
			$join     = 'LEFT JOIN `'.C('DB_PREFIX').'user` u ON u.uid=f.uid';
			$total = $this->friend_db->alias('f')->join($join)->where($map)->count();
			$list  = $total ? $this->friend_db->public_list(0, $map, $limit) : array();
			foreach ($list as $key=>$value){
				//格式化时间
				$list[$key]['createtime'] = date('Y-m-d H:i', $value['createtime']);
			}
			$this->ajaxReturn(array('total'=>$total, 'rows'=>$list));
		}else {
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->display('list');
		}
	}
	/**
	 * 删除分享记录
	 * @param unknown $id
	 */
	function delete($id){
		if ($this->friend_db->delete($id)) {
			//收藏 直接把内容给写到收藏表
			M('friend_praise')->where(array('fsid'=>$fsid))->delete();//删除赞
			M('friend_reply')->where(array('fsid'=>$fsid))->delete();// 回复
			$this->success('删除成功');
		}else {
			$this->error('删除失败');
		}
	}
}