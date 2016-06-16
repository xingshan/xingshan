<?php
namespace Version\Model;
use Think\Model;
class VersionModel extends Model {
	protected $tableName = 'version';
	protected $pk        = 'id';
	
	function _list($map, $limit, $order='createtime desc'){
		return $this->where($map)->order($order)->limit($limit)->select();
	}
	/**
	 * 版本升级更新
	 * @return array();
	 */
	function getVersion(){
		$os	=	I('os');
		if(!in_array($os, array('ios','android'))){
			return showData(new \stdClass(), '更新失败', 1, '', '需要更新类型');
		}
		$version	=	I('version');
		if(empty($version)){
			return showData(new \stdClass(),'更新失败', 1, '' ,'需要当前版本号');
		}
		switch ($os){
			case 'ios':
				$ID	=	I('ID');
				if(empty($ID)){
					return showData(new \stdClass(),'更新失败', 1, '', '需要AppStore ID');
				}
				$file	=	file_get_contents('http://itunes.apple.com/lookup?id='.$ID);
				$result =	json_decode($file,true);
				if(empty($result['results'])){
					return  showData(new \stdClass(),'更新失败,稍后再试', 1,'','AppStore 审核未通过');
				}
				$ver	=	$result['results'][0]['version'];
				$url	=	$result['results'][0]['trackViewUrl'];
				$v		=	$this->order('id desc')->where(array('version'=>$ver,'type'=>'ios'))->find();
				if($ver > $version){
					$data = array (
							'hasNewVersion' => 1,
							'currVersion' 	=> $ver,
							'url' 			=> $url,
							'description' 	=> $v ['description'],
							'updateType' 	=> $v ['updatetype'] ? $v ['updatetype'] : 0
					);
					return showData($data);
				}else{
					$data = array (
							'hasNewVersion' => 0,
							'currVersion' 	=> '',
							'url' 			=> '',
							'description' 	=> '',
							'updateType' 	=> 0
					);
					return showData($data,'已经是最新版本了');
				}
				break;
			case 'android':
				$data = array (
				'hasNewVersion' => 0,
				'currVersion' 	=> '',
				'url' 			=> '',
				'description' 	=> '',
				'updateType' 	=> 0
				);
				$apk	=	$this->where(array('type'=>'android'))->order('id desc')->find();
				if ($apk) {
					if($apk['version'] > $version){
						$data['hasNewVersion'] = 1;
						$data['currVersion']   = $apk['version'];
						$data['url']		   = $apk['url'];
						$data['description']   = $apk['description'];
						$data['updateType']    = $apk['updatetype'];
						return showData($data);
					}else {
						return showData($data,'已经是最新版本了');
					}
				}else {
					return showData($data,'已经是最新版本了');
				}
				break;
		}
	}
}