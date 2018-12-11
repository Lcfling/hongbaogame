<?php

require_once './grafika-master/src/autoloader.php';
use Grafika\Grafika;

use Grafika\Color;
class UcenterAction extends CommonAction {
    //todo 获取用户信息
    public function index(){
        $user_id=$this->uid;
        $data=$this->member;
        $user=D('users');
        $money=$user->getUserMoney($user_id);
        $data['money']=$money;
        foreach ($data as &$v){
            if($v==null){
                $v='';
            }
        }
        $this->ajaxReturn($data,"个人信息");
    }
    //todo  获取用户信息
    public function userinfo(){
        $data=$this->member;
        $this->ajaxReturn($data,"个人信息");
    }

    // todo 我的推荐 （自己邀请的人）
    public function pid(){

        $user_id=$this->uid;
        $user=D('users');
        $data=$user->use_pid($user_id);
        $this->ajaxReturn($data,"我的推荐");
    }
    //todo 推广团队
    public function pid_team(){
        $uid=(int)$_POST['user_id'];
        $counts=(int)$_POST['counts'];

        $user=D('Users');
        $where['pid']=$uid;
        $data=$user->where($where)->select();
        $res['count']=$user->where($where)->count();
        foreach ($data as $k=>$v){
            $where1['pid']=$data[$k]['user_id'];
            $count=$user->where($where1)->count();
            $data[$k]['count']=$count;
            if($data[$k]['face']==''){
                $data[$k]['face']='img/avatar.png';
            }

            $data[$k]['reg_time']=date('Y-m-d',$data[$k]['reg_time']);
            $data[$k]['counts']=$counts;
        }
        $res['data']=$data;

        //
        $this->ajaxReturn($res);
    }

    //todo 我的收益
    public function shouyi(){
        $user_id=$this->uid;
        $user=D('users');
        $data=$user->sum_money($user_id);
        $this->ajaxReturn($data,'我的收益');
    }
    //todo 提现 1
    public function tx(){
        $user_id=$this->uid;
        $money=(int)$_POST['money']; //提现金额
        $zfb_pwd=(int)$_POST['zfb_pwd'];
        if ($money == "" || $zfb_pwd == ""){
            $this->ajaxReturn("数据异常,请检查!");
        }
        //开始时间
        $begintime=date("Y-m-d H:i:s",mktime(10,0,0,date('m'),date('d'),date('Y')));
        $begintime=strtotime($begintime);
        //结束时间
        $overtime=date("Y-m-d H:i:s",mktime(22,00,0,date('m'),date('d'),date('Y')));
        $overtime=strtotime($overtime);

        //当前时间
        $time=time();
        if ( !($time>$begintime && $time<$overtime)){
            $this->ajaxReturn('',"提现时间为10:00--22:00期间!",0);
        }
        $users=D('Users');
        $user_info=$users->getUserByUid($user_id,true);
        $this->writeLog(var_export($user_info,true));
        /*if ($user_info['zfb_num']==""||$user_info['zfb_num']==NULL||$user_info['name']==NULL){
            $this->ajaxReturn(null,'请先绑定支付宝',0);
        }*/
        $bank=D('Bank');
        $where['user_id']=$user_id;
        $bank_info=$bank->where($where)->find();
        if (!$bank_info){
            $this->ajaxReturn(null,"请先绑定银行卡",0);
        }
        if ($user_info['zfb_pwd'] !=md5($zfb_pwd)){
            $this->ajaxReturn(null,'支付密码错误!',0);
        }
        $nostr=time().rand_string(6,1);
        if(!D('Users')->txLock($this->uid,$nostr)){
            $this->ajaxReturn(null,"操作频繁",0);
        }

        //  查用户流水

        $paid=D('Paid');
        $lb_where['type']=2;
        $lb_where['user_id']=$user_id;
        $lb_money=$paid->where($lb_where)->field('sum(money) as money')->select();// 领包流水


        $hb_where['type']=4;
        $hb_where['user_id']=$user_id;
        $fb_money=$paid->where($hb_where)->field('sum(money) as money')->select();//发红包流水


        $yj_where['type']=13;
        $yj_where['user_id']=$user_id;
        $yj_money=$paid->where($yj_where)->field('sum(money) as money')->select();// 佣金流水



        /*if ($fb_money[0]['money']*-1>=$money*100 ||$lb_money[0]['money']>=$money*100 || $yj_money[0]['money']>=$money*100){*/
        if (1){
            $user_money=$users->getUserMoney($user_id);


            if (($money+$money*0.01)*100>$user_money){
                D('Users')->txopenLock($this->uid);
                $data['msg']='提现金额大于剩余金额';
                $data['status']=0;
                $this->ajaxReturn($data,$data['msg'],$data['status']);
            }


            if(!($money>=50)){
                D('Users')->txopenLock($this->uid);
                $this->ajaxReturn(null,"提现最少50",0);
            }
            $users=D("Users");
            $data= $users->txmoney($user_id,$money);
            //开锁
            D('Users')->txopenLock($this->uid);
            $this->ajaxReturn($data,$data['msg'],$data['status']);

        }else{
            D('Users')->txopenLock($this->uid);
            $data['msg']='流水金额小于提现金额';
            $data['status']=0;
            $this->ajaxReturn($data,$data['msg'],$data['status']);
        }
    }

