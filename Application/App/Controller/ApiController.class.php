<?php
namespace App\Controller;
use Think\Controller;
class ApiController extends Controller {
	public function UserLogin()
	{
		$info='注册成功';
        $state=1;
		$username=trim(I('username'));
		$password=trim(I('password'));
		if(!$password)
		{
			
			$info='密码不能为空！'; 
			$state=0;
		}

		if(!$username)
		{

			$info='用户名不能为空！'; 
			$state=0;
		}
		
		$r = M('user')->where(array('username'=>$username))->find();
		if(!$r)
		{
		$info='用户不存在！'; 
		$state=0;
		}
		if($r['password'] != $password) {
		$info='密码不正确！'; 
		$state=0;
		}
	    $data = array('state'=>$state, 'info'=>$info);
		echo json_encode($data);

	}
	public function Reg()
	{
		$isok=TRUE;

		$username=trim(I('username')) ? trim(I('username')) : $isok=FALSE;
		$password=trim(I('password')) ? trim(I('password')) : $isok=FALSE;
		$nickname=trim(I('nickname')) ? trim(I('nickname')) : $isok=FALSE;
		if($isok)
		{
			 $user=M('user');
			 $user->username=$username;
			 $user->password=$password;
			 $user->nickname=$nickname;
			 $id=$user->add();
			 if($id)
			 {
			 	 $data = array('state'=>1, 'info'=>$id);
			 }
			 else{
			 	$data = array('state'=>0, 'info'=>'注册失败，请检查服务器！');
			 }
			
		}
		else{
			 $data = array('state'=>0, 'info'=>'用户名、密码、昵称不能为空！');
		}
		
		echo json_encode($data);
		
		
	}

}
