<?php
namespace Admin\Controller;

/**
 * 授权管理
 * @author Administrator
 *
 */
class AuthController  {
	
	
	// 是否有权限
	function hasAuth() {
		return false;
	}
	
	// 无权限的提升
	function tipWithoutAuth() {
		return "you could register only 100 person";
	}
}