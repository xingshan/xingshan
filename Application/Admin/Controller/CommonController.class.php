<?php
namespace Admin\Controller;
use Think\Controller;
use Admin\Model\MenuModel;
class CommonController extends Controller {
	protected $priv_db;
	protected $log_db;
	protected $menu_db;
	public function _initialize(){
		if (IS_AJAX && IS_GET) C('DEFAULT_AJAX_RETURN', 'html');
		$this->priv_db = M('admin_role_priv');
		$this->menu_db = new MenuModel();
		self::check_admin();
		self::check_priv();
		self::lock_screen();
	}
	/**
	 * 判断用户是否已经登陆
	 */
	final public function check_admin() {
		if(CONTROLLER_NAME =='Index' && in_array(ACTION_NAME, array('login', 'code')) ) {
			return true;
		} else {
			if(!session('userid') || !session('roleid')){
				//$this->error('请先登录后台管理', U('Index/login'));
				$this->redirect('Admin/Index/login');
			}
		}
	}

	/**
	 * 权限判断
	 */
	final public function check_priv() {
		if(CONTROLLER_NAME =='Index' && in_array(ACTION_NAME, array('login', 'code'))) return true;
		if(session('roleid') == 1) return true;
		
		
		/*
		
		$action = ACTION_NAME;
		if(preg_match('/^public_/', ACTION_NAME)) return true;
		if(preg_match('/^ajax_([a-z]+)_/', ACTION_NAME,$_match)) {
			$action = $_match[1];
		}
		$r = $this->priv_db->where(array('m'=>CONTROLLER_NAME, 'a'=>$action, 'roleid'=>session('roleid')))->find();
		if(!$r){
			if(IS_AJAX && IS_GET){
				exit('<div style="padding:6px">您没有权限操作该项</div>');
			}else {
				$this->error('您没有权限操作该项');
			}
		}
		*/
	}
	
	/**
	 *
	 * 记录日志
	 */
	final private function manage_log(){
		//判断是否记录
		if(C('SAVE_LOG_OPEN')){
			$action = ACTION_NAME;
			if($action == '' || strchr($action,'public') || in_array($action, array('login','code'))) {
				return false;
			}else {
				$ip = get_client_ip();
				$username = cookie('admin_username');
				$userid = session('userid');
				$time = date('Y-m-d H-i-s',SYS_TIME);
				$data = array('GET'=>IS_GET);
				if(IS_POST){
					$data['POST'] = IS_POST;
				}
				$data_json = json_encode($data);
				$this->log_db->add(array('username'=>$username,'userid'=>$userid,'module'=>CONTROLLER_NAME,'action'=>ACTION_NAME, 'querystring'=>$data_json,'time'=>$time,'ip'=>$ip));
			}
		}
	}
	
	/**
	 * 检查锁屏状态
	 */
	final private function lock_screen() {
		if(session('lock_screen')==1) {
			if(preg_match('/^public_/', ACTION_NAME) || (ACTION_NAME == 'login') || (CONTROLLER_NAME == 'Index' && ACTION_NAME=='index')) return true;
			$this->error('请先登录后台管理', U('Index/login'));
		}
	}
}