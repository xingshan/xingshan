<?php
/**
 * 消息类
 * @author yangdong
 *
 */
namespace Message\Model;
use Think\Model;
use Think\Upload;
use Think\Image;
use Message\Core\Openfire;
class MessageModel extends Model {
	protected $tableName   = 'message';
	protected $pk          = 'id';
	protected $groupUser   = 'group_user';
	protected $sessionUser = 'session_user';
	protected $meetingUser = 'meeting_user';
	//消息文件类型
	const text 			= 1; //文本
	const image 		= 2; //图片
	const voice 		= 3; //语音
	const location 		= 4; //位置
	//消息聊天类型
	const singleChat	= 100;//单聊
	const groupChat		= 200;//群聊
	const sessionChat	= 300;//会话
	const meetingChat	= 500;//会议
	/**
	 * 聊天接口
	 */
	function message($uid){
		$typechat  = trim(I('typechat')) ? trim(I('typechat')) : 100;//聊天类型
		$typefile  = trim(I('typefile')) ? trim(I('typefile')) : 1;//上传文件类型
		$fromid    = $uid;		//发送者
		$from 	   = array('id'=>$fromid,'name'=>trim(I('fromname')),'url'=>trim(I('fromurl'))); //发送者的护展信息
		$toid      = I('toid');	//接收者
		$to 	   = array('id'=>$toid,'name'=>trim(I('toname')),'url'=>trim(I('tourl')));	//接收者的扩展信息
		$content   = trim(I('content','',''));//消息内容
		$imageData = trim(I('image','','')) ? json_decode(trim(I('image','','')),true) : '';	//图片信息 接收转发的图片信息
		$voiceData = trim(I('voice','','')) ? json_decode(trim(I('voice','','')),true) : '';	//语音信息 接收转发的语音信息
		$location  = '';				//位置信息
		$tag	   = trim(I('tag'));//客户端用的tag标识符
		//检查用用户是否还在一个群组当中
		$check = self::checkUser($typechat, $toid, $fromid);
		if($check){
			return $check;
		}
		//上传文件
		if (!empty($_FILES)) {
			$info = self::upload($uid, $typefile);
			if ($info['status']) {
				$imageData = $info['image'];
				$voiceData = $info['voice'];
			}else {
				return showData(new \stdClass(), $info['info'], 1);
			}
		}
		//位置
		if ($typefile == self::location) {
			$location = array('lat'=>trim(I('lat')),'lng'=>trim(I('lng')),'address'=>trim(I('address')));
		}
		//消息体
		$body = array(
				'from'		=> $from,
				'to'		=> $to,
				'image'		=> $imageData,
				'voice'		=> $voiceData,
				'location'	=> $location,
				'content'	=> $content,
				'typechat'	=> $typechat,
				'typefile'	=> $typefile,
				'tag'		=> $tag,
				'time'		=> getMillisecond(),//时间
		);
		
		$data  = array(
				'fromid'	=> $fromid,
				'from'		=> json_encode($from),
				'toid'		=> $toid,
				'to'		=> json_encode($to),
				'image'		=> $imageData ? json_encode($imageData) : '',
				'voice'		=> $voiceData ? json_encode($voiceData) : '',
				'location'	=> $location ? json_encode($location) : '',
				'content'	=> $content,
				'typechat'	=> $typechat,
				'typefile'	=> $typefile,
				'tag'		=> $tag,
				'time'		=> $body['time']
		);
	
		//判断是单聊还是群聊及接收消息的用户
		$uids = self::chatType($typechat, $toid, $fromid);
		if ($uids) $toUids = $uids;
	
		$msgid = $this->add($data);
		if ($msgid) {
			$body['id'] = $msgid;
			if ($toUids){
				$openfire = new Openfire();
				$ret = $openfire->message($fromid, $toUids, $body);
				if ($ret) {
					return showData($body, '发送消息成功');
				}else {
					//openfire发送失败 删除掉写入数据表的消息内容
					$this->delete($msgid);
					return showData(new \stdClass(), '发送消息失败', 1);
				}
			}else {//没有接收者 只是保存消息
				return showData($body, '发送消息成功');
			}
		}else {
			return showData(new \stdClass(), '发送消息失败', 1);
		}
	}
	/**
	 * 判断哪些用户能够接收消息
	 * @param unknown $typechat
	 * @return string
	 */
	function chatType($typechat, $toid, $fromid){
		$list = array();
		$uids = '';
		switch ($typechat) {
			case self::singleChat:
				$uids = $toid;
				break;
			case self::groupChat:
				$list = M($this->groupUser)->field('uid')->where(array('groupid'=>$toid,'uid'=>array('neq',$fromid)))->select();
				break;
			case self::sessionChat:
				$list = M($this->sessionUser)->field('uid')->where(array('sessionid'=>$toid,'uid'=>array('neq',$fromid)))->select();
				break;
			case self::meetingChat:
				$list = M($this->meetingUser)->field('uid')->where(array('meetingid'=>$toid,'status'=>1,'uid'=>array('neq',$fromid)))->select();
				break;
		}
		if ($list) {
			foreach ($list as $k=>$v){
				$uids .= $v['uid'] . ',';
			}
			$uids = substr($uids, 0, -1);
		}
		return $uids;
	}
	/**
	 * 检查用户是不是某组成员
	 * @param unknown $typechat 聊天类型
	 * @param unknown $toid		接收者
	 * @param unknown $fromid	发送者
	 * @return array
	 */
	function checkUser($typechat, $toid, $fromid){
		if ($typechat == self::groupChat) {
			if ( ! M($this->groupUser)->where(array('groupid'=>$toid,'uid'=>$fromid))->count()) {
				return showData(new \stdClass(), '发送失败，你已不是该组成员！',3);
			}
		}else if ($typechat == self::sessionChat) {
			if ( ! M($this->sessionUser)->where(array('sessionid'=>$toid,'uid'=>$fromid))->count()) {
				return showData(new \stdClass(), '发送失败，你已不是该组成员！',3);
			}
		}else if ($typechat == self::meetingChat){
			if ( ! M($this->meetingUser)->where(array('meetingid'=>$toid,'uid'=>$fromid,'status'=>1))->count()) {
				return showData(new \stdClass(), '发送失败，你已不是该会议成员！', 3);
			}
		}else {
			if ( M('blacklist')->where(array('uid'=>$toid, 'fid'=>$fromid))->count()) {
				return showData(new \stdClass(), '拒绝接收你的消息', 4);
			}
		}
		return false;
	}
	/**
	 * 上传图片
	 * @return array
	 */
	function upload($uid, $typefile){
		$ROOT_PATH 	= SITE_DIR.UPLOADS;	//物理根目录
		$URL_PATH 	= SITE_PROTOCOL.SITE_URL.UPLOADS; //URL地址
		$upload   	= new Upload();
		$upload->exts 	   = array('jpg','png','gif','mp3','jpeg');// 设置附件上传类型
		$upload->rootPath  = $ROOT_PATH;
		$upload->savePath  = '/Picture/message/'.$uid.'/'; // 设置附件上传目录
		$upload->replace   = true;
		$upload->subName   = array('date','Ymd');
		// 上传文件
		$info   =   $upload->upload();
		if(!$info) {// 上传错误提示错误信息
			return array('status'=>0, 'info'=>$upload->getError());
		}else{// 上传成功
			$image = new Image();
			$imageData = $voiceData = array();
			foreach($info as $file){
				$URL =  $file['savepath'].$file['savename'];
				if ($typefile == self::image) {
					$smallUrl = $file['savepath'].'s_'.$file['savename'];
					$image->open($ROOT_PATH.$URL);			//打开图片
					$s_path = $ROOT_PATH.$smallUrl;			//小图
					$image->thumb(200, 200)->save($s_path);	//压缩
					$image->open($s_path);					// 打开小图
					$imageData['urllarge'] 	= $URL_PATH.ltrim($URL,'/');
					$imageData['urlsmall'] 	= $URL_PATH.ltrim($smallUrl,'/');
					$imageData['width'] 	= $image->width();
					$imageData['height'] 	= $image->height();
				}else {
					$voiceData['url']   = $URL_PATH.ltrim($URL,'/');
					$voiceData['time']	= I('voicetime',0);
				}
			}
			return array('status'=>1, 'image'=>$imageData, 'voice'=>$voiceData);
		}
	}
}