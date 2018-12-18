<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-11-13
 * Time: 14:32
 */require_once LIB_PATH.'/GatewayClient/Gateway.php';

use GatewayClient\Gateway;
class TestAction extends CommonAction{





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








    //  todo 绑定银行卡
    public  function  add_bank(){


        $user_name=I('post.user_name','','strip_tags');
        $bank_num=(int)$_POST['bank_num'];
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
            $this->ajaxReturn($info,'绑定成功!');
        }else{
            $this->ajaxReturn(null,'绑定失败!',0);
        }
    }




   //抢庄
    public function getrob(){

        //获取缓存抢庄信息
        //$room_id=(int)$_POST['room_id'];

        $room_id=3735277;
        $fj_info=unserialize( Cac()->get("qz_status_".$room_id));

        if ($fj_info['status'] ==1){
            $air_time=Cac()->get("qz_airtime_".$room_id);

            if (!empty($air_time)){
             //   echo 20-(time()-$air_time);
                if (20-(time()-$air_time)<0){

                    $ErbaModel=D('Erba');
                    // 获取抢庄信息
                    $data=$ErbaModel->get_qz($room_id);
                    Cac()->del("qz_user_".$room_id);
                    //取出抢庄信息金额最大的  未成功抢庄数据进行金额解冻
                    $list=$ErbaModel->max_money($data);

                    //数据库存入庄家场次信息
                    $zhuang=$ErbaModel->add_zhuang($list,$room_id);

                    if ($zhuang){
                        //获取庄家信息
                        $UserModel=D('Users');
                        $user_info= $UserModel->getuserbyuid($zhuang['user_id']);
                        $zhuang['nickname']=$user_info['nickname'];

                        //  存储场次
                        Cac()->set("qz_chang_id_".$room_id,$zhuang['chang_id']);
                        //下注开始时间
                        Cac()->set("qz_airtime_".$room_id,time());
                        $start_time=Cac()->get("qz_airtime_".$room_id);
                        $zhuang['start_time']=60-(time()-$start_time);
                        // 通知系统庄家信息  开始下注
                        $this->sendnotify($room_id,$zhuang,2);
//
                        //记录当前状态
                        $fj_info= unserialize(Cac()->get("qz_status_".$room_id));

                        $fj_info['status']=2;
                        $fj_info['nickname']=$user_info['nickname'];
                        $fj_info['money']=$zhuang['money'];
                        $fj_info['chang_id']=$zhuang['chang_id'];
                        $fj_info['start_time']=60-(time()-$start_time);
                        Cac()->set("qz_status_".$room_id,serialize($fj_info));
                        $this->ajaxReturn($zhuang,'庄家信息');

                    }
                }
            }

        }


    }

    //  todo  发包
    public function fabao(){

        //  $room_id=$_POST['room_id'];
        $room_id=3735277;
        $fj_info=unserialize( Cac()->get("qz_status_".$room_id));


        if ($fj_info['status'] ==2) {
            $air_time = Cac()->get("qz_airtime_" . $room_id);
           // echo  60-(time() - $air_time);
            if (60 - (time() - $air_time) < 0) {
                $chang_id=Cac()->get("qz_chang_id_".$room_id);

                Cac()->del("qztime_".$room_id,time());
              //  Cac()->del("qz_chang_id_".$room_id);
                // $chang_id=(int)$_POST['chang_id'];
                //获取下注人数
                $count=Cac()->get("qz_count_".$room_id);

                //判断是否有人下注
                if (empty($count)){
                    //初始化房间数据  玩家重新上庄
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
                    Cac()->del("qz_chang_id_".$room_id);
                    Cac()->del("qz_count_".$room_id);


                    //获取庄家信息解冻金额
                    $ErbaModel=D('Erba');
                    $info=$ErbaModel->zhuang_info($room_id,$chang_id);
                    $ErbaModel->zhuang_jiedong($info);

                    $this->sendnotify($room_id,$fj_info,5);
                    $this->ajaxReturn(array(),'无人下注!');
                }


                //获取发包金额
                $money=($count+1)*88;
                $ErbaModel=D('Erba');
                // 获取庄家信息
                $info=$ErbaModel->zhuang_info($room_id,$chang_id);
                //生成红包          钱,人数 房间id 场次id 庄家信息
                $hongbao_info=D('Erba')->createhongbao($money,$count+1,$room_id,$chang_id,$info['user_id']);
                if($hongbao_info){

                    $UserModel=D('Users');
                    $user_info= $UserModel->getuserbyuid($hongbao_info['user_id']);
                    $hongbao_info['face']=$user_info['face'];
                    $hongbao_info['nickname']=$user_info['nickname'];

                    Cac()->set("qz_airtime_".$room_id,time());
                    $start_time=Cac()->get("qz_airtime_".$room_id);
                    $hongbao_info['start_time']=10-(time()-$start_time);  //点包开始时间
                    //   发送红包信息
                    $this->sendnotify_hb($room_id,$hongbao_info,4);
                    //存贮红包信息
                    Cac()->set("qz_hongbao_id_".$room_id,$hongbao_info['id']);

                    Cac()->del('qz_money_'.$room_id);

                    //记录当前状态
                    $fj_info= unserialize(Cac()->get("qz_status_".$room_id));
                    $fj_info['status']=3;
                    $fj_info['start_time']=10-(time()-$start_time);
                    Cac()->set("qz_status_".$room_id,serialize($fj_info));

                    $this->ajaxReturn($hongbao_info,'发包成功!');
                }
            }

        }

    }

        //自动抢包
    public function zidong(){

        //记录当前状态
        $room_id=3735277;
        $chang_id=Cac()->get("qz_chang_id_".$room_id);
        $hongbao_id=Cac()->get("qz_hongbao_id_".$room_id);
        $fj_info=unserialize( Cac()->get("qz_status_".$room_id));
//        echo "chang_id=".$chang_id;
//        echo "hongbao_id =".$hongbao_id;

        if ($fj_info['status'] ==3) {
            $air_time = Cac()->get("qz_airtime_" . $room_id);
           // echo 10 - (time() - $air_time);
            if (10 - (time() - $air_time) < 0) {

                $zhuang=M('erba_zhuang');
                $where['room_id']=$room_id;
                $where['chang_id']=$chang_id;
                $where['is_get']=0;
                $zhuang_info=$zhuang->where($where)->find();
              //  print_r($zhuang_info);
                if (!empty($zhuang_info)){

                    $this->zidong_qb($room_id,$chang_id,$hongbao_id,$zhuang_info['user_id']);
                }

                //获取未领取的用户
                $xiazhu=M('erba_xiazhu');
                $where['room_id']=$room_id;
                $where['chang_id']=$chang_id;
                $where['is_get']=0;
                $info=$xiazhu->where($where)->select();
               // print_r($info);
                if (!empty($info)){
                    foreach ($info as $k=>$v){
                        $this->zidong_qb($room_id,$chang_id,$hongbao_id,$info[$k]['user_id']);
                    }
                }


            }
        }


    }

    public function zidong_qb($room_id,$chang_id,$hongbao_id,$user_id){

        Cac()->incr("get_hongbao".$chang_id.$user_id);
        $num=Cac()->get("get_hongbao".$chang_id.$user_id);
        if ($num>1){
            $this->ajaxReturn(null,'领取失败!',0);
        }


        $hongbaoModel=D('Erba');

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


            //    Cac()->del("get_hongbao".$zhuang_info['user_id']);

//                foreach ($xiazhu_info as $k=>$v){
//                    Cac()->del("get_hongbao".$xiazhu_info[$k]['user_id']);
//                }

                $UserModel=D('Users');
                $usermoney=$UserModel->getusermoney($user_id);
                if($usermoney<1){
                  //  Cac()->del("qz_chang_id_".$room_id);
                    //记录当前状态
                    $fj_info['status']=1;
                    $fj_info['nickname']="";
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




            }
        }
    }


    //todo 连庄
    public function lianzhuang()
    {


        $room_id = 3735277;
        $chang_id = Cac()->get("qz_chang_id_" . $room_id);
       // Cac()->del("qz_chang_id_".$room_id);
        $fj_info = unserialize(Cac()->get("qz_status_" . $room_id));

        if ($fj_info['status'] == 4) {
            $air_time = Cac()->get("qz_airtime_" . $room_id);

            if (5- (time() - $air_time) < 0) {
                $zhuang_info = M("erba_zhuang");
                $where['room_id'] = $room_id;
                $where['chang_id'] = $chang_id;
                $info = $zhuang_info->where($where)->find();

                $UserModel=D('Users');
                $usermoney=$UserModel->getusermoney($info['user_id']);

                if ($usermoney<$info['money']+10000){
                    Cac()->del("rob");
                    $this->xiazhuang();
                    $this->ajaxReturn(null,'余额不足，余额：'.$usermoney/100,'0');
                }


                //冻结庄家金钱
                $ErbaModel = D('Erba');
                $ErbaModel->lz_dongjie($info['user_id'],$info['money']+10000);
                $info['money']=$info['money']+10000;

                //数据库存入庄家场次信息
                $zhuang = $ErbaModel->add_zhuang($info,$room_id);

                if ($zhuang) {
                    //获取庄家信息
                    $UserModel = D('Users');
                    $user_info = $UserModel->getuserbyuid($zhuang['user_id']);
                    $zhuang['nickname'] = $user_info['nickname'];
                    //  存储场次
                    Cac()->set("qz_chang_id_".$room_id,$zhuang['chang_id']);

                    //下注开始时间
                    Cac()->set("qz_airtime_" . $room_id, time());
                    $start_time = Cac()->get("qz_airtime_" . $room_id);
                    $zhuang['start_time'] = 60 - (time() - $start_time);
                    // 通知系统庄家信息  开始下注
                    $this->sendnotify($room_id,$zhuang,6);
//
                    //记录当前状态
                    $fj_info = unserialize(Cac()->get("qz_status_".$room_id));

                    $fj_info['status'] = 2;
                    $fj_info['nickname'] = $user_info['nickname'];
                    $fj_info['user_id']=$user_info['user_id'];
                    $fj_info['money'] = $zhuang['money'];
                    $fj_info['chang_id'] = $zhuang['chang_id'];
                    $fj_info['start_time'] = 60 - (time() - $start_time);
                    Cac()->set("qz_status_" . $room_id, serialize($fj_info));
                    $this->ajaxReturn($zhuang, '庄家信息');

                }
            }

        }
    }




    public function qz(){
        $room_id=3735277;
         Cac()->del("qz_user_".$room_id);
        Cac()->del("qztime_".$room_id);
    }

        public function suo()
        {
            $num = Cac()->get("rob");
            echo $num;
        }
        public function suo1(){
            Cac()->del("aaa");
        }
        public function kaisuo(){
            $user_id=6666736;
            // 判断用户余额
            $UserModel=D('Users');
            $usermoney=$UserModel->getusermoney($user_id);
           echo $usermoney;
        }


    public function zhuang(){
        $user_id=6667022;
       $data= D()->query("SELECT *,(SELECT num FROM bao_erba_zhuang where chang_id=a.chang_id)as zhuang_num,(SELECT hb_money FROM bao_erba_zhuang where chang_id=a.chang_id)as zhuang_money from  bao_erba_xiazhu as a    where user_id=$user_id    order by  a.id desc LIMIT 10 
");
        print_r($data);
    }

public function zhuang1(){
    $user_id=6667022;

   $data= D()->query("select * from  bao_erba_zhuang WHERE   user_id=$user_id  ORDER  by chang_id DESC  limit 10");
   $this->ajaxReturn($data,'数组');

}
    public function zhuang2(){
        $user_id=6667022;

        $data= D()->query("select * from  bao_erba_zhuang  ORDER  by chang_id DESC  limit 10");
        print_r($data);



    $num=1;


            if ($num <10){
                echo "这是点数";
            }

        if ($num >10 && $num<100){
            echo "这是对子";
        }
        if ($num ==100){
            echo  "这是28杠";
        }

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
                'hongbao_id'=>$userinfo['id'],
                'start_time'=>$userinfo['start_time']

            )
        );
        $data=json_encode($data);
        Gateway::sendToAll($data);
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


    public function xiazhuang(){
        $room_id=3735277;
      //  $room_id=(int)$_POST['room_id'];
        //记录当前状态

        $fj_info['status']=1;
        $fj_info['nickname']="";
        $fj_info['money']="";
        $fj_info['chang_id']="";
        $fj_info['start_time']=-1;
        Cac()->set("qz_status_".$room_id,serialize($fj_info));

        Cac()->del("qz_airtime_".$room_id);
        // Cac()->del("qz_count_".$room_id);
        Cac()->del('qz_money_'.$room_id);
        Cac()->del("qz_chang_id_".$room_id);
        Cac()->del("qz_count_".$room_id);
        $this->sendnotify($room_id,$fj_info,7);
        $this->ajaxReturn(array(),'用户下庄!');

    }

    public function s2(){
        $room_id="3735277";
        $qz_time=Cac()->get("qztime_".$room_id);
        echo "qz_time111=".$qz_time;
    }



}