    private function abslength($str)
    {
        $len=strlen($str);
        $i=0;
        while($i<$len)
        {
            if(preg_match("/^[".chr(0xa1)."-".chr(0xff)."]+$/",$str[$i]))
            {
                $i+=2;
            }
            else
            {
                $i+=1;
            }
        }
        return $i;
    }
    //todo 修改个人资料（只允许修改头像 昵称）
    public function set_userinfo(){

        $user_id=$this->uid;

        $user_name= I('post.user_name','','strip_tags');
        $user_img=  I('post.avatar','','strip_tags');


        if( preg_match('/\\d+/',$user_name,$matchs1) == 1)
        {
            $this->ajaxReturn(null,"名称不允许包含数字",0);
        }

        if (strlen($user_name)>12){
            $this->ajaxReturn(null,"名称不允许过长",0);
        }
        if(strstr($user_name,'微信')  || strstr($user_name,'官方') || strstr($user_name,'儿子') || strstr($user_name,'爸爸') || strstr($user_name,'爷爷')){
            $this->ajaxReturn(null,"名称非法字符",0);
        }

        if ($user_img == "" ){
            $users=D("Users");
//            if($this->abslength($user_name)>6){
//                Cac()->delete("userinfo_".$this->uid);
//                $this->ajaxReturn(null,"修改失败",0);
//            }
            $status=$users->setuserinfo($user_id,$user_name);
            if ($status){
                $this->ajaxReturn(null,"修改成功");
            }else{
                $this->ajaxReturn(null,"修改失败",0);
            }
        }else if ( $user_name==""){
            $users=D("Users");
//            if($this->abslength($user_name)>6){
//                Cac()->delete("userinfo_".$this->uid);
//                $this->ajaxReturn(null,"修改失败",0);
//            }
            $status=$users->setface($user_id,$user_img);
            if ($status){
                $this->ajaxReturn(null,"修改成功");
            }else{
                $this->ajaxReturn(null,"修改失败",0);
            }
        }

    }
    public function avatar(){

        $avatar=M('avatar');
        $data=$avatar->select();
       $this->ajaxReturn($data,'头像列表');
    }

    //todo 设置密码
    public function setpwd(){
        $user_id=$this->uid;

        $pwd=I('post.pwd','','strip_tags');
        if ($pwd == ""){
            $this->ajaxReturn("数据异常,请检查!");
        }
        $users=D('Users');
        $status=$users->set_pwd($user_id,$pwd);
        if ($status){
            $this->ajaxReturn(null,"设置成功");
        }else{
            $this->ajaxReturn(null,"设置失败",0);
        }

    }

    //todo 查询银行卡
    public function get_bank(){
        $user_id=$this->uid;
        //查询用户银行卡
        $bank=D('Bank');
        $where['user_id']=$user_id;
        $bank_info=$bank->where($where)->find();
        if ($bank_info){
            $this->ajaxReturn($bank_info,"银行卡信息");
        }else{
            $this->ajaxReturn(null,"未绑定银行卡",0);
        }
    }


