<?php
/**
 * 版本更新管理
 * @author yangdong
 *
 */
namespace Version\Controller;
use Think\Upload;
use Admin\Controller\CommonController;
use Version\Model\VersionModel;
class VersionController extends CommonController {
	function _initialize() {
		parent::_initialize();
		$this->version_db = new VersionModel();
	}
	/**
	 * apk管理列表
	 * @param number $page
	 * @param number $rows
	 * @param unknown $search
	 */
	function apk($page=1, $rows=10, $search=array()){
		if (IS_POST) {
			$map['type'] = 'android';
			$limit = ($page - 1) * $rows . "," . $rows;
			$total = $this->version_db->where($map)->count();
			$list  = $total ? $this->version_db->_list($map, $limit) : array();
			if ($total) {
				foreach ($list as $k=>$v){
					$list[$k]['createtime'] = date('Y-m-d H:i',$v['createtime']);
				}
			}
			$this->ajaxReturn(array('total'=>$total, 'rows'=>$list));
		}else {
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->display('apk_list');
		}
	}
	/**
	 * 添加
	 */
	function add(){
		if (IS_POST) {
			$data = I('info');
			$data['type'] = 'android';
			$data['createtime'] = SYS_TIME;
			if ($this->version_db->add($data)) {
				$this->success('添加成功');
			}else {
				$this->error('添加失败');
			}
		}else {
			$this->uptype  = $this->public_updatetype();
			$this->display('apk_add');
		}
	}
	/**
	 * 编辑
	 * @param number $id
	 */
	function edit($id=0){
		if (IS_POST) {
			$data = I('info');
			if ($this->version_db->save($data) !== false) {
				$this->success('编辑成功');
			}else {
				$this->error('编辑失败');
			}
		}else {
			$this->info = $this->version_db->find($id);
			$this->uptype  = $this->public_updatetype();
			$this->display('apk_edit');
		}
	}
	/**
	 * 删除
	 * @param number $id
	 */
	function delete($id=0){
		if ($this->version_db->delete($id)) {
			$this->success('删除成功');
		}else {
			$this->error('删除失败');
		}
	}
	/**
	 * ios管理列表
	 * @param number $page
	 * @param number $rows
	 * @param unknown $search
	 */
	function ios($page=1, $rows=10, $search=array()){
		if (IS_POST) {
			$map['type'] = 'ios';
			$limit = ($page - 1) * $rows . "," . $rows;
			$total = $this->version_db->where($map)->count();
			$list  = $total ? $this->version_db->_list($map, $limit) : array();
			if ($total) {
				foreach ($list as $k=>$v){
					$list[$k]['createtime'] = date('Y-m-d H:i',$v['createtime']);
				}
			}
			$this->ajaxReturn(array('total'=>$total, 'rows'=>$list));
		}else {
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->display('ios_list');
		}
	}
	/**
	 * 添加
	 */
	function iosAdd(){
		if (IS_POST) {
			$data = I('info');
			$data['type'] = 'ios';
			$data['createtime'] = SYS_TIME;
			if ($this->version_db->add($data)) {
				$this->success('添加成功');
			}else {
				$this->error('添加失败');
			}
		}else {
			$this->uptype  = $this->public_updatetype();
			$this->display('ios_add');
		}
	}
	/**
	 * 编辑
	 * @param number $id
	 */
	function iosEdit($id=0){
		if (IS_POST) {
			$data = I('info');
			if ($this->version_db->save($data) !== false) {
				$this->success('编辑成功');
			}else {
				$this->error('编辑失败');
			}
		}else {
			$this->info = $this->version_db->find($id);
			$this->uptype  = $this->public_updatetype();
			$this->display('ios_edit');
		}
	}
	/**
	 * 升级类型
	 */
	function public_updatetype(){
		$data[0] = array('id'=>0, 'text'=>'正常升级');
		$data[1] = array('id'=>1, 'text'=>'警告升级');
		$data[2] = array('id'=>2, 'text'=>'强制升级');
		return $data;
	}
	/**
	 * 上传APK
	 */
	function public_upload(){		
		$URL_PATH = SITE_PROTOCOL.SITE_URL.UPLOADS;//物理根目录
		$upload = new Upload();
		$upload->exts = array('apk');// 设置附件上传类型
		$upload->rootPath  = SITE_DIR.UPLOADS;
		$upload->savePath  = '/Download/'; // 设置附件上传目录
		$upload->subName   = array('date','ymd');
		$upload->replace   = true;
		// 上传文件
		$info   =   $upload->upload();
		if(!$info) {// 上传错误提示错误信息
			$this->ajaxReturn(array('info'=>$upload->getError(),'status'=>0));
		}else{// 上传成功
			foreach($info as $file){
		        $url =  $file['savepath'].$file['savename'];
		    }
		    $this->ajaxReturn(array('info'=>'上传成功', 'url'=>$URL_PATH.ltrim($url,'/'), 'status'=>1));
		}
	}
}