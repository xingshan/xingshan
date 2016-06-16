<?php
namespace User\Controller;
use Think\Controller;
use User\Model\PushModel;
class PushController extends Controller {
	function ios(){
		$push = new PushModel();
		$push->pushtoios_offlinemsg();
	}
}