    //  todo 绑定银行卡
    public  function  add_bank(){
        $user_name=I('post.user_name','','strip_tags');
        $bank_num=$_POST['bank_num'];
        $bank_info=I('post.bank_info','','strip_tags');
        $user_id=$this->uid;
        if( preg_match('/\\d+/',$user_name,$matchs1) == 1)
        {
            $this->ajaxReturn($user_name,"名称不允许包含数字",0);
        }
        if( preg_match('/\\d+/',$bank_info,$matchs1) == 1)
        {
            $this->ajaxReturn($bank_info,"开户行不允许包含数字",0);
        }
        if (!is_numeric($bank_num)){
            $this->ajaxReturn($bank_num,"银行卡号必须为纯数字",0);
        }

        $user=D('Users');
        $info=$user->add_bankinfo($user_id,$user_name,$bank_num,$bank_info);
        if ($info){
            $this->ajaxReturn(null,'绑定成功!');
        }else{
            $this->ajaxReturn(null,'绑定失败!',0);
        }
    }




    //todo 绑定支付宝账号
    public function zhifubao(){

        $user_id=$this->uid;
        $user_zfb=I('post.user_zfb','','strip_tags');
        $user_name=I('post.name','','strip_tags');

        if ($user_zfb == "" || $user_name == ""){
            $this->ajaxReturn(null,"数据异常,请检查!");
        }

        $users=D("Users");
        $status=$users->setzyb($user_id,$user_name,$user_zfb);

        if ($status){
            $this->ajaxReturn(null,"绑定成功");
        }else{
            $this->ajaxReturn(null,"绑定失败",0);
        }
    }


    //todo 解除绑定支付宝
    public function set_zfb(){
        $user_id=$this->uid;
        $yzm=(int)$_POST['yzm'];
        //判断验证码
        $userinfo=D("Users")->getUserByUid($this->uid);
        $mobile=$userinfo['account'];
        $num=Cac()->get('login_code_'.$mobile);
        if ($yzm !=$num){
            $this->ajaxReturn(null,'验证码错误!');
        }
        $users=D("Users");
        $status=$users->setzfb($user_id);
        if ($status){
            $this->ajaxReturn(null,"解除成功");
        }else{
            $this->ajaxReturn(null,"解除失败",0);
        }
    }

    //todo 设置支付密码
    public function pay_pwd(){
        $user_id=$this->uid;
        $zfb_pwd=(int)$_POST['zfb_pwd'];
        $yzm=(int)$_POST['yzm'];
        //判断验证码
        $users= D("Users");
        $userinfo=$users->getUserByUid($this->uid);
        $mobile=$userinfo['account'];
        $num=Cac()->get('zf_code_'.$mobile);

        if ($yzm !=$num){
            $this->ajaxReturn(null,'验证码错误!');
        }
            Cac()->delete('zf_code_'.$mobile);
        if (!is_numeric($zfb_pwd)){
            $this->ajaxReturn($zfb_pwd,"请设置6位纯数字",0);
        }
        if ( strlen($zfb_pwd) !=6){
            $this->ajaxReturn(null,"请设置6位密码",0);
        }
        $users=D("Users");
        $status=$users->setpay($user_id,$zfb_pwd);
        if ($status){
            $this->ajaxReturn(null,"设置成功");
        }else{
            $this->ajaxReturn(null,"设置失败",0);
        }
    }
    //todo 解除支付密码
    public function set_paypwd(){
        $user_id=$this->uid;
        $yzm=(int)$_POST['yzm'];
        $zfb_pwd=(int)$_POST['zfb_pwd'];

        if ($yzm == "" || $zfb_pwd == ""){
            $this->ajaxReturn("数据异常,请检查!");
        }

        if ( strlen($zfb_pwd) !=6){
            $this->ajaxReturn(null,"数据异常,请检查!",0);
        }
        //判断验证码
        $users= D("Users");
        $userinfo=$users->getUserByUid($this->uid);
        $mobile=$userinfo['account'];
        $num=Cac()->get('zf_code_'.$mobile);
        if ($yzm !=$num){
            $this->ajaxReturn(null,'验证码错误!');
        }
            Cac()->delete('zf_code_'.$mobile);
        //判断支付密码是否正确
        $data=$users->getUserByUid($user_id);
        if ($data['zfb_pwd'] != md5($zfb_pwd)){
            $this->ajaxReturn(null,"支付密码错误!");
        }
         // 进去解除操作
        $status=$users->set_paypwd($user_id);
        if ($status){
            $this->ajaxReturn(null,"解除成功!");
        }else{
            $this->ajaxReturn(null,"解除失败!",0);
        }
    }


