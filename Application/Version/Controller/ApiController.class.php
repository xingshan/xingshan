<?php
/**
 * 版本升级API
 * @author yangdong
 *
 */
namespace Version\Controller;
use Api\Controller\BaseApiController;
use Version\Model\VersionModel;
class ApiController extends BaseApiController {
	function _initialize() {
		parent::_initialize();
	}
	function update(){
		$version = new VersionModel();
		$return  = $version->getVersion();
		$this->jsonOutput($return); 
	}
}