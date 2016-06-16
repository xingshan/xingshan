<?php
namespace Meeting\Controller;
use Admin\Controller\CommonController;
use Meeting\Model\MeetingModel;
class MeetingController extends CommonController {
	protected $meeting_db;
	function _initialize() {
		parent::_initialize();
		$this->meeting_db = new MeetingModel();
	}
	/**
	 * 会议列表
	 * @param unknown $page
	 * @param number $rows
	 * @param unknown $search
	 */
	function index($page=1, $rows=10, $search=array()) {
		if (IS_POST) {
			$map   = array();
			if ($search) {
				if ($search['name']){
					$map['name'] = array('like','%'.$search['name'].'%');
				}
			}
			$limit = ($page - 1) * $rows . "," . $rows;
			$total = $this->meeting_db->where($map)->count();
			$list  = $total ? $this->meeting_db->public_list(0, $map, $limit) : array();
			foreach ($list as $key=>$value){
				//格式化时间
				$list[$key]['createtime'] = date('Y-m-d H:i', $value['createtime']);
				$list[$key]['start'] = date('Y-m-d H:i', $value['start']);
				$list[$key]['end'] = date('Y-m-d H:i', $value['end']);
			}
			$this->ajaxReturn(array('total'=>$total, 'rows'=>$list));
		}else {
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->display('list');
		}
	}
	/**
	 * 编辑
	 */
	function edit($id=0){
		if (IS_POST) {
			$data = I('info');
			if (!$data['content']) $this->error('请输入会议主题');
			if ($this->meeting_db->where(array('id'=>$id))->save($data) !== false) {
				$this->success('编辑成功');
			}else {
				$this->error('编辑失败');
			}
		}else {
			$this->info = $this->meeting_db->public_list(0, array('m.id'=>$id), 1);
			$this->rand = I('rand');
			$this->display('edit');
		}
	}
	/**
	 * 删除会议
	 */
	function delete($id=0, $uid=0){
		if ($this->meeting_db->where(array('id'=>$id))->delete()) {
			//删除成员表
			M('meeting_user')->where(array('meetingid'=>$id))->delete();
			//删除消息记录
			M('message')->where(array('typechat'=>500))->delete();
			$this->success('删除成功');
		}else {
			$this->error('删除失败');
		}
	}
	/**
	 * 上传头像
	 * @param unknown $uid
	 */
	function public_upload($uid=0){
		$data = upload('/Picture/meeting/', 0, 'meeting');
		if (is_string($data)) {
			$this->ajaxReturn(array('info'=>$data, 'status'=>0));
		}else {
			$this->ajaxReturn(array('info'=>'上传成功', 'url'=>$data['0']['smallUrl'], 'status'=>1));
		}
	}
}