     //todo 排行榜
    public function ranking_list(){


        $begintime=date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d'),date('Y')));

        $catime = strtotime($begintime);

        $fb_sql=" SELECT user_id,nickname,face,(SELECT ABS(SUM(money)) FROM bao_paid where user_id=bao_users.user_id and type=4 and creatime >=$catime and user_id>0 ) as moneys from bao_users  ORDER BY moneys desc LIMIT 10";
        $fb_data['remark']="今日发包";
        $fb_data['data']=D()->query($fb_sql);
        foreach ($fb_data['data'] as $k=>$v){
            if ($fb_data['data'][$k]['moneys'] =="" || $fb_data['data'][$k]['moneys']== null){
                $fb_data['data'][$k]['moneys']="0";
            }
        }
        Cac()->set('phb_1',serialize($fb_data));

        $qb_sql="SELECT user_id,nickname,face,(SELECT ABS(SUM(money)) FROM bao_paid where user_id=bao_users.user_id and type=2 and creatime >=$catime  ) as moneys from bao_users  ORDER BY moneys desc LIMIT 10";
        $qb_data['remark']="今日抢包";
        $qb_data['data']=D()->query($qb_sql);
        foreach ($qb_data['data'] as $k=>$v){
            if ($qb_data['data'][$k]['moneys'] =="" || $qb_data['data'][$k]['moneys']== null){
                $qb_data['data'][$k]['moneys']="0";
            }
        }
        Cac()->set('phb_2',serialize($qb_data));


        $yj_sql="SELECT user_id,nickname,face,(SELECT ABS(SUM(money)) FROM bao_paid where user_id=bao_users.user_id and type=13 and creatime >=$catime  ) as moneys from bao_users  ORDER BY moneys desc LIMIT 10";
        $yj_data['remark']="今日佣金";
        $yj_data['data']=D()->query($yj_sql);
        foreach ($yj_data['data'] as $k=>$v){
            if ($yj_data['data'][$k]['moneys'] =="" || $yj_data['data'][$k]['moneys']== null){
                $yj_data['data'][$k]['moneys']="0";
            }
        }
        Cac()->set('phb_3',serialize($yj_data));


        $tg_sql=" SELECT user_id,nickname,face,(SELECT COUNT(user_id) from bao_users where pid=b.user_id and reg_time >=$catime ) as COUNTS  from bao_users as b where b.user_id>0   ORDER BY counts desc LIMIT 10";
        $tg_data['remark']="今日推广";
        $tg_data['data']=D()->query($tg_sql);
        Cac()->set('phb_4',serialize($tg_data));

    }

    public function get_renking(){
        $type=(int)$_POST['type'];
        $data=Cac()->get('phb_'.$type);
        $this->ajaxReturn(null,"排行榜",0);
        //$this->ajaxReturn(unserialize($data),"排行榜");
    }

    public function haibao(){

        require_once(APP_PATH.'Lib/phpqrcode/phpqrcode.php');
        $ID=(int)$_GET['uid'];

        $value= $url = "http://regfw.weiquer.com/xiazai/registerAPP.html?pid=".$ID;					//二维码内容
        $errorCorrectionLevel = 'L';	//容错级别
        $matrixPointSize = 7;//生成图片大小
        header('Content-type: image/png');
        $url="./erweima/".$ID.".png";
        //生成二维码图片
        QRcode::png($value,$url,$errorCorrectionLevel, $matrixPointSize,2);

        //生成海报
        $editor = Grafika::createEditor();
        $editor->open($image1 , './erweima/hb.jpg'); //背景海报
        $editor->open($image2 ,  $url); // 二维码代码
        $editor->blend ( $image1, $image2 , 'normal', 1, 'center',0,150);//拼接成海报
        $editor->text($image1 ,"扫一扫关注我们",20,280,1000,new Color("#0faeff"),LIB_PATH.'Net/grafika-master/fonts/songti.TTF',0);//打印文字
        //   $editor->text($image1 ,'长按识别二维码',20,220,1100,new Color("FFF"),LIB_PATH.'Net/grafika-master/fonts/songti.TTF',0);//打印文字
        header('Content-type: image/jpeg');
        $image1->blob('jpeg');
    }

