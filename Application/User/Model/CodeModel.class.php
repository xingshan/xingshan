<?php
/**
 * 验证码
 * @author yangdong
 *
 */
namespace User\Model;
use Think\Model;
class CodeModel extends Model {
	protected $tableName 	= 'user_code';
	protected $pk        	= 'phone';
	
	/**
	 * 根据手机号获取验证码
	 * @return array
	 */
	function getCode(){
		$phone = I('phone');
		$code  = rand(100000, 999999);
		if (!$phone) return showData(new \stdClass(), '请输入手机号', 1);
		if ($this->where(array('phone'=>$phone))->count()) {
			$ret = $this->where(array('phone'=>$phone))->setField('code',$code);
			if ($ret) {
				return showData(array('code'=>$code));
			}else {
				return showData(new \stdClass(), '获取失败，请稍侯再试！', 1);
			}
		}else {
			if ($this->add(array('phone'=>$phone, 'code'=>$code))) {
				return showData(array('code'=>$code));
			}else {
				return showData(new \stdClass(), '获取失败，请稍侯再试！', 1);
			}
		}
	}
	/**
	 * 验证验证码
	 */
	function checkCode(){
		$phone = I('phone');
		$code  = I('code');
		if (!$phone) return showData(new \stdClass(), '请输入手机号', 1);
		if (!$code) return showData(new \stdClass(), '请输入验证码', 1);
		$info = $this->where(array('phone'=>$phone))->find();
		if ($info) {
			return showData(new \stdClass(), '验证成功');
		}else {
			return showData(new \stdClass(), '验证失败', 1);
		}
	}
}