<?php
/**
 * 模块管理
 * @author Administrator
 *
 */
namespace Admin\Controller;
use Admin\Controller\CommonController;
use Admin\Model\ModuleModel;
use Think\Model;
use Think\Db;
use Admin\Model\MenuModel;
class ModuleController extends CommonController {
	private $module_db;
	public function _initialize(){
		parent::_initialize();
		$this->module_db = new ModuleModel();
	}
	/**
	 * 取得模块的基本信息
	 * @param unknown $module
	 * @return string
	 */
	private function getModuleInfoInFile($module) {
		$data = require APP_PATH . $module .'/Data/description.php';
		return $data;
	}
	/**
	 * 创建数据表
	 * @param [resource] [$db] [数据库连接资源]
	 * @param [string] [$prefix] [数据表前缀]
	 */
	private function create_tables($module,$prefix = ''){
		//连接数据库
		$dbconfig = require CONF_PATH .'/config.php';
		$db 	  = Db::getInstance($dbconfig);
		$file 	  = APP_PATH . $module . '/Data/install.sql';
		//读取sql文件
		$sql = file_get_contents($file);
		$sql = str_replace("\r", "\n", $sql);
		$sql = explode(";\n", $sql);
		//替换表前缀
		$sql = str_replace("`tc_", "`{$prefix}", $sql);
		//开始安装
		foreach ($sql as $value) {
			$value = trim($value);
			if (empty($value)) continue;
			if (substr($value, 0,12) == 'CREATE TABLE') {
				//$name = preg_replace("/^CREATE TABLE `(\w+)` .*/s", "\\1", $value);
				//$msg = "创建数据表{$name}";
				$db->execute($value);
			} else {
				$db->execute($value);
			}
		}
	}
	/**
	 * 更新菜单
	 */
	private function updateMenu($module){
		$data 	  = require APP_PATH . $module .'/Data/menu.php';
		M('menu')->where(array('module'=>$module))->delete();//删除存在的 防止重复安装
		$data['0']['listorder'] = M('menu')->where(array('parentid'=>0))->order('listorder desc')->getField('listorder') + 1;
		$parentid = M('menu')->add($data['0']);
		if ($parentid) {
			foreach ($data['1'] as $k=>$v){
				$v['parentid'] = $parentid;
				M('menu')->add($v);
			}
		}
		//更新缓存
		$menu = new MenuModel();
		$menu->clearCatche();
	}
	/**
	 * 更新配置文件
	 */
	private function updateConfig($module, $type='add'){
		$config = require CONF_PATH.'/module.php';
		if ($type == 'add') {
			if (!in_array($module, $config['MODULE_ALLOW_LIST'])) {
				array_push($config['MODULE_ALLOW_LIST'], $module);//追加一个元素
				file_put_contents(CONF_PATH.'/module.php', "<?php \nreturn " . stripslashes(var_export($config, true)) . ";", LOCK_EX);
			}
		}else {
			$list = $config['MODULE_ALLOW_LIST'];
			if (in_array($module, $config['MODULE_ALLOW_LIST'])) {
				unset($list[array_search($module, $list)]);//删除一个元素
				$config['MODULE_ALLOW_LIST'] = array_shift($list);
				file_put_contents(CONF_PATH.'/module.php', "<?php \nreturn " . stripslashes(var_export($config, true)) . ";", LOCK_EX);
			}
		}
	}
	/**
	 * 模块列表，获取Application目录下面的所有文件夹
	 */
	function index(){
		if(IS_POST){
			$data = array();
			//获取不需要列出的文件夹名字
			$exclusion = C('EXCLUSION_APP_MODULES');
			//获取application目录下的文件夹
			$basedir = SITE_DIR . ltrim(APP_PATH,'.');
			$handle  = opendir($basedir);
			if ( $handle ) {
				while ( ($file = readdir($handle)) !== false )	{
					if ( $file != '.' && $file != '..')	{
						$cur_path = $basedir . DIRECTORY_SEPARATOR . $file;
						if ( is_dir($cur_path) && !in_array($file, $exclusion) ){
							$find = $this->module_db->where(array('name'=>$file))->find();
							if($find) {
								$data[] = $find;//检测模块是否在数据库中
							}else {
								$data[] = self::getModuleInfoInFile($file);//从文件中检测
							}
						}// end is_dir
					}// end file
				}
				closedir($handle);
			}
			$this->ajaxReturn($data);
		}else{
			$this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
			$this->display('list');
		}
	}
	/**
	 * 安装并启动
	 * @param string $module 模块名
	 */
	function installAndStartup($module) {
		if(!$module)
			return $this->error("操作失败, modulename不能为空");
		// 写入配置文件允许该模块访问
		self::updateConfig($module);
		// 执行安装相关操作		
		self::create_tables($module, C('DB_PREFIX'));
		self::updateMenu($module);
		// 写入数据库
		$data = self::getModuleInfoInFile($module);
		$data['status'] = 1;
				
		$result = $this->module_db->add($data);
		if(!$result)
			$this->error("操作失败");
		
		$this->success("操作成功");
	}
	/**
	 * 启动模块
	 * @param unknown $id
	 */
	function startup($id) {
		$module = $this->module_db->field('name')->where(array('id'=>$id))->find();
		if ($module) {
			$result = $this->module_db->where(array('id'=>$id))->save(array('status'=>1));
			if($result){
				self::updateConfig($module['name'],'add');// 写入配置文件允许该模块访问
				$this->success("操作成功");
			}else {
				$this->error("操作失败");
			}
		}else {
			$this->error("操作失败，数据库中未查找到该模块");
		}
	}
	/**
	 * 停止模块
	 * @param unknown $id
	 */
	function stop($id) {
		$module = $this->module_db->field('name')->where(array('id'=>$id))->find();
		if ($module) {
			$result = $this->module_db->where(array('id'=>$id))->save(array('status'=>2));
			if ($result) {
				self::updateConfig($module['name'],'delete');// 写入配置文件不允许该模块访问
				$this->success("操作成功");
			}else {
				$this->error("操作失败");
			}
		}else {
			$this->error("操作失败，数据库中未查找到该模块");
		}
	}
	/**
	 * 删除模块
	 * @param unknown $id
	 */
	function delete($module, $id=0) {
		//从文件中删除
		$dir =  SITE_DIR . ltrim(APP_PATH,'.')  . $module;
		if (chmod($dir, 0777)){
			deldir($dir);
			///从数据库中删除
			$this->module_db->where(array('id'=>$id))->delete();
			//删除菜单
			M('menu')->where(array('module'=>$module))->delete();
			//删除表
			$model = new Model();
			$sql   = 'DROP TABLE IF EXISTS `'.C('DB_PREFIX').strtolower($module).'`';
			$model->execute($sql);
			//写入配置文件不允许该模块访问
			self::updateConfig($module['name'],'delete');
		}else {
			$this->success('删除失败,你未没有足够权限，请改变文件的读写属性');	
		}
		$this->success("操作成功");
	}
}