    public function sendcode(){
        //
        $userinfo=D("Users")->getUserByUid($this->uid);
        $mobile=$userinfo['account'];
        $code=rand_string(6,1);
        Cac()->set('login_code_'.$mobile,$code,300);
        //todo 发送短信
        //Sms:LoginCodeSend($mobile,$code);
        $res=D("Sms")->dxbsend($mobile,$code);

        if($res=="0"){
            $this->ajaxReturn('','短信发送成功！',1);
        }else{
            $this->ajaxReturn('','失败！请联系管理员'.$res,1);
        }
    }


    public function rechargelist(){
        $userModel=D('Paid');
        $_GET['p']=(int)$_POST['p'];
        import('ORG.Util.Page'); // 导入分页类
        $map=array();
        $map['user_id']=$this->uid;
        $map['type']=1;
        $count = $userModel->where($map)->count($map); // 查询满足要求的总记录数

        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        //$pager = $Page->show(); // 分页显示输出
        $list = $userModel->where($map)->order(array('creatime'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as &$value){
            $value['creatime']=date('Y-m-d H:i:s',$value['creatime']);
        }
        $data['current']=$Page->currentPage();
        $data['list']=$list;
        $this->ajaxReturn($data);
    }
    public function txlist(){
        $userModel=D('Tixian');
        $_GET['p']=(int)$_POST['p'];
        import('ORG.Util.Page'); // 导入分页类
        $map=array();
        $map['user_id']=$this->uid;
        $count = $userModel->where($map)->count($map); // 查询满足要求的总记录数

        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        //$pager = $Page->show(); // 分页显示输出
        $list = $userModel->where($map)->order(array('time'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as &$value){
            $value['time']=date('Y-m-d H:i:s',$value['time']);
        }
        $data['current']=$Page->currentPage();
        $data['list']=$list;
        if(empty($data['list'])){
            $data['list']=array();
        }
        $this->ajaxReturn($data,"提现记录");
    }
    public function paidlist(){
        $userModel=D('Paid');
        $_GET['p']=(int)$_POST['p'];
        import('ORG.Util.Page'); // 导入分页类
        $map=array();
        $map['user_id']=$this->uid;
        $count = $userModel->where($map)->count($map); // 查询满足要求的总记录数
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        //$pager = $Page->show(); // 分页显示输出
        $list = $userModel->where($map)->order(array('creatime'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as &$value){
            $value['creatime']=date('Y-m-d H:i:s',$value['creatime']);
        }
        $data['current']=$Page->currentPage();
        $data['list']=$list;
        $this->ajaxReturn($data);
    }
    public function getmoney(){
        $money=D('Users')->getusermoney($this->uid);
        $this->ajaxReturn($money);
    }
    public function yjtx(){
        $user=$this->member;
        $all=D('Users')->sum_money($this->uid);
        D('Users')->addmoney($this->uid,$all,9,1,'佣金提现');
    }
    public function sendzfcode(){
        $mobile=$this->member['mobile'];
        if(!isMobile($mobile)){
            $this->ajaxReturn('','手机号码格式错误！',0);
        }
        $code=rand_string(6,1);
        Cac()->set('zf_code_'.$mobile,$code,300);
        //todo 发送短信
        //Sms:LoginCodeSend($mobile,$code);
        $res=D("Sms")->dxbsend($mobile,$code);

        if($res=="0"){
            $this->ajaxReturn('','短信发送成功！',1);
        }else{
            $this->ajaxReturn('','失败！请联系管理员:'.$res,0);
        }
    }
    public function vrifyzfcode(){
        $mobile=$this->member['mobile'];
        $yzm=(int)$_POST['yzm'];
        $code=Cac()->get('zf_code_'.$mobile);
        //todo 发送短信
        //Sms:LoginCodeSend($mobile,$code);

        if($code==$yzm){
            $this->ajaxReturn('','短信验证成功！',1);
        }else{
            $this->ajaxReturn('','短信验证失败！',0);
        }
    }
    public function notice(){
        $cate= D('Article');
        $where['cate_id']=3;
        $data=$cate->where($where)->order(array('article_id'=>'desc'))->limit(1)->select();
        $this->ajaxReturn($data,'公告');
    }
}
