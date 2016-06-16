<?php
/**
 * 消息推送
 * @author mywind
 *
 */
namespace User\Model;
use Think\Model;
class PushModel extends Model {
	protected $tableName    = 'user_push';
	protected $pk        	= 'uid';
	protected $OP_ADMIN 	= "admin";
	protected $OP_BEAUTYAS  = "beautyas";
	
	/**
	 * 获取from内容
	 * @param string $xml 消息
	 * @return string 发送者
	 */
	private function parseFromFiled($xml){
		$start  = strpos($xml, 'from="', 0);
		if($start <= 0) return '';
		$start  = $start + strlen('from="');
		$end    = strpos($xml, '"', $start);
		$newmsg = substr($xml, $start, $end-$start);
		return $newmsg;
	}
	/**
	 * 取出json消息体
	 * @param string $xml 消息体
	 * @return string
	 */
	private function parseBodyField($xml){
		$start 	= strpos($xml, '<body>', 0);
		if($start <= 0) return '';
		$start 	= $start + strlen('<body>');
		$end 	= strpos($xml, '</body>', $start);
		$newmsg = substr($xml, $start, $end-$start);
		return $newmsg;
	}
	/**
	 * 推送消息给苹果服务器
	 * @param unknown $uid 接收者
	 * @param unknown $content
	 * @return number 成功返回1，失败返回0
	 */
	private function send2ios_msg($uid, $content){
		$devToken = $this->where(array('uid'=>$uid, 'bnotice'=>1))->getField('udid');
		if ($devToken) {
			// 发送通知
			$where['username'] = $uid;
			$DB_OP = M(null, null, 'DB_CONFIG2');
			$count = $DB_OP->table('ofOffline')->where($where)->count();
			if ($count === false) {
				$count = $DB_OP->table('ofoffline')->where($where)->count();
			}
			if ($count) self::iosmsg($devToken, $content, $count);
		}
	}
	/**
	 * ios推送消息
	 * @param unknown $devToken 设备
	 * @param unknown $msg 消息
	 * @param unknown $badge 消息数量
	 */
	private function iosmsg($devToken, $msg, $badge){
		$deviceToken = $devToken;	//deviceToken
		$passphrase  = '123456';	//密码
		$message     = $msg;		//推送消息
	
		$ctx = stream_context_create();
		if (C('IOS_PUSH_RELEASE')) {
			//正式
			stream_context_set_option($ctx, 'ssl', 'local_cert', './Data/release.pem');//pem地址
			stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);	//证书密码
			//这个为正式的发布地址
			$fp = stream_socket_client("ssl://gateway.push.apple.com:2195", $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
		}else {
			//测试pem
			stream_context_set_option($ctx, 'ssl', 'local_cert', './Data/develop.pem');//pem地址
			stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);	//证书密码
			//这个是沙盒测试地址
			$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		}
	
		if (!$fp) return false;
	
		$body['aps'] = array(
				'alert' => $message,
				'sound' => 'default',
				'badge' => (int)$badge
		);
		// Encode the payload as JSON
		$payload = json_encode($body);
		// Build the binary notification
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
	
		fwrite($fp, $msg, strlen($msg));
		fclose($fp);
		return true;
	}
	/**
	 * 推送消息体
	 * @param unknown $typefile
	 * @param unknown $msg
	 */
	private function msg($typefile, $fromname, $content){
		$msg = '';
		//用户发送的消息
		switch ($typefile) {
			case 1:
				//文字
				$msg = $fromname.':'.$content;
				break;
			case 2:
				//图片
				$msg = $fromname.':发送了一张图片';
				break;
			case 3:
				//语音
				$msg = $fromname.':发送了一段语音';
				break;
			case 4:
				$msg = $fromname.':发送了一个位置';
		}
		return $msg;
	}
	
	/**
	 * 推送离线消息
	 */
	function pushtoios_offlinemsg() {
		// 1. 获取本地保存的最新离线消息记录
		header('Content-Type: text/html; charset=utf-8');
		$lastoffline = S('Iospush_offline');
		if (!$lastoffline) {
			$lastoffline = array('id'=>1);
		}
		// 2. 从op上查询最新的离线消息
		$map['messageID'] 	 = array('gt',$lastoffline['id']);
		
		//不同的操作系统建表的字段大小写不一样
		$DB_OP = M(null, null, 'DB_CONFIG2');
		$list = $DB_OP->table('ofOffline')->where($map)->order('creationDate asc')->select();
		if ($list === false) {
			$list = $DB_OP->table('ofoffline')->where($map)->order('creationDate asc')->select();
		}
		if ($list){
			foreach ($list as $key=>$value){
				$msg 	= '';
				$str 	= iconv('GBK', 'UTF-8', $value['stanza']);
				$newData= array();
				$newData= json_decode(self::parseBodyField($str), true);//$newData 是消息体
				$fromID = self::parseFromFiled($str);
				//系统通知
				if(!(strpos($fromID, "admin") ===false && strpos($fromID, "beautyas") === false) ){
					$msg = '';
				}else {
					if ($newData['typechat'] == 300) {//群聊
						//切换数据库
						if (M('session_user', C('DB_PREFIX'), 'DB_CONFIG1')->where(array('sessionid'=>$newData['to']['id'],'uid'=>$value['username']))->getField('getmsg')) {
							$msg = self::msg($newData['typefile'], $newData['from']['name'], $newData['content']);
						}
					}else if ($newData['typechat'] == 500){
						if (M('meeting_user', C('DB_PREFIX'), 'DB_CONFIG1')->where(array('meetingid'=>$newData['to']['id'],'uid'=>$value['username']))->count()) {
							$msg = self::msg($newData['typefile'], $newData['from']['name'], $newData['content']);
						}
					}else {//单聊
						if (M('user_friend', C('DB_PREFIX'), 'DB_CONFIG1')->where(array('uid'=>$value['username'],'fid'=>$newData['from']['id']))->getField('getmsg')) {
							$msg = self::msg($newData['typefile'], $newData['from']['name'], $newData['content']);
						}
					}
				}
				// 3. 发送消息到苹果手机,如果用户不是苹果手机，则直接丢弃该消息。
				if ($msg){
					M(null, C('DB_PREFIX'), 'DB_CONFIG1');//切换数据库
					self::send2ios_msg($value['username'], $msg);//username 用户uid
				}
				$data = array('id'=>$value['messageID']);
				S('Iospush_offline',$data);
			}
		}
	}
	/**
	 * 添加通知host
	 */
	function addNoticeHostForIphone($uid){
		$udid    = I('udid');
		$bnotice = I('bnotice') ? I('bnotice') : 1;
	
		$this->where(array('uid'=>$uid))->delete();	//删除掉用户相关
		$this->where(array('udid'=>$udid))->delete();//删除掉设备相关
	
		$ret = $this->add(array('uid'=>$uid, 'udid'=>$udid, 'bnotice'=>$bnotice));
		if ($ret) {
			return showData(new \stdClass(), '操作成功');
		}else {
			return showData(new \stdClass(), '操作失败', 1);
		}
	}
	/**
	 * 移除通知host
	 */
	function removeNoticeHostForIphone($uid){
		$udid    = I('udid');
		if ($this->where(array('udid'=>$udid))->delete() !== false){
			$this->where(array('uid'=>$uid))->delete();	//删除掉用户相关
			return showData(new \stdClass(), '操作成功');
		}else {
			return showData(new \stdClass(), '操作失败', 1);
		}
	}
}