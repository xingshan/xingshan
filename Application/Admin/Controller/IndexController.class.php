<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;
use Admin\Model\AdminModel;
use Admin\Model\Admin_roleModel;
use Think\Verify;
use Company\Model\CompanyModel;
class IndexController extends CommonController {
	private $admin_db;
	private $admin_role_db;
	private $verify;
	public function _initialize(){
		parent::_initialize();
		$this->admin_db 	 = new AdminModel();
		$this->admin_role_db = new Admin_roleModel();
		$this->times_db      = M('times');
		$this->verify		 = new Verify();
	}
	//首页
	function index() {
		$userid = session('userid');
		$roleid = session('roleid');
		$admin_username = cookie('admin_username');
		$rolename = $this->admin_role_db->getRoleName($roleid);
		
		$this->admin_db->where(array('userid'=>$userid))->save(array('lastlifetime'=>SYS_TIME));
		
		$menu_array = $this->menu_db->getMenu(0);	//头部菜单
		$info = $this->admin_db->field('`lastlogintime`,`lastloginip`')->where(array('userid'=>$userid))->find();
		
		$this->assign('userid',$userid);
		$this->assign('admin_username',$admin_username);
		$this->assign('rolename',$rolename);
		$this->assign('menu_array',$menu_array);
		$this->assign('info',$info);
		$this->display('index');
	}
	//登陆
	function login(){
		if (I('dosubmit')) {
			//检查用户名 密码
			$username = trim(I('username')) ? trim(I('username')) : $this->error('用户名不能为空', HTTP_REFERER, 1);
			//密码错误剩余重试次数
			$rtime = $this->times_db->where(array('username'=>$username, 'isadmin'=>1))->find();
			if($rtime['times'] >= C('MAX_LOGIN_TIMES')) {
				$minute = C('LOGIN_WAIT_TIME') - floor((SYS_TIME-$rtime['logintime'])/60);
				if ($minute > 0) {
					$this->error("密码重试次数太多，请过{$minute}分钟后重新登录！");
				}else {
					$this->times_db->where(array('username'=>$username))->delete();
				}
			}
			//查询帐号
			$r = $this->admin_db->where(array('username'=>$username))->find();
			if(!$r) $this->error('管理员不存在', HTTP_REFERER, 1);
			$password = md5(md5(trim($_POST['password'])).$r['encrypt']);
			
			$ip = get_client_ip();
			if($r['password'] != $password) {
				if($rtime && $rtime['times'] < C('MAX_LOGIN_TIMES')) {
					$times = C('MAX_LOGIN_TIMES') - intval($rtime['times']);
					$this->times_db->where(array('username'=>$username))->save(array('ip'=>$ip,'isadmin'=>1));
					$this->times_db->where(array('username'=>$username))->setInc('times');
				} else {
					$this->times_db->where(array('username'=>$username,'isadmin'=>1))->delete();
					$this->times_db->add(array('username'=>$username,'ip'=>$ip,'isadmin'=>1,'logintime'=>SYS_TIME,'times'=>1));
					$times = C('MAX_LOGIN_TIMES');
				}
				$this->error("密码错误，您还有{$times}次尝试机会！", HTTP_REFERER);
			}
			//验证码判断 thinkphp的验证验证成功会清空,所以得在所有的处理成功了 才来验证验证码
 			$code = I('code');
 			if (!$code) $this->error('请输入验证码', HTTP_REFERER, 1) ;
 			if (!$this->verify->check($code)) $this->error('验证码错误！', HTTP_REFERER, 1);
			
			$this->times_db->where(array('username'=>$username))->delete();
			$this->admin_db->where(array('userid'=>$r['userid']))->save(array('lastloginip'=>$ip,'lastlogintime'=>SYS_TIME,'lastlifetime'=>SYS_TIME));
				
			import('ORG.Util.String');
			session('userid', $r['userid']);
			session('realname', $r['realname']);
			session('roleid', $r['roleid']);
			session('company_id', $r['company_id']);
	
			$company=new CompanyModel();
			$companyname = $company->where(array("id"=>$r['company_id']))->find()['name'];
			
			session('company_name', $companyname);
			
			session('lock_screen', 0);
			cookie('admin_username',$username);
			cookie('userid', $r['userid']);
			
			$this->success('登陆成功', U('Index/Index'));
		}else {
			$this->display();
		}
	}
	
