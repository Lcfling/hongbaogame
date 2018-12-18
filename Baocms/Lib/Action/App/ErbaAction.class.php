<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-11-13
 * Time: 14:32
 */
require_once LIB_PATH.'/GatewayClient/Gateway.php';

use GatewayClient\Gateway;
class ErbaAction extends CommonAction{


    // todo 抢庄
    public function rob(){

        $room_id=(int)$_POST['roomid'];
        $user_id=$this->uid;
        $money=(int)$_POST['money'];


        Cac()->incr("rob");
        $num=Cac()->get("rob");
        if ($num>1){
            Cac()->del("rob");
            $this->ajaxReturn(null,'抢庄失败,请重新抢庄!',0);

        }

        if ($money <3000){
            Cac()->del("rob");
            $this->ajaxReturn(null,'抢庄金额不能低于3000',0);
        }

        if ($room_id  == "" || $money == ""){
            Cac()->del("rob");
            $this->ajaxReturn(null,'数据异常请检查!',0);
        }

        $UserModel=D('Users');
        $usermoney=$UserModel->getusermoney($user_id);

        if ($usermoney<$money*100){
            Cac()->del("rob");
            $this->ajaxReturn(null,'余额不足，余额：'.$usermoney/100,'0');
        }

        $ErbaModel=D('Erba');

        $data=$ErbaModel->get_qz($room_id);
        foreach ($data as $k=>$v){
            if ($user_id == $data[$k]){
                Cac()->del("rob");
                $this->ajaxReturn(null,"您已抢过庄!",0);
            }
        }
        $qz_time=Cac()->get("qztime_".$room_id);


        if (empty($qz_time)){
            //记录抢庄开始时间
            Cac()->set("qztime_".$room_id,time());
        }else{
            if ($qz_time+18 <=time()){
                Cac()->del("rob");
                $this->ajaxReturn(null,"抢庄时间已过!",0);
            }
        }


            //抢庄记录时间
         $air_time=Cac()->get("qz_airtime_".$room_id);

        if (empty($air_time)){
            //记录抢庄开始时间
            Cac()->set("qz_airtime_".$room_id,time());
        }

        // 冻结抢庄的钱
        $ErbaModel->qz_dongjie($user_id,$money);

        //获取用户信息
        $user_info= $UserModel->getuserbyuid($user_id);
        $data['user_id']=$user_id;
        $data['money']=$money*100;
        $data['nickname']=$user_info['nickname'];
           //抢庄开始时间

        //抢庄信息存入缓存
       $ErbaModel->set_qz($room_id,$user_id,$data);

        //记录当前状态
        $fj_info['status']=1;
        $fj_info['nickname']="";
        $fj_info['money']="";
        $fj_info['chang_id']="";
        $fj_info['shangzhuang_money']=300000;
        Cac()->set("qz_status_".$room_id,serialize($fj_info));
        $air_time=Cac()->get("qz_airtime_".$room_id);

        $data['start_time']=20-(time()-$air_time);
        // 通知系统抢庄信息
        $this->sendnotify($room_id,$data,1);

        Cac()->del("rob");
        $this->ajaxReturn($data,"抢庄");
    }



