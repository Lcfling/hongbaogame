<?php
require_once LIB_PATH.'/GatewayClient/Gateway.php';

use GatewayClient\Gateway;

class GameAction extends CommonAction
{

    //进入游戏房间  初始化游戏数据
    public function index(){
        $roomid=$_POST['roomid'];

        $GameModel=D('Game');
        if($GameModel->is_free($roomid)){
            $time=60-time()+strtotime(date('H:i',time()));
            $data['timer']=$time;
            $data['status']=1;
            $this->ajaxReturn($data,'','1');
        }
        $GameInfo=$GameModel->getNewInfo($roomid);

        if($GameInfo['creatime']>time()-20){
            $time=$GameInfo['creatime']+20-time();
            $data['timer']=$time;
            $data['status']=2;
            $this->ajaxReturn($data,'','1');
        }
        //黄筛子
        if($GameInfo['creatime']>time()-30){
            $time=$GameInfo['creatime']+30-time();
            $data['timer']=$time;
            $data['status']=3;
            $this->ajaxReturn($data,'','1');
        }
        //数据结算
        if($GameInfo['creatime']>time()-40){
            $time=$GameInfo['creatime']+40-time();
            $data['timer']=$time;
            $data['status']=4;
            $this->ajaxReturn($data,'','1');
        }
        //庄家是否继续
        if($GameInfo['creatime']>time()-50){
            $time=$GameInfo['creatime']+50-time();
            $data['timer']=$time;
            $data['status']=5;
            $this->ajaxReturn($data,'','1');
        }
    }
    //强庄
    public function rob(){
        $roomid=$_POST['roomid'];
        $UserModel=D('Users');
        $usermoney=$UserModel->getusermoney($this->uid);
        if($usermoney<300000){
            $this->ajaxReturn('','余额不足3000，余额：'.$usermoney,'0');
        }

        $GameModel=D('Game');
        if(!$GameModel->is_free($roomid)){
            //$GameModel->Robqueue($roomid);
            $this->ajaxReturn('','已经存在','0');
        }
        if(!$GameModel->Robqueue($roomid,$this->uid)){
            $this->ajaxReturn('','手慢了！','0');
        }
        //生成一局游戏
        $frozenMoney=300000;
        $UserModel->frozen($this->uid,$frozenMoney);
        $res=$GameModel->creategame($this->uid,$roomid);
        $GameModel->unLock($roomid);
        //通知来时下注
        unset($res['out_number']);
        $this->notifyGameStart($res);
        $this->ajaxReturn('','抢庄成功！','1');
    }



    public function test(){
        D('Game')->unLock(3735275);
    }


    //投注
    public function betting(){
        $roomid=(int)$_POST['roomid'];
        $betmoney=(int)$_POST['money'];
        $betType=(int)$_POST['bettype'];
        $GameModel=D('Game');
        $GameInfo=$GameModel->getNewInfo($roomid);
        $startTime=$GameInfo['creatime'];
        $endTime=$GameInfo['creatime']+20;
        $betList=array(1, 2, 3, 4, 13, 14, 23, 24);
        if(!in_array($betType,$betList)){
            $this->ajaxReturn('','类型错误！','0');
        }
        if(time()>$endTime){
            $this->ajaxReturn('','已经封盘！time='.time().' end='.$endTime,'0');
        }
        if($betType>10){
            $multiple=3.5;
        }else{
            $multiple=2;
        }
        $UserModel=D('Users');
        $usermoney=$UserModel->getusermoney($this->uid);
        $pankou=3000;
        $allbetmoney=$GameModel->getAllbetmoney($GameInfo['id']);
        if($usermoney<$betmoney){
            $this->ajaxReturn('','余额不足,请充值','0');
        }
        if($allbetmoney>2500){
            $this->ajaxReturn('','超出盘口了','0');
        }
        $ablebet=2500-$allbetmoney;
        if($betmoney*$multiple>$ablebet){
            $this->ajaxReturn('','超出盘口了','0');
        }
        //插入自己的bet
        $GameModel->insertbet($betmoney,$GameInfo['id'],$betType,$this->uid);
        //todo 获取盘口 Gateway通知
        $this->ajaxReturn('','下注成功','1');

    }

