<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-11-13
 * Time: 14:32
 */
class TestAction extends CommonAction{

    public function str(){
        $str= rand(100,999);
        echo "数字为:".$str;
        //$str= $_GET['str'];
        $str1 = substr($str,-1);
        $str2=substr($str,1,1);

        $sum=$str1+$str2;

        if ($str1 == $str2){
            echo "您的点数为:对子";
            die();
        }
        if ( ($str1 == 2 && $str2 ==8) || ($str1 ==8 && $str2 == 2)){
            echo "您的点数为:二八杠";
            die();
        }
        $sum1= substr($sum,-1);
        echo "您的点数为:".$sum1;
        die();

    }
    //抢庄
    public function up(){
        $room_id=(int)$_POST['room_id'];
        $user_id=$this->uid;
        $money=(int)$_POST['money'];

        $UserModel=D('Users');
        $usermoney=$UserModel->getusermoney($user_id);
        if($usermoney<3000){
            $this->ajaxReturn('','余额不足3000，余额：'.$usermoney,'0');
        }
        // 冻结抢庄的钱
        $this->qz_dongjie($user_id,$money);

        //获取用户信息
        $user_info= $UserModel->getuserbyuid($user_id);
        $data['user_id']=$user_id;
        $data['money']=$money;
        $data['nickname']=$user_info['nickname'];
        //抢庄信息存入缓存
        Cac()->set("qz_".$room_id,$data);
        // 通知系统抢庄信息
        $this->sendnotify($room_id,$data);
        $this->ajaxReturn($data,"抢庄");
    }
    public function getup(){

         //获取缓存抢庄信息
        $room_id=$_POST['room_id'];
        $data=Cac()->get("qz_".$room_id);
        //取出抢庄信息金额最大的  未成功抢庄数据进行金额解冻
       $list= $this->max_money($data);
        //数据库存入庄家场次信息
        $zhuang=$this->zhuang($list);
        // 通知系统庄家信息  开始下注
        $this->sendnotify($zhuang,$list);
        //清空抢庄信息
        Cac()->delete("qz_".$room_id);
        Cac()->set("qz_count_".$room_id,1);

    }

    //todo 下注
    public function ble_money(){
        $room_id=(int)$_POST['room_id'];
        $user_id=$this->uid;
        $money=(int)$_POST['money'];
        $chang_id=(int)$_POST['chang_id'];

        // 是否是下注时间
        if (!($this->if_time($room_id,$chang_id)) ){
            $this->ajaxReturn('','已经封盘!');
        }

        // 判断用户余额
        $UserModel=D('Users');
        $usermoney=$UserModel->getusermoney($user_id);
        if($usermoney<$money*100*5){
            $this->ajaxReturn('','余额不足请充值!');
        }

        //  判断庄剩余注额
        if ( !($this->if_money($user_id,$money)) ){
            $this->ajaxReturn('','超出盘口了!');
        }

        //进行下注
        $user_list=$this->xiazhu($user_id,$money,$chang_id);
        //通知系统下注信息
        $this->sendnotify($room_id,$user_list);
        //抢庄人数++
        Cac()->incr("qz_count_".$room_id);
        $this->ajaxReturn('','下注成功!');
    }

    //  todo  发包
    public function fabao(){

    }

    public function time(){
        //开始时间
        $begintime=date("Y-m-d H:i:s",mktime(10,0,0,date('m'),date('d'),date('Y')));
        $begintime=strtotime($begintime);
        //结束时间
        $overtime=date("Y-m-d H:i:s",mktime(22,00,0,date('m'),date('d'),date('Y')));
        $overtime=strtotime($overtime);
        //当前时间
        $time=time();

        if ( !($time>$begintime && $time<$overtime)){
            echo " 提现时间为10:00--22:00期间! ";
        }else{
            echo " 是提现时间";
        }

    }
}