    // todo 下注
    public function ble_money(){
        $room_id=(int)$_POST['room_id'];
        $user_id=$this->uid;
        $money=(int)$_POST['money'];
        $chang_id=(int)$_POST['chang_id'];

        Cac()->incr("ble_money");
        $num=Cac()->get("ble_money");
        if ($num>1){
            Cac()->del("ble_money");
            $this->ajaxReturn(null,'下注失败,请重新下注!',0);
        }



        if ($room_id == "" || $money == "" || $chang_id == ""){
            Cac()->del("ble_money");
            $this->ajaxReturn(null,'数据异常请检查!',0);
        }
        if ($money<10){
            $this->ajaxReturn(null,'最低下注为10元!',0);
        }
        // 判断用户余额
        $UserModel=D('Users');
        $usermoney=$UserModel->getusermoney($user_id);
        if($usermoney<$money*100*5){
            Cac()->del("ble_money");
            $this->ajaxReturn(null,'最大赔率余额不足请充值!',0);
        }

         $ErbaModel=D('Erba');
        // 是否是下注时间
        if (!($ErbaModel->if_time($room_id,$chang_id)) ){
            Cac()->del("ble_money");
            $this->ajaxReturn(null,'已经封盘!',0);
        }

        $data=$ErbaModel->zhuang_info($room_id,$chang_id);

        if ($user_id == $data['user_id']){
            Cac()->del("ble_money");
            $this->ajaxReturn(null,"庄家不允许下注!",0);
        }


        //        //  判断庄下注金额
        if ( !($ErbaModel->if_money($room_id,$money)) ){
            Cac()->del("ble_money");
            $this->ajaxReturn(null,'超出盘口了!',0);
        }



        //判断是否重复下注
        if ($ErbaModel->xiazhus($user_id,$chang_id,$room_id,$money)){
            $zhuijia_info=$ErbaModel->zhuijia_money($user_id,$chang_id,$room_id,$money);
            Cac()->del("ble_money");
            //获取用户信息
            $UserModel=D('Users');
            $user_info= $UserModel->getuserbyuid($user_id);

            $zhuijia_info['face']=$user_info['face'];
            $zhuijia_info['nickname']=$user_info['nickname'];
            $zhuijia_info['user_id']=$user_info['user_id'];
            $air_time=Cac()->get("qz_airtime_".$room_id);
            $zhuijia_info['start_time']=60-(time()-$air_time);
            $zhuijia_info['money']=$money*100;

            //通知系统下注信息
            $this->sendnotify($room_id,$zhuijia_info,3);
            $this->ajaxReturn($zhuijia_info,'追加下注成功!',1);
        }


        //进行下注
        $user_list=$ErbaModel->xiazhu($user_id,$money,$chang_id,$room_id);
        //获取用户信息
        $UserModel=D('Users');
        $user_info= $UserModel->getuserbyuid($user_id);

        $user_list['face']=$user_info['face'];
        $user_list['nickname']=$user_info['nickname'];
        $user_list['user_id']=$user_info['user_id'];
       $air_time=Cac()->get("qz_airtime_".$room_id);
        $user_list['start_time']=60-(time()-$air_time);

            //通知系统下注信息
            $this->sendnotify($room_id,$user_list,3);
            //下注人数++
            Cac()->incr("qz_count_".$room_id);
            Cac()->del("ble_money");
            $this->ajaxReturn($user_list,'下注成功!');

    }



    //todo 点包
    public function click_hb(){
        $hongbao_id=(int)$_POST['hongbao_id'];
        $chang_id=(int)$_POST['chang_id'];
            //获取红包信息
        $hongbaoModel=D('Erba');
        $hongbao_info=$hongbaoModel->getInfoById($hongbao_id);
        $userInfo=D('Users')->getUserByUid($hongbao_info['user_id']);
        $hongbao_info['username']=$userInfo['nickname'];

        if($hongbao_info['creatime']<time()-180){
            $hongbao_info['type']=2;
            $hongbao_info['remark']='红包过期';
            $this->ajaxReturn($hongbao_info,'红包过期!',1);
        }
        if($hongbaoModel->isfinish($hongbao_id)){
            $hongbao_info['type']=3;
            $hongbao_info['remark']='红包已经领取完毕!';
            $this->ajaxReturn($hongbao_info,'红包已经领取完毕!',1);
        }
        if($hongbaoModel->is_recived($hongbao_id,$this->uid)){
            $hongbao_info['type']=4;
            $hongbao_info['remark']='已经领取过该红包!';
            $this->ajaxReturn($hongbao_info,'已经领取过该红包!',1);
        }
        //判断是否下注的人点包
        if (!($hongbaoModel->xiazhu_info($chang_id,$this->uid))){
            $hongbao_info['type']=5;
            $hongbao_info['remark']='未下注者不允许抢红包!';
            $this->ajaxReturn($hongbao_info,"未下注者不允许抢红包!");
        }

        $hongbao_info['type']=1;
        $hongbao_info['remark']='可以领取';

        $this->ajaxReturn($hongbao_info,'可以领取',1);

    }