    //结算
    public function balance(){
        $roomid=$_GET['roomid'];
        $gameInfo=D('Game')->getNewInfo($roomid);
        $betModle=D('Betted');
        $this->notifybalance($gameInfo);
        $numarr=explode(" ", $gameInfo['out_number']);
        $outotle=0;
        foreach ($numarr as $value){
            $outotle+=$value;
        }
        if($outotle>9){
            $dx=1;
        }else{
            $dx=2;
        }
        if($outotle%2>0){
            $ds=3;
        }else{
            $ds=4;
        }
        $UserModel=D('Users');
        $UserModel->unfrozen($gameInfo['user_id'],300000);
        $fuhe=(int)($dx.$ds);
        $betlist=$betModle->where('game_id='.$gameInfo['id'])->select();
        foreach ($betlist as $v){
            if($v['bettype']>10){
                if($v['bettype']==$fuhe){
                    $UserModel->addmoney($v['user_id'],$v['multmoney']*0.95-$v['money'],11);
                    $UserModel->reducemoney($gameInfo['user_id'],$v['money'],21);
                    $res['roomid']=$roomid;
                    $res['money']=$v['multmoney']*0.95;
                    $this->notify($v['user_id'],$res);//入账通知
                }else{
                    $UserModel->addmoney($gameInfo['user_id'],$v['money']*0.95,11);
                    $UserModel->reducemoney($v['user_id'],$v['money'],21);
                    $res['roomid']=$roomid;
                    $res['money']=$v['money']*0.95;
                    $this->notify($gameInfo['user_id'],$res);//入账通知
                }
                //todo
            }else if($v['bettype']==3||$v['bettype']==4){
                if($v['bettype']==$ds){
                    $UserModel->addmoney($v['user_id'],$v['multmoney']*0.95,11);
                    $UserModel->reducemoney($gameInfo['user_id'],$v['multmoney'],21);
                    $res['roomid']=$roomid;
                    $res['money']=$v['multmoney']*0.95;
                    $this->notify($v['user_id'],$res);//入账通知
                }else{
                    $UserModel->addmoney($gameInfo['user_id'],$v['money']*0.95,11);
                    $res['roomid']=$roomid;
                    $res['money']=$v['money']*0.95;
                    $this->notify($gameInfo['user_id'],$res);//入账通知
                }
            }else if($v['bettype']==1||$v['bettype']==2){
                if($v['bettype']==$dx){
                    $UserModel->addmoney($v['user_id'],$v['multmoney']*0.95,11);
                    $UserModel->reducemoney($gameInfo['user_id'],$v['multmoney'],21);
                    $res['roomid']=$roomid;
                    $res['money']=$v['multmoney']*0.95;
                    $this->notify($v['user_id'],$res);//入账通知
                }else{
                    $UserModel->addmoney($gameInfo['user_id'],$v['money']*0.95,11);
                    $res['roomid']=$roomid;
                    $res['money']=$v['money']*0.95;
                    $this->notify($gameInfo['user_id'],$res);//入账通知
                }
            }
        }

    }
    public function createresult(){
        $roomid=$_GET['roomid'];

        $GameModel=D('Game');
        $GameInfo=$GameModel->getNewInfo($roomid);
        if($GameModel->is_createresult($GameInfo['id'])){
            $this->ajaxReturn('','','0');
        }
        //$GameInfo=$GameModel->getNewInfo($roomid);

        //echo $resultStr;

        //通知房间的用户开始开奖
        $this->notifyresult($GameInfo);


    }

