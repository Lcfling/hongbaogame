<?php
require_once LIB_PATH.'/GatewayClient/Gateway.php';

use GatewayClient\Gateway;

class JielongAction extends CommonAction{


    // 机器人发包
    public function start(){
        $user_id='0';
        $money='100';
        $roomid=(int)$_POST['roomid'];
        // $roomid='3735276';
        $roomData=D('Room')->getRoomData($roomid);
        if(empty($roomData)){
            $this->ajaxReturn('','房间不存在!',0);
        }

         // 判断队列中是否第一个红包
        $status=D('Jielong')->one_start($roomid);
        if (!$status){
           $this->ajaxReturn('','房间有红包!',0);
        }


        $robot=array('nickname'=>"机器人","user_id"=>0,"face"=>"");
        //机器人生成红包
        $hongbao_info=D('Jielong')->createhongbao_xt($money,5,$roomid,$user_id);
        if($hongbao_info){
            //通知
            $this->sendnotify($hongbao_info,$robot);
            $this->ajaxReturn('','发送完毕!',1);
        }else{
            $this->ajaxReturn('','红包发送失败！',0);
        }
    }

    //点击检测
    public function clickkickback(){
        $hongbao_id=(int)$_POST['hongbao_id'];

        $hongbaoModel=D('Jielong');
        //获取红包信息
        $hongbao_info=$hongbaoModel->getInfoById($hongbao_id);

        $userInfo=D('Users')->getUserByUid($hongbao_info['user_id']);

        $hongbao_info['username']=$userInfo['nickname'];

        if($hongbao_info['creatime']<time()-180){
            $hongbao_info['type']=2;
            $hongbao_info['remark']='红包过期';
            $this->ajaxReturn($hongbao_info,'红包过期!',1);
        }
        // 判断红包是否被领取
        if($hongbaoModel->isfinish($hongbao_id)){
            $hongbao_info['type']=3;
            $hongbao_info['remark']='红包已经领取完毕!';
            $this->ajaxReturn($hongbao_info,'红包已经领取完毕!',1);
        }
        //此处加强判断 已经领取  不允许重复领取
        if($hongbaoModel->is_recived($hongbao_id,$this->uid)){
            $hongbao_info['type']=4;
            $hongbao_info['remark']='已经领取过次红包!';
            $this->ajaxReturn($hongbao_info,'已经领取过次红包!',1);
        }
        //余额判断   余额小于红包总金额 不允许强

        $hongbao_info['type']=1;
        $hongbao_info['remark']='可以领取';

        $this->ajaxReturn($hongbao_info,'可以领取',1);

    }

    //开包
    public function openkickback(){

        $hongbao_id=(int)$_POST['hongbao_id'];//红包id
        $hongbaoModel=D('Jielong');
        $hongbao_info=$hongbaoModel->getInfoById($hongbao_id);

        if(empty($hongbao_info)){
            $this->ajaxReturn('','红包不存在！',0);
        }
        $userMoney=D('Users')->getUserMoney($this->uid);
        // 查找房间的信息
        //     $room_info= $hongbaoModel->RoomData($hongbao_info['roomid']);

        if($userMoney<1000){
            $info['type']=5;
            $info['remark']='余额不足!';
            $this->ajaxReturn('','余额不足!',0);
        }
        //此处加强判断 已经领取  不允许重复领取
        if($hongbaoModel->is_recivedQ($hongbao_id,$this->uid)){
            $this->ajaxReturn('','已经领取过次红包!',0);
        }


        $kickback_id=$hongbaoModel->getOnekickid($hongbao_id);
        if($kickback_id>0){

            //把领取人的资金冻结
            $hongbaoModel->freezeMoney($this->uid, 1000);


            //先把自己入队到已经领取
            $hongbaoModel->UserQueue($hongbao_id,$this->uid);
            //设置kickback为已经领取
            $hongbaoModel->setkickbackOver($kickback_id,$this->uid);
            $kickback_info=$hongbaoModel->getkickInfo($kickback_id);

            //判断是否机器人发包
            $is_start=$hongbaoModel->is_start($hongbao_id);
            if (!$is_start){
                $money=$kickback_info['money'];
                D('Users')->addmoney($this->uid,$money,2,1,'接龙抢包');
            }


            //领取通知
            $userinfo=D('Users')->getUserByUid($this->uid);

            $this->benotify($hongbao_info,$userinfo);

            //判断是否是最后一个 是的话开始同步数据库信息
            if($hongbaoModel->is_self_last($hongbao_id,$this->uid)){
                //  把所有冻结的资金解冻
                $hongbaoModel->jiedong($hongbao_id,1000);
                //  取出钱最少的那个人信息
                $min_user=$hongbaoModel->min_user($hongbao_id);
                D('Users')->reducemoney($min_user['user_id'],1000,4,1,'接龙发包');//减少发包人的金额
                $hb_info=$hongbaoModel->createhongbao(1000,5,$hongbao_info['roomid'],$min_user['user_id']);
                //设置mysql红包为领取状态为完毕
                $hongbaoModel->sethongbaoOver($hongbao_id);

                $this->sendnotify($hb_info,$min_user);

                $this->ajaxReturn('','领取成功!!!!!!',1);
            }else{

                $this->ajaxReturn('','领取成功!',1);
            }
        }else{
            $this->ajaxReturn('','手慢了，领取完了!',0);
        }
    }





