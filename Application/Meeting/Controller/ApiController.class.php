<?php
namespace Meeting\Controller;
use Meeting\Model\MeetingModel;
use Api\Controller\BaseApiController;
class ApiController extends BaseApiController {
	protected $meeting_db;
	protected $mid;
	protected $meetingid;
	function _initialize() {
		parent::_initialize();
		$this->mid = $this->_initUser(trim(I('uid')));
		$this->meeting_db = new MeetingModel();
		if (!in_array(ACTION_NAME, array('add','meetingList'))) {
			$id = $this->meeting_db->where(array('id'=>trim(I('meetingid'))))->getField('id');
			if ($id) {
				$this->meetingid = $id;
			}else {
				$this->jsonOutput(showData(new \stdClass(), '该会议不存在', 1));
			}
		}
	}
	/**
	 * 创建会议
	 */
	function add(){
		$return = $this->meeting_db->addMeeting($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 会议详细
	 */
	function detail(){
		$return = $this->meeting_db->detail($this->meetingid, $this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 会议列表
	 */
	function meetingList(){
		$return = $this->meeting_db->meetingList($this->mid);
		$this->jsonOutput($return);
	}
	/**
	 * 申请加入会议
	 */
	function apply(){
		$return = $this->meeting_db->apply($this->mid, $this->meetingid);
		$this->jsonOutput($return);
	}
	/**
	 * 同意加入会议
	 */
	function agreeApply(){
		$fid = $this->_initUser(trim(I('fuid')));
		$return = $this->meeting_db->agreeApply($this->mid, $fid, $this->meetingid);
		$this->jsonOutput($return);
	}
	/**
	 * 不同意申请加入会议
	 */
	function disagreeApply(){
		$fid = $this->_initUser(trim(I('fuid')));
		$return = $this->meeting_db->disagreeApply($this->mid, $fid, $this->meetingid);
		$this->jsonOutput($return);
	}
	/**
	 * 邀请入会
	 */
	function invite(){
		$return = $this->meeting_db->invite($this->mid, 0, $this->meetingid);
		$this->jsonOutput($return);
	}
	/**
	 * 同意邀请
	 */
	function agreeInvite(){
		$return = $this->meeting_db->agreeInvite($this->mid, $this->meetingid);
		$this->jsonOutput($return);
	}
	/**
	 * 不同意邀请
	 */
	function disagreeInvite(){
		$return = $this->meeting_db->disagreeInvite($this->mid, $this->meetingid);
		$this->jsonOutput($return);
	}
	/**
	 * 会议的用户申请列表
	 */
	function meetingApplyList(){
		$return = $this->meeting_db->meetingApplyList($this->meetingid);
		$this->jsonOutput($return);
	}
	/**
	 * 用户活跃度
	 */
	function huoyue(){
		$return = $this->meeting_db->huoyue($this->meetingid);
		$this->jsonOutput($return);
	}
	/**
	 * 踢出用户
	 */
	function remove(){
		$fid = $this->_initUser(trim(I('fuid')));
		$return = $this->meeting_db->removeUser($this->mid, $fid, $this->meetingid);
		$this->jsonOutput($return);
	}
}