    // todo 领包
    public function get_hongbao(){
        $room_id=(int)$_POST['room_id'];
        $chang_id=(int)$_POST['chang_id'];
        $hongbao_id=(int)$_POST['hongbao_id'];//红包id

        $user_id=$this->uid;


        Cac()->incr("get_hongbao".$chang_id.$user_id);
        $num=Cac()->get("get_hongbao".$chang_id.$user_id);
        if ($num>1){
            $this->ajaxReturn(null,'领取失败!',0);
        }


        $hongbaoModel=D('Erba');
        //此处加强判断 已经领取  不允许重复领取
        if($hongbaoModel->is_recivedQ($hongbao_id,$user_id)){
            $this->ajaxReturn(null,'已经领取过该红包!',0);
        }

      //  判断是否下注的人点包
        if (!($hongbaoModel->xiazhu_info($chang_id,$user_id))){
            $this->ajaxReturn(null,"未下注者不允许抢红包!",0);
        }

        $kickback_id=$hongbaoModel->getOnekickid($hongbao_id);


        if ($kickback_id>0){
            //先把自己入队到已经领取
            $hongbaoModel->UserQueue($hongbao_id,$user_id);
            //设置kickback为已经领取
            $hongbaoModel->setkickbackOver($kickback_id,$user_id,$room_id,$chang_id);
            $kickback_info=$hongbaoModel->getkickInfo($kickback_id);

            //判断是否是最后一个 是的话开始同步数据库信息
            if($hongbaoModel->is_self_last($hongbao_id,$user_id,$room_id)){

                    //获取下注人信息
                 $xiazhu_info=$hongbaoModel->get_xiazhu($chang_id);

                //获取庄家信息
                $zhuang_info=$hongbaoModel->zhuang_info($room_id,$chang_id);

                //  庄家解冻金钱
                $hongbaoModel->zhuang_jiedong($zhuang_info);

                    // 用户给庄家进行对比 赔付
                $hongbaoModel->peifu($xiazhu_info,$zhuang_info);

                //设置mysql红包为领取状态为完毕
                $hongbaoModel->sethongbaoOver($hongbao_id);

                // 获取红包信息
                $hb_info= $hongbaoModel->hb_info($room_id,$chang_id);
                //获取最新庄家信息
                $zhuanginfo=$hongbaoModel->zhuang_info($room_id,$chang_id);

                 foreach ($hb_info as $k=>$v){
                     $userInfo=D('Users')->getUserByUid($hb_info[$k]['user_id']);
                     $hb_info[$k]['nickname']=$userInfo['nickname'];
                 }

                $zInfo=D('Users')->getUserByUid($zhuang_info['user_id']);
                $zhuanginfo['nickname']=$zInfo['nickname'];
                $data['zhuang_info']=$zhuanginfo;
                $data['xiazhu_info']=$hb_info;
                Cac()->del("qz_airtime_".$room_id);
                Cac()->del("qz_count_".$room_id);
                Cac()->del("qz_hongbao_id_".$room_id);
                Cac()->del('qz_money_'.$room_id);


                Cac()->set("qz_airtime_".$room_id,time());
                $start_time=Cac()->get("qz_airtime_".$room_id);
                $data['start_time']=5-(time()-$start_time);

                //通知这局信息
                $this->sendnotify_info($room_id,$data);

                //解除庄家领取锁
             //   Cac()->del("get_hongbao".$zhuang_info['user_id']);
                //解除下注人的锁
//                foreach ($xiazhu_info as $k=>$v){
//                    Cac()->del("get_hongbao".$xiazhu_info[$k]['user_id']);
//                }

                $UserModel=D('Users');
                $usermoney=$UserModel->getusermoney($user_id);
                if($usermoney<1){
                    Cac()->del("qz_chang_id_".$room_id);
                    //记录当前状态
                    $fj_info['status']=1;
                    $fj_info['nickname']="";
                    $fj_info['user_id']="";
                    $fj_info['money']="";
                    $fj_info['chang_id']="";
                    $fj_info['start_time']=-1;
                    $fj_info['shangzhuang_money']=300000;
                    Cac()->set("qz_status_".$room_id,serialize($fj_info));
                }else{

                //记录当前状态
                $fj_info= unserialize(Cac()->get("qz_status_".$room_id));
                $fj_info['status']=4;
                $fj_info['start_time']=5-(time()-$start_time);
                Cac()->set("qz_status_".$room_id,serialize($fj_info));
                }
                $this->ajaxReturn($kickback_info,'领取成功!!!',1);

            }else{
                $this->ajaxReturn($kickback_info,'领取成功!',1);
            }
        }

    }

