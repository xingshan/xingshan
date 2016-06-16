<?php
/**
 * ios推送管理
 */
namespace Admin\Controller;
use Admin\Controller\CommonController;
use Think\Upload;
class PushController extends CommonController {
    function _initialize() {
        parent::_initialize();
    }
    
    /**
     * 推送证书环境设置
     */
    function index(){
        if (IS_POST){
            @chmod(CONF_PATH.'/push.php', 0777);
            if (I('push')){
                $config['IOS_PUSH_RELEASE'] = false;
            }else {
                $config['IOS_PUSH_RELEASE'] = true;
            }
            if (!trim(I('password'))) $this->error('请设置推送证书的密码');
            $config['IOS_PUSH_PASSWD']  = trim(I('password'));
            if (file_put_contents(CONF_PATH.'/push.php', "<?php \nreturn " . stripslashes(var_export($config, true)) . ";", LOCK_EX)){
                $isEdit = 1;
            }else {
                $this->error('没有权限，请修改文件读写权限');
            }
            if ($isEdit){
                $this->success('修改成功');
            }else {
                $this->error('修改失败');
            }
        }else {
            $this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
            $this->info = C('IOS_PUSH_RELEASE');
            $this->passwd = C('IOS_PUSH_PASSWD');
            $this->display();
        }
    }
    /**
     * 证书列表
     */
    function upload(){
        if (IS_POST){
            $filesnames = scandir(SITE_DIR.'/Data/');
            $i    = 1;
            foreach ($filesnames as $name) {
                if (pathinfo($name, PATHINFO_EXTENSION) == 'pem'){
                    $list[] = array('id'=>$i, 'name'=>$name);
                    $i++;
                }
            }
            $this->ajaxReturn($list);
        }else {
            $this->currentpos = $this->menu_db->currentPos(I('menuid'));  //栏目位置
            $this->display();
        }
    }
    /**
     * 删除证书
     */
    function delete($id){
        if (unlink(LOG_PATH.$id)){
            $this->success('删除成功');
        }else {
            $this->error('删除失败,你没有权限删除，请修改根目录(./Data/)文件夹的权限');
        }
    }
    /**
     * 上传推送证书
     */
    function public_upload(){
        $upload 	= new Upload();
        $upload->exts 	   = array('pem');// 设置附件上传类型
        $upload->rootPath  = SITE_DIR;
        $upload->replace   = true;
        $upload->savePath  = '/Data/'; 		// 设置附件上传目录
        $upload->saveName  = $_REQUEST['name'];
        $upload->subName   = '';
        
        $info   =   $upload->upload();
        if(!$info) {
            // 上传错误提示错误信息
            $this->ajaxReturn(array('info'=>$upload->getError(), 'status'=>0));
        }else{// 上传成功
            /* foreach($info as $file){
            } */
            $this->ajaxReturn(array('info'=>'上传成功', 'status'=>1));
        }
    }
}