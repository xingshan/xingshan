<?php
/**
 * 消息类
 * @author yangdong
 *
 */
namespace Company\Model;
use Think\Model;


class CompanyModel extends Model {
	
    function _initialize() {
    
    }
    
    function public_list($uid, $map, $limit, $order='id'){
        $list = $this->where($map)->order($order)->limit($limit)->select();

        if ($limit == 1) {
            return $list['0'];
        }else {
            return $list;
        }
    }
    
}