    public function isgoon(){
        $roomid=$_GET['roomid'];
        $GameModel=D('Game');
        $GameInfo=$GameModel->getNewInfo($roomid);
        if(empty($GameInfo)){
            $this->ajaxReturn('','','0');
        }
        /*if($GameModel->goonQueue()){

        }*/
        if($GameInfo['goon']!=1){
            //生成游戏
            $usermoney=D('Users')->getusermoney($GameInfo['user_id']);
            if($usermoney<300000){
                //通知用户进入抢庄状态
                $this->notifystartrob($roomid);
            }else{
                $UserModel=D('Users');
                $frozenMoney=300000;
                $UserModel->frozen($GameInfo['user_id'],$frozenMoney);
                $res=$GameModel->creategame($GameInfo['user_id'],$roomid);
                //通知游戏开始
                $this->notifyGameStart($res);
            }
        }else{
            //通知用户进入抢庄状态
            $this->notifystartrob($roomid);
        }
    }
    public function down(){
        $roomid=$_POST['roomid'];
        $GameModel=D('Game');
        $GameInfo=$GameModel->getNewInfo($roomid);
        if(empty($GameInfo)){
            $this->ajaxReturn('','','0');
        }
        if($GameModel->down($GameInfo['id'])){
            $this->ajaxReturn('','下庄成功','1');
        }else{
            $this->ajaxReturn('','下庄失败','0');
        }
    }

    //通知游戏开始  开始下注
    private function notifyGameStart($res){
        Gateway::$registerAddress = '127.0.0.1:1238';
        $data=array(
            'roomid'=>$res['roomid'],
            'm'=>'gamestart',
            'data'=>$res,
        );
        $data=json_encode($data);
        Gateway::sendToAll($data);
    }

    //结果生成 通知用户开始展示结果
    private function notifyresult($res){
        Gateway::$registerAddress = '127.0.0.1:1238';
        $res['out_number']=explode(' ',$res['out_number']);
        $data=array(
            'roomid'=>$res['roomid'],
            'm'=>'result',
            'data'=>$res,
        );
        $data=json_encode($data);
        Gateway::sendToAll($data);
    }
    //结算之后 入账通知
    private function notifybalance($res){
        Gateway::$registerAddress = '127.0.0.1:1238';
        $data=array(
            'roomid'=>$res['roomid'],
            'm'=>'balance',
            'data'=>$res,
        );
        $data=json_encode($data);
        Gateway::sendToAll($data);
    }
    //结算之后 入账通知
    private function notify($uid,$res){
        Gateway::$registerAddress = '127.0.0.1:1238';
        $data=array(
            'roomid'=>$res['roomid'],
            'm'=>'notify',
            'data'=>$res,
        );
        $data=json_encode($data);
        Gateway::sendToUid($uid,$data);
    }
    //通知庄家是否继续上庄
    private function notifyisgoon($res,$uid){
        Gateway::$registerAddress = '127.0.0.1:1238';
        $data=array(
            'roomid'=>$res['roomid'],
            'm'=>'isgoon',
        );
        $data=json_encode($data);
        Gateway::sendToUid($uid,$data);
    }
    //游戏结束 玩家开始抢庄
    private function notifystartrob($roomid){
        Gateway::$registerAddress = '127.0.0.1:1238';
        $data=array(
            'roomid'=>$roomid,
            'm'=>'startrob',
            'data'=>array('timer'=>$time=60-time()+strtotime(date('H:i',time())))
        );
        $data=json_encode($data);
        Gateway::sendToAll($data);
    }
    private function notifybet(){

    }
    public function hislist(){
        $roomid=$_POST['roomid'];
        $list=D('Game')->historylist($roomid);
        if(!empty($list)){
            foreach ($list as &$v){
                $v['out_number'] = explode(' ',$v['out_number']);
                $num=0;
                foreach ($v['out_number'] as $s){
                    $num+=$s;
                }
                if($num>9){
                    $v['dx']="大";
                }else{
                    $v['dx']="小";
                }
                if($num%2==1){
                    $v['ds']="单";
                }else{
                    $v['ds']="双";
                }
                $v['he']=$num;
             }
        }
        $this->ajaxReturn($list,'','1');
    }
}