    /**
     *
     * 红包详情
     *
     */
    public function getrecivelist(){
        //判断自己是否在
        $hongbao_id=(int)$_POST['hongbao_id'];
        $HbModel=D('Erba');
        $hongbao_info=$HbModel->getInfoById($hongbao_id);

        if(empty($hongbao_info)){
            $this->ajaxReturn('','红包不存在！',0);
        }
        $hbUser=D('Users')->getUserByUid($hongbao_info['user_id']);
        //判断超时
        if($hongbao_info['creatime']<time()-180){
            $timeout=1;
        }else{
            $timeout=0;
        }

        if($HbModel->isfinish()){
            $finish=1;
        }else{
            $finish=0;
        }
        $kickList=$HbModel->getkickListInfo($hongbao_id,$this->uid);

        if($HbModel->is_recived($hongbao_id,$this->uid)){
            $recived=1;
            $res['is_selfin']=1;
            $res['selfmoney']=$kickList['money'];
            if($hongbao_info['bom_num']==substr($kickList['money'],-1)){
                $res['is_bom']=1;
            }else{
                $res['is_bom']=0;
            }
        }else{
            $recived=0;
            $res['is_selfin']=0;
            $res['selfmoney']=0;
        }

        $res['hongbao_id']=$hongbao_id;
        $res['username']=$hbUser['nickname'];
        $res['avatar']=$hbUser['face'];
        if($res['avatar']==""){
            $res['avatar']="img/avatar.png";
        }
        $res['money']=$hongbao_info['money'];
        $res['bom_num']=$hongbao_info['bom_num'];
        $res['recive_num']=$kickList['num'];
        $res['check']=$kickList['check'];
        $res['nums']=7;
        $res['list']=$kickList['list'];
        foreach ($res['list'] as &$v){
            $v['recivetime']=date('H:i:s',$v['recivetime']);
            if($v['user_id']>0){
                $userTemp=D('Users')->getUserByUid($v['user_id']);
                $v['username']=$userTemp['nickname'];
                $v['avatar']=$userTemp['face'];
                if($v['avatar']==""){
                    $v['avatar']="img/avatar.png";
                }
            }

        }


        if($timeout==0&&$finish==0&&$recived==0){
            $this->ajaxReturn('','红包未领取！',0);
        }
        $this->ajaxReturn($res,'请求成功！',1);
    }






  // todo  用户下庄
    public function xiazhuang(){

        $room_id=(int)$_POST['room_id'];
        //记录当前状态

        $fj_info['status']=1;
        $fj_info['nickname']="";
        $fj_info['money']="";
        $fj_info['chang_id']="";
        $fj_info['start_time']=-1;
        $fj_info['user_id']="";
        Cac()->set("qz_status_".$room_id,serialize($fj_info));

        Cac()->del("qz_airtime_".$room_id);
        // Cac()->del("qz_count_".$room_id);
        Cac()->del('qz_money_'.$room_id);
        Cac()->del('qz_hongbao_id_'.$room_id);
        Cac()->del("qz_chang_id_".$room_id);
        Cac()->del("qz_count_".$room_id);
        $this->sendnotify($room_id,$fj_info,7);
        $this->ajaxReturn(array(),'用户下庄!');

    }

