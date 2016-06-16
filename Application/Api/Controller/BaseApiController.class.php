<?php
/**
 * API公共类
 * @author yangdong
 *
 */
namespace Api\Controller;
use Think\Controller;
class BaseApiController extends Controller {
	function _initialize(){
	}
	/**
	 * 初始化用户
	 * @param int $uid
	 * @return string
	 */
	function _initUser($uid){
		$count = M('user')->where(array('uid'=>$uid))->count();
		if ($count) {
			return $uid;
		}else {
			$data = showData(new \stdClass(),'该用户不存在',1);
			$this->jsonOutput($data);
		}
	}
	/**
	 * 输出json数据
	 * @param array $data
	 * @return string
	 */
	function jsonOutput($data){
		header('Content-Type: text/html; charset=utf-8');
		$status = array('code' => $data['code'],'msg'=> $data['msg']);
		if (APP_DEBUG) {
			$status['debugMsg'] = $data['debugMsg'];
			$status['url']      = MODULE_NAME.'/'.CONTROLLER_NAME . '/' . ACTION_NAME;
		}else {
			$status['debugMsg'] = '';
			$status['url']      = '';
		}
		$json = array('data' => $data['data'],'state' => $status);
		if ($data['page']){
			$newPage['total'] 		= $data['page']['total'];		//总条数
			$newPage['count'] 		= $data['page']['count'];		//返回记录的条数
			$newPage['pageCount'] 	= $data['page']['pageCount'];	//总页数
			$newPage['page'] 		= $data['page']['page'];		//当前页
			//如果下一页大于当前页
			if ($data['page']['page_next'] > $data['page']['page']) {
				$newPage['hasMore'] = 1;
			}else {
				$newPage['hasMore'] = 0;
			}
			$json['pageInfo'] = $newPage;
		}
		exit( json_encode($json) );
		//exit ( unicodeString(json_encode($json)) );
	}
	
}