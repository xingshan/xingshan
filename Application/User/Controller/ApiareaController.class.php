<?php
namespace User\Controller;
use Api\Controller\BaseApiController;
use User\Model\AreaModel;
class ApiareaController extends BaseApiController {
	protected $area_db;
	function _initialize() {
		parent::_initialize();
		$this->area_db = new AreaModel();
	}
	/**
	 * 区域列表
	 */
	function areaList(){
		$return = $this->area_db->areaList();
		$this->jsonOutput($return);
	}
}