    public function Gameover(){

        $hongbao=D('Hongbao_jielong');
        $where['is_over']=array('EQ',0);
        $list=$hongbao->order('id desc')->limit(1)->find();
        if (empty($list)){
            $hongbao->delete_start($list['roomid']);
            $this->ajaxReturn('','游戏结束!',1);
        }

        $hongbao_id=(int)$list['id'];
        $hongbaoModel=D('Jielong');
        if (!$hongbaoModel->isfinish($hongbao_id)){


            $timediff =time()-$list['creatime'];
            //计算分钟数
            $mins = intval($timediff/60);
            if ($mins >1){

                $hb_where['id']=$list['id'];
                $hb_where['roomid']=$list['roomid'];
                $hb_save['is_over']=1;
                $hb_save['overtime']=time();
                $hongbao->where($hb_where)->save($hb_save);

                if ($list['is_start'] !='1'){
                    $where['hb_id']=$hongbao_id;
                    $where['user_id']='0';
                    $where['is_robot']=array('NEQ',1);
                    $kickInfo=D('Kickback_jielong')->where($where)->select();
                    //退回红包剩余金额
                    foreach ($kickInfo as $k=>$v){
                        D('Users')->addmoney($list['user_id'],$kickInfo[$k]['money'],2);
                    }
                }


                //解冻领取人的金额
                $hongbaoModel->jiedong($hongbao_id,1000);

                $hongbao->delete_start($list['roomid']);
                $this->ajaxReturn('','游戏结束!',1);
            }else{
                $this->ajaxReturn('','游戏继续!',1);
            }

        }

    }
    //执行发红包
    public function dosend(){
        $money=(int)($_POST['money']*100);
        //  $bom_num=(int)$_POST['bom_num'];
        $roomid=(int)$_POST['roomid'];


        //todo 在这里验证支付密码
        $roomData=D('Room')->getRoomData($roomid);
        if(empty($roomData)){
            $this->ajaxReturn('','房间不存在!',0);
        }
        //金额判断
        if($money>$roomData['conf_max']||$money<$roomData['conf_min']){
            $this->ajaxReturn('','请选择正确的金额 '.$roomData['conf_min'].'-'.$roomData['conf_max'],0);
        }
        //余额判断判断
        $userMoney=D('Users')->getUserMoney($this->uid);
        if($userMoney<$money){
            $this->ajaxReturn('','余额不足，请充值!'. $userMoney,0);
        }
        //生成红包
        $hongbao_info=D('Hongbao_jielong')->createhongbao_jielong($money,5,$roomid,$this->uid);
        if($hongbao_info){
            D('Users')->reducemoney($this->uid,$money,4,1,'发送红包');
            //通知
            $this->sendnotify($hongbao_info,$this->member,$hongbao_info['roomid']);
            $this->ajaxReturn('','发送完毕!',1);
        }else{
            $this->ajaxReturn('','红包发送失败！',0);
        }
    }
    // 房间红包记录
    public function getlist(){
        $roomid=(int)$_POST['roomid'];
        $list=D('Hongbao_jielong')->where('roomid='.$roomid)->order('id DESC')->limit(10)->select();
        $list=array_reverse($list);
        foreach ($list as &$value){
            $user=D('Users')->getUserByUid($value['user_id']);
            $value['username']=$user['nickname'];
            if($user['face']==""){
                $value['avatar']="img/avatar.png";
            }else{
                $value['avatar']=$user['face'];
            }
        }
        $this->ajaxReturn($list);
    }

