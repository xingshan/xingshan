<?php
/**
 * 区域
 * @author yangdong
 * 创建时间：2014-8-6
 */
namespace User\Model;
use Think\Model;
class AreaModel extends Model {
	protected $tableName 	= 'area';
	protected $pk        	= 'areaid';
	/**
	 * 区域列表
	 * @param unknown $map
	 * @param unknown $limit
	 * @param string $order
	 * @param number $uid
	 * @return unknown
	 */
	function public_list($map, $limit, $order='`parentid` ASC, `vieworder` ASC'){
		$list  = $this->alias('a')->where($map)->order($order)->limit($limit)->select();
		if ($limit == 1) {
			return $list['0'];
		}else {
			return $list;
		}
	}
	/**
	 *  列表
	 * @return array
	 */
	function areaList(){
		$list = $this->public_list(array(), 0);
		return showData($list);
	}
}