    //todo 下注记录
    public function xiazhuinfo(){
        $user_id=$this->uid;
     $data=D()->query("SELECT *,(SELECT num FROM bao_erba_zhuang where chang_id=a.chang_id)as zhuang_num ,(SELECT hb_money FROM bao_erba_zhuang where chang_id=a.chang_id)as zhuang_money from  bao_erba_xiazhu as a    where user_id=$user_id   order by  a.id desc LIMIT 10 
");

       $this->ajaxReturn($data,'下注记录');
    }

    //todo  庄家记录
    public function zhuanginfo(){
        $user_id=$this->uid;
        $data= D()->query("select * from  bao_erba_zhuang WHERE   user_id=$user_id  ORDER  by chang_id DESC  limit 10");
        $this->ajaxReturn($data,'庄家记录');
    }
    //todo 庄家开奖记录
    public function kaijiang(){
        $data= D()->query("select * from  bao_erba_zhuang  ORDER  by chang_id DESC  limit 10");

        $this->ajaxReturn($data,'庄家开奖记录');
    }

    //todo  获取记录
    public function get_status(){
        $room_id=(int)$_POST['room_id'];
        $fj_info=unserialize( Cac()->get("qz_status_".$room_id));

           if ($fj_info['status'] ==1){
               $air_time=Cac()->get("qz_airtime_".$room_id);
               if (empty($air_time)){
                   //记录当前状态
                   $fj_info['status']=1;
                   $fj_info['nickname']="";
                   $fj_info['money']="";
                   $fj_info['chang_id']="";
                   $fj_info['start_time']=-1;
                   $fj_info['user_id']="";
                   $fj_info['shangzhuang_money']=300000;

                   Cac()->set("qz_status_".$room_id,serialize($fj_info));
                   $this->ajaxReturn($fj_info,'当前房间数据');
               }else{
                   $fj_info['start_time']=20-(time()-$air_time);
                   $this->ajaxReturn($fj_info,'当前房间数据');
               }

           }
           if ($fj_info['status'] ==2){
               $air_time=Cac()->get("qz_airtime_".$room_id);
               $fj_info['start_time']=60-(time()-$air_time);
               $this->ajaxReturn($fj_info,'当前房间数据');
           }
           if ($fj_info['status'] ==3){
               $air_time=Cac()->get("qz_airtime_".$room_id);
               $fj_info['start_time']=10-(time()-$air_time);
               $this->ajaxReturn($fj_info,'当前房间数据');
           }
        if ($fj_info['status'] ==4){
            $air_time=Cac()->get("qz_airtime_".$room_id);
            $fj_info['start_time']=5-(time()-$air_time);
            $this->ajaxReturn($fj_info,'当前房间数据');
        }

    }


    private function sendnotify($room_id,$userinfo,$type)
    {

        Gateway::$registerAddress = '127.0.0.1:1238';
        if($userinfo['face']=='img/avatar.png'){
            $userinfo['face']='';
        }
        $data=array(
            'roomid'=>$room_id,
            'm'=>1,
            'type'=>$type,
            'data'=>array(
                'username'=>$userinfo['nickname'],
                'user_id'=>$userinfo['user_id'],
                'money'=>$userinfo['money'],
                'avatar'=>$userinfo['face'],
                'chang_id'=>$userinfo['chang_id'],
                'start_time'=>$userinfo['start_time']

            )
        );
        $data=json_encode($data);
        Gateway::sendToAll($data);
    }
    private function sendnotify_hb($room_id,$userinfo,$type)
    {

        Gateway::$registerAddress = '127.0.0.1:1238';
        if($userinfo['face']=='img/avatar.png'){
            $userinfo['face']='';
        }
        $data=array(
            'roomid'=>$room_id,
            'm'=>1,
            'type'=>$type,
            'data'=>array(
                'username'=>$userinfo['nickname'],
                'user_id'=>$userinfo['user_id'],
                'money'=>$userinfo['money'],
                'avatar'=>$userinfo['face'],
                'chang_id'=>$userinfo['chang_id'],
                'hongbao_id'=>$userinfo['id']

            )
        );
        $data=json_encode($data);
        Gateway::sendToAll($data);
    }


    private function sendnotify_info($room_id,$hb_info)
    {
        Gateway::$registerAddress = '127.0.0.1:1238';

        $data=array(
            'roomid'=>$room_id,
            'm'=>2,
            'data'=>$hb_info,
             'start_time'=>$hb_info['start_time']
        );
        $data=json_encode($data);
        Gateway::sendToAll($data);
    }

    private function benotify($hb,$userinfo){
        Gateway::$registerAddress = '127.0.0.1:1238';
        $data=array(
            'roomid'=>$hb['roomid'],
            'm'=>3,
            'data'=>array(
                'username'=>$userinfo['nickname'],
                'user_id'=>$userinfo['user_id'],
                'avatar'=>$userinfo['face'],
                'hongbao_id'=>$hb['id'],
                'money'=>$hb['money'],
                'bom_num'=>$hb['bom_num']
            )
        );
        $data=json_encode($data);
        Gateway::sendToUid($hb['user_id'],$data);
    }


}