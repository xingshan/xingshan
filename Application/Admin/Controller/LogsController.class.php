<?php
/**
 * 在debug模式下生成的logs文件
 */
namespace Admin\Controller;
use Admin\Controller\CommonController;
class LogsController extends CommonController {
    function _initialize() {
        parent::_initialize();
    }
    
    /**
     * logs文件列表
     */
    function index(){
        if (IS_POST){
            //获取本文件目录的文件夹地址
            $filesnames = scandir(LOG_PATH);
            //获取也就是扫描文件夹内的文件及文件夹名存入数组 $filesnames
            $list = array();
            $i    = 1;
            foreach ($filesnames as $name) {
                if ($name == '.' || $name == '..'){
                    ;
                }else {
                    $list[] = array(
                        'id'    => $i,
                        'name'  => $name
                    );
                    $i++;
                }
            }
            $this->ajaxReturn($list);
        }else {
            $this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
            $this->display('list');
        }
    }
    /**
     * logs详细
     */
    function detail($id=''){
        $content = file_get_contents(LOG_PATH.$id);
        $str = str_replace("\r\n", '<br>', $content);
        $this->content = $str;
        $this->display('detail');
    }
    /**
     * 删除文件
     */
    function delete($id=''){
        $fileArray = explode(',', $id);
        $logs = '';
        foreach ($fileArray as $v){
            if ($v){
                if (unlink(LOG_PATH.$v)){
                    $delete = true;
                }else {
                    $logs .= $v.',';
                    $delete = false;
                }
            }
        }
        if ($delete){
            $this->success('删除成功');
        }
        $this->error($v.'删除失败,你没有权限删除，请修改根目录(./Data/)文件夹的权限');
    }
    //网站调试模式设置
    function debug(){
        $content = file_get_contents(SITE_DIR.'/index.php');
        if (IS_POST){
            $isEdit = 0;
            if (I('debug')){
                if (file_put_contents(SITE_DIR.'/index.php', str_replace("define('APP_DEBUG',false)", "define('APP_DEBUG',true)", $content), LOCK_EX)){
                    $isEdit = 1;
                }else {
                    $this->error('没有权限，请修改文件读写权限');
                }
            }else {
                if (file_put_contents(SITE_DIR.'/index.php', str_replace("define('APP_DEBUG',true)", "define('APP_DEBUG',false)", $content), LOCK_EX)){
                    $isEdit = 1;
                }else {
                    $this->error('没有权限，请修改文件读写权限');
                }
            }
            if ($isEdit){
                $this->success('修改成功');
            }else {
                $this->error('修改失败');
            }
        }else {
            $content = file_get_contents(SITE_DIR.'/index.php');
            $debug   = stristr($content, "define('APP_DEBUG',true)");
            if ($debug){
                $this->info = 1;
            }else {
                $this->info = 0;
            }
            $this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
            $this->display('debug');
        }
    }
}