	//验证码
	public function code() {
		$this->verify->fontSize = 15;
		$this->verify->imageW   = 120;
		$this->verify->imageH   = 30;
		$this->verify->length   = 4;
		$this->verify->useNoise = false;
		$this->verify->entry();
	}
	//后台首页
	public function public_main(){
		load('Func.Sys#func', COMMON_PATH);
		$userid = session('userid');
		$roleid = session('roleid');
		$admin_username = cookie('admin_username');
		$rolename = $this->admin_role_db->getRoleName($roleid);
		$r = $this->admin_db->where(array('userid'=>$userid))->find();
		$logintime = $r['lastlogintime'];
		$loginip = $r['lastloginip'];
		
		//检测框架目录可写性
		
		$is_writeable = is_writable(THINK_PATH.'ThinkPHP.php');
		$sysinfo = get_sysinfo();
		$os = explode(' ', php_uname());
		//网络使用状况
		$net_state = null;
		if ($sysinfo['sysReShow']=='show' && false !== ($strs = @file("/proc/net/dev"))){
			for ($i = 2; $i < count($strs); $i++ ){
				preg_match_all( '/([^\s]+):[\s]{0,}(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $strs[$i], $info );
				$net_state.="{$info[1][0]} : 已接收 : <font color=\"#CC0000\"><span id=\"NetInput{$i}\">".$sysinfo['NetInput'.$i]."</span></font> GB &nbsp;&nbsp;&nbsp;&nbsp;已发送 : <font color=\"#CC0000\"><span id=\"NetOut{$i}\">".$sysinfo['NetOut'.$i]."</span></font> GB <br />";
			}
		}
	
		//当前在线人数
		$onlineList = $this->admin_db->field('username,realname')->where('`lastlifetime` >= '.strtotime('-5 minute'))->order('lastlifetime desc')->select();
		
		$this->assign('admin_username',$admin_username);
		$this->assign('rolename',$rolename);
		$this->assign('logintime',$logintime);
		$this->assign('loginip',$loginip);
		$this->assign('is_writeable',$is_writeable);
		$this->assign('sysinfo',$sysinfo);
		$this->assign('os',$os);
		$this->assign('net_state',$net_state);
		$this->assign('onlineList', $onlineList);
		$this->display('main');
	}
	//获取php信息
	public function public_phpinfo(){
		$this->display('phpinfo');
	}
	//退出登录
	public function public_logout() {
		$userid = session('userid');
		//清空在线信息
		if($userid) $this->admin_db->where(array('userid'=>$userid))->save(array('lastlifetime'=>''));
	
		session(null);
		cookie(null);
		$this->success('安全退出！', U('Index/login'));
	}
	//左侧菜单
	public function public_menuLeft($menuid = 0) {
		$datas = array();
		
		$list = $this->menu_db->getMenu($menuid);
		foreach ($list as $k=>$v){
			$datas[$k]['name'] = $v['name'];
			$son_datas = $this->menu_db->getMenu($v['id']);
			if ($son_datas) {
				foreach ($son_datas as $k2=>$v2){
					$datas[$k]['son'][$k2]['name'] = $v2['name'];
					$datas[$k]['son'][$k2]['url'] = U($v2['module'].'/'.$v2['m'].'/'.$v2['a'].'?menuid='.$v2['id'].'&'.$v2['data']);
				}
			}else {
				$datas[$k]['son'] = array();
			}
			
		}
		$this->ajaxReturn($datas);
	}
	//防止登录超时和后台在线人数统计
	function public_sessionLife(){
		$userid = session('userid');
		$this->admin_db->where(array('userid'=>$userid))->save(array('lastlifetime'=>SYS_TIME));
	}
}