    private function awordnotify($hb,$userinfo,$aword,$type){
        //中奖判断  全局通知
        //1.领包通知
        Gateway::$registerAddress = '127.0.0.1:1238';
        $data=array(
            'roomid'=>$hb['roomid'],
            'm'=>2,
            'data'=>array(
                'username'=>$userinfo['nickname'],
                'user_id'=>$userinfo['user_id'],
                'aword'=>$aword,
                'money'=>$hb['money'],
                'hongbao_id'=>$hb['id'],
                'bom_num'=>$hb['bom_num'],
                'type'=>$type
            )
        );
        $data=json_encode($data);
        Gateway::sendToAll($data);
    }

    private function sendnotify($hb,$userinfo)
    {
        Gateway::$registerAddress = '127.0.0.1:1238';
        if($userinfo['face']=='img/avatar.png'){
            $userinfo['face']='';
        }
        $data=array(
            'roomid'=>$hb['roomid'],
            'm'=>1,
            'data'=>array(
                'username'=>$userinfo['nickname'],
                'user_id'=>$userinfo['user_id'],
                'avatar'=>$userinfo['face'],
                'hongbao_id'=>$hb['id'],
                'money'=>$hb['money'],
            )
        );
        $data=json_encode($data);
        Gateway::sendToAll($data);
    }
    public function aotudosend(){
        //获取随机用户

        //获取房间列表
        $roomlist=D('Room')->getroomlist('saolei');
        foreach ($roomlist as $roomid){
            //查看一分钟内是否有当前房间内发送的红包
            if(D('Jielong')->issendIntime($roomid,60)){
                continue;
            }
            //获取随机用户
            $user=D('Users')->randUser();
            $roominfo=D('Room')->getroom($roomid);
            $money=$roominfo['conf_min'];
            $bom_num=rand_string(1,1);
            $hongbao_info=D('Jielong')->createhongbao($money,$bom_num,5,$roomid,$user['id']);
            if($hongbao_info){
                D('Users')->reducemoney($user['id'],$money,4,0,'发送红包');
                //通知
                $this->sendnotify($hongbao_info,$user,$hongbao_info['roomid']);
                continue;
            }
        }
    }

    //红包倍领取通知

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

    /**
     *
     * 红包详情
     *
     */
    public function getrecivelist(){
        //判断自己是否在
        $hongbao_id=(int)$_POST['hongbao_id'];
        $HbModel=D('Jielong');
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
        $kickList['list']=array_reverse($kickList['list']);
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
        $res['money']=$hongbao_info['money'];
        $res['bom_num']=$hongbao_info['bom_num'];
        $res['recive_num']=$kickList['num'];
        $res['check']=$kickList['check'];
        $res['nums']=5;
        $res['list']=$kickList['list'];
        foreach ($res['list'] as &$v){
            $v['recivetime']=date('H:i:s',$v['recivetime']);
            if($v['user_id']>0){
                $userTemp=D('Users')->getUserByUid($v['user_id']);
                $v['username']=$userTemp['nickname'];
                $v['face']=$userTemp['face'];
            }else{
                $v['username']='免死';
                $v['face']='img/miansi.jpg';
            }

        }


        if($timeout==0&&$finish==0&&$recived==0){
            $this->ajaxReturn('','红包未领取！',0);
        }
        $this->ajaxReturn($res,'请求成功！',1);
    }
}