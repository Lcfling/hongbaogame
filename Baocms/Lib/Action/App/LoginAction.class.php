<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/29
 * Time: 15:47
 */
require_once LIB_PATH.'/GatewayClient/Gateway.php';

use GatewayClient\Gateway;

class LoginAction extends CommonAction{

    //todo lcfling
    public function login(){
        $userName=(int)$_POST['username'];
        $userPassword=$_POST['password'];

        //判断用户名密码
        if($userName==""||$userPassword==""){
            $this->ajaxReturn(null,'账号密码不能为空！',0);
        }
        $user_ip=getip();

        $userModel = D('Users');
        $res=$userModel->getUserByMobile($userName,true);
        /*if($res['last_ip']!=$user_ip){
            $this->ajaxReturn(null,'检测到异地登录，请使用短信登录！',0);
        }*/
        if($res['password']!=md5($userPassword)){
            $this->ajaxReturn(null,'账号密码错误！',0);
        }
        $userInfo=$userModel->updateLoginCache($res);
        $this->ajaxReturn($userInfo,'登陆成功！');

    }
    public function mobile(){
        $mobile=(int)$_POST['mobile'];
        $code=(int)$_POST['code'];
        if(!isMobile($mobile)){
            $this->ajaxReturn(null,'手机号码格式错误！',0);
        }
        $userModel = D('Users');
        $userInfo=$userModel->getUserByMobile($mobile,true);
        //print_r($userInfo);
        $Cachecode=Cac()->get('login_code_'.$mobile);
        if($code!=$Cachecode){
            $this->ajaxReturn(null,'验证码错误！',0);
        }
        //判断用户是否存在
        $pid=(int)$_POST['pid'];
        if(!($pid>0)){
            $pid=0;
        }
        if(!empty($userInfo)){
            $userInfo=$userModel->updateLoginCache($userInfo);
        }else{
            //不存在 入库用户信息
            $userInfo=$userModel->insertUserInfo($mobile,$pid);
            if(empty($userInfo)){
                $this->ajaxReturn(null,'登录失败！',0);
            }
        }
        $this->ajaxReturn($userInfo,'登录成功！',1);
    }

    public function test(){
        //Cac()->delete('randUserList');
        $t=D('Hongbao')->getInfoByTime('3735274','5','180');
        print_r($t);
    }

    public function clearrobot(){
        Cac()->delete('randUserList');
    }

    public function sendcode(){
        $mobile=(int)$_POST['mobile'];
        if(!isMobile($mobile)){
            $this->ajaxReturn('','手机号码格式错误！',0);
        }
        $code=rand_string(6,1);
        Cac()->set('login_code_'.$mobile,$code,300);
        //todo 发送短信
        //Sms:LoginCodeSend($mobile,$code);
        $res=D("Sms")->dxbsend($mobile,$code);

        if($res=="0"){
            $this->ajaxReturn('','短信发送成功！',1);
        }else{
            $this->ajaxReturn('','失败！请联系管理员:'.$res,0);
        }
    }
    public function sendmobile(){
        $mobile=(int)$_POST['mobile'];
        if(!isMobile($mobile)){
            $this->ajaxReturn('','手机号码格式错误！',0);
        }
        $yzm = $this->_post('yzm');
        if(strtolower($yzm) != strtolower(session('verify'))){
            session('verify',null);
            $this->ajaxReturn('','验证码错误！',0);
        }
        $code=rand_string(6,1);
        Cac()->set('login_code_'.$mobile,$code,300);
        //todo 发送短信
        //Sms:LoginCodeSend($mobile,$code);
        $res=D("Sms")->dxbsend($mobile,$code);
        if($res=="0"){
            $this->ajaxReturn('','短信发送成功！',1);
        }else{
            $this->ajaxReturn('','失败！请联系管理员:'.$res,0);
        }
    }
    public function getcodeview(){
        $mobile=(int)$_GET['mobile'];
        if(!isMobile($mobile)){
            $this->ajaxReturn('','手机号码格式错误！',0);
        }
        $code=Cac()->get('login_code_'.$mobile);

        echo $code;
    }

    public function getzfcodeview(){
        $mobile=(int)$_GET['mobile'];
        if(!isMobile($mobile)){
            $this->ajaxReturn('','手机号码格式错误！',0);
        }
        $code=Cac()->get('zf_code_'.$mobile);

        echo $code;
    }
    public function get_zhifuview(){
        $mobile=(int)$_GET['mobile'];
        if(!isMobile($mobile)){
            $this->ajaxReturn('','手机号码格式错误！',0);
        }
        $code=Cac()->get('zf_code_'.$mobile);

        echo $code;
    }

    //产生一个指定长度的随机字符串,并返回给用户
    private function genRandomString($len = 6) {
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
        );
        $charsLen = count($chars) - 1;
        // 将数组打乱
        shuffle($chars);
        $output = "";
        for ($i = 0; $i < $len; $i++) {
            $output .= $chars[mt_rand(0, $charsLen)];
        }
        return $output;
    }
    public function reg(){

        $pid=$_GET['pid'];
        $url="https://www.darkhorse.vip/xiazai/registerAPP.html?pid=".$pid;

        header("Location:".$url);
    }
    public function online(){
        echo count(Gateway::getAllClientInfo());
    }

    public function clearhc(){
        Cac()->flushAll();
    }
}