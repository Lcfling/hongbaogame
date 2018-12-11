<?php
require_once LIB_PATH.'/GatewayClient/Gateway.php';

use GatewayClient\Gateway;

class HongbaoAction extends CommonAction
{
    public function index(){
        $gametype=$_POST['gametype'];
        $roomlist=D('Room')->getroomlist($gametype);
        if(!empty($roomlist)){
            $this->ajaxReturn($roomlist);
        }else{
            $this->ajaxReturn('','未知错误！',0);
        }
    }

    //点击检测
    public function clickkickback(){
        $hongbao_id=(int)$_POST['hongbao_id'];
        $hongbaoModel=D('Hongbao');
        $hongbao_info=$hongbaoModel->getInfoById($hongbao_id);
        $userInfo=D('Users')->getUserByUid($hongbao_info['user_id']);
        $hongbao_info['username']=$userInfo['nickname'];

        Cac()->set('alluserin',count(Gateway::getAllClientInfo()));
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
        //此处加强判断 已经领取  不允许重复领取
        if($hongbaoModel->is_recived($hongbao_id,$this->uid)){
            $hongbao_info['type']=4;
            $hongbao_info['remark']='已经领取过该红包!';
            $this->ajaxReturn($hongbao_info,'已经领取过该红包!',1);
        }
        //余额判断   余额小于红包总金额的1.6倍 不允许强

        $hongbao_info['type']=1;
        $hongbao_info['remark']='可以领取';

        $this->ajaxReturn($hongbao_info,'可以领取',1);

    }
    //开包
    public function openkickback(){
        $hongbao_id=(int)$_POST['hongbao_id'];//红包id
        $hongbaoModel=D('Hongbao');
        $hongbao_info=$hongbaoModel->getInfoById($hongbao_id);
        if(empty($hongbao_info)){
            $this->ajaxReturn('','红包不存在！',0);
        }
        $bom_num=$hongbao_info['bom_num'];
        $userMoney=D('Users')->getUserMoney($this->uid);
        if($userMoney<$hongbao_info['money']*1.6){
            if($hongbao_info['user_id']!=$this->uid){
                $info['type']=5;
                $info['remark']='余额不足!';
                $this->ajaxReturn('','余额不足!',0);
            }
        }
        //此处加强判断 已经领取  不允许重复领取
        if($hongbaoModel->is_recivedQ($hongbao_id,$this->uid)){
            $this->ajaxReturn('','已经领取过该红包!',0);
        }
        $kickback_id=$hongbaoModel->getOnekickid($hongbao_id);
        if($kickback_id>0){
            //先把自己入队到已经领取
            $hongbaoModel->UserQueue($hongbao_id,$this->uid);
            //设置kickback为已经领取
            $hongbaoModel->setkickbackOver($kickback_id,$this->uid);
            $kickback_info=$hongbaoModel->getkickInfo($kickback_id);
            $money=$kickback_info['money'];
            D('Users')->addmoney($this->uid,$money,2,1,"领红包");


            //抢包返佣逻辑
            $miansi=D('Kickback')->where("hb_id=".$hongbao_id." and is_robot=1")->find();

            //$this->writeLog(var_export($miansi,true));
            if($miansi['money']>=100){
                D('Grabfanyong')->fanyong($this->uid,$miansi["money"],"saolei");
            }



            //领取通知
            $userinfo=D('Users')->getUserByUid($this->uid);
            $this->benotify($hongbao_info,$userinfo);

            $user_bom=substr((int)$money,-1);
            //如果相等 中磊
            if($user_bom==$bom_num){
                D('Users')->reducemoney($this->uid,(int)($hongbao_info['money']*1.6),5,1,"中雷");//中雷
                D('Users')->addmoney($hongbao_info['user_id'],(int)($hongbao_info['money']*1.6),3,1,"中雷");//收雷
            }


            //领取中奖
            $selfaword=number_type($money);
            if($hongbao_info['money']<3000&&$selfaword>0){
                $selfaword=111;
            }
            if($selfaword>0){
                $type='['.($money/100).']';
                $this->awordnotify($hongbao_info,$userinfo,$selfaword,$type);
                D('Users')->addmoney($this->uid,$selfaword,7,1,"中奖");//收雷
            }
            //判断是否是最后一个 是的话开始同步数据库信息
            if($hongbaoModel->is_self_last($hongbao_id,$this->uid)){
                //判断红包的雷数
                $bom_nums=$hongbaoModel->getBomNums($hongbao_id);
                $awordmoney=0;
                switch ($bom_nums){
                    case 3:
                        $awordmoney=1888;
                        break;
                    case 4:
                        $awordmoney=4888;
                        break;
                    case 5:
                        $awordmoney=18800;
                        break;
                    case 6:
                        $awordmoney=38800;
                        break;
                    case 7:
                        $awordmoney=58800;
                        break;
                    default:
                        $awordmoney=0;
                        break;
                }
                if($bom_nums > 2){
                    $type='['.$bom_nums.' 雷]';
                    $hbUser=D('Users')->getUserByUid($hongbao_info['user_id']);
                    D('Users')->addmoney($hongbao_info['user_id'],$awordmoney,7,1,"中奖");//奖励
                    $this->awordnotify($hongbao_info,$hbUser,$awordmoney,$type);
                }

                //设置mysql红包为领取状态为完毕
                $hongbaoModel->sethongbaoOver($hongbao_id);
                $this->ajaxReturn('','领取成功!',1);
            }else{
                $this->ajaxReturn('','领取成功!',1);
            }
        }else{
            $this->ajaxReturn('','手慢了，领取完了!',0);
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
        $HbModel=D('Hongbao');
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

        foreach ($res['list'] as $k=>$v){
            $volume[$k]  = $v['recivetime'];
        }
        array_multisort($volume, SORT_DESC, $res['list']);

        foreach ($res['list'] as &$v){
            $v['recivetime']=date('H:i:s',$v['recivetime']);
            if($v['user_id']>0){
                $userTemp=D('Users')->getUserByUid($v['user_id']);
                $v['username']=$userTemp['nickname'];
                $v['avatar']=$userTemp['face'];
                if($v['avatar']==""){
                    $v['avatar']="img/avatar.png";
                }
            }else{
                $v['username']='免死';
                $v['avatar']='img/miansi.jpg';
            }

        }


        if($timeout==0&&$finish==0&&$recived==0&&$hongbao_info['user_id']!=$this->uid){
            $this->ajaxReturn('','红包未领取！',0);
        }
        $this->ajaxReturn($res,'请求成功！',1);

    }

    /**发红包
     *
     */
    public function send(){
        $roomid=(int)$_POST['roomid'];
        $roomData=D('Room')->getRoomData($roomid);
        if(empty($roomData)){
            $this->ajaxReturn('','房间不存在!',0);
        }
        $this->ajaxReturn($roomData,'请求成功',1);
    }
    //执行发红包
    public function dosend(){
        $money=(int)($_POST['money']*100);
        $bom_num=(int)$_POST['bom_num'];
        $roomid=(int)$_POST['roomid'];


        //todo 在这里验证支付密码

        //加锁
        $nostr=time().rand_string(6,1);
        if(!D('Hongbao')->qsendbaoLock($this->uid,$nostr)){
            $this->ajaxReturn('','频繁操作',0);
        }


        $roomData=D('Room')->getRoomData($roomid);
        if(empty($roomData)){
            D('Hongbao')->opensendbaoLock($this->uid);
            $this->ajaxReturn('','房间不存在!',0);
        }
        //金额判断
        if($money>$roomData['conf_max']||$money<$roomData['conf_min']){
            D('Hongbao')->opensendbaoLock($this->uid);
            $this->ajaxReturn('','请选择正确的金额 '.$roomData['conf_min'].'-'.$roomData['conf_max'],0);
        }
        //雷点判断
        if(!($bom_num<10&&$bom_num>=0)){
            D('Hongbao')->opensendbaoLock($this->uid);
            $this->ajaxReturn('','请选择正确的雷数字 0-9',0);
        }
        //余额判断判断
        $userMoney=D('Users')->getUserMoney($this->uid);
        if($userMoney<$money){
            D('Hongbao')->opensendbaoLock($this->uid);
            $this->ajaxReturn('','余额不足，请充值!',0);
        }
        //生成红包
        $hongbao_info=D('Hongbao')->createhongbao($money,$bom_num,7,$roomid,$this->uid);
        if($hongbao_info){
            D('Hongbao')->opensendbaoLock($this->uid);
            D('Users')->reducemoney($this->uid,$money,4,1,'发送红包');
            //通知
            $this->sendnotify($hongbao_info,$this->member,$hongbao_info['roomid']);
            $this->ajaxReturn('','发送完毕!',1);
        }else{
            D('Hongbao')->opensendbaoLock($this->uid);
            $this->ajaxReturn('','红包发送失败！',0);
        }
    }


    public function getlist(){

        $roomid=(int)$_POST['roomid'];
        $list=D('Hongbao')->where('roomid='.$roomid." and money < 50001")->order('id DESC')->limit(10)->select();
        $list=array_reverse($list);
        foreach ($list as &$value){
            $user=D('Users')->getUserByUid($value['user_id']);
            $value['username']=$user['nickname'];
            $value['hongbao_id']=$value['id'];
            if($user['face']==""){
                $value['avatar']="img/avatar.png";
            }else{
                $value['avatar']=$user['face'];
            }
        }
        $this->ajaxReturn($list,'请求成功roomid='.$roomid);
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
        if($userinfo['face']==''){
            $userinfo['face']="img/avatar.png";
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
                'bom_num'=>$hb['bom_num'],
                'id'=>$hb['id'],
                'overtime'=>null,
                'creatime'=>null,
                'roomid '=>$hb['roomid'],
                'num'=>null,
                'token'=>null,
                'is_over'=>0,
                'is_best'=>0,
            )
        );
        $data=json_encode($data);
        Gateway::sendToAll($data);
    }
    //红包倍领取通知

    private function benotify($hb,$userinfo){
        Gateway::$registerAddress = '127.0.0.1:1238';
        if($userinfo['face']==''){
            $userinfo['face']="img/avatar.png";
        }
        $data=array(
            'roomid'=>$hb['roomid'],
            'm'=>3,
            'data'=>array(
                'username'=>$userinfo['nickname'],
                'user_id'=>$userinfo['user_id'],
                'avatar'=>$userinfo['face'],
                'hongbao_id'=>$hb['id'],
                'money'=>$hb['money'],
                'bom_num'=>$hb['bom_num'],

            )
        );
        $data=json_encode($data);
        Gateway::sendToUid($hb['user_id'],$data);
    }

    public function aotudosend(){
        //获取随机用户
        $k=$_GET['key'];
        if($k!='dbak3s7fhash34fah39t'){
            die('大哥 别乱搞！');
        }
        //获取房间列表
        $roomlist=D('Room')->getroomlist('saolei');
        foreach ($roomlist as $roomdata){
            //查看一分钟内是否有当前房间内发送的红包
            $roomid=$roomdata['room_id'];
            //获取随机用户
            $user=D('Users')->getrandUser();
            $roominfo=D('Room')->getroom($roomid);
            $money=$roominfo['conf_min'];
            $ranstatus=rand_string(6,1);
            $ransfabao=rand_string(6,1);
            if($ranstatus>600000){
                $money=$money+1000;
            }elseif($ranstatus<350000){
                $money=$money+2000;
            }else{

                $randm2=rand_string(1,1);
                $randm2=$randm2*100;
                $randm3=rand_string(1,1);
                $randm3=$randm3*100;
                $money=$money+$randm2+$randm3;
            }
            $rands2=rand_string(2,1);
            if($rands2>65&&$money<5000){
                $randm=rand_string(3,1);
                $money=(int)$money+(int)$randm;
            }
            /*if($money>3000){
                if($ransfabao>200000){
                    continue;
                }
            }*/
            $bom_num=rand_string(1,1);
            $hongbao_info=D('Hongbao')->createhongbao($money,$bom_num,7,$roomid,$user['user_id']);
            if($hongbao_info){
                D('Users')->reducemoney($user['user_id'],$money,4,0,'发送红包');
                //通知
                $this->sendnotify($hongbao_info,$user,$hongbao_info['roomid']);
                continue;
            }
        }

    }

    //刷包  不发通知
    public function maotudosend(){
        //获取随机用户
        $k=$_GET['key'];
        if($k!='dbak3s7fhash34fah39t'){
            die('大哥 别乱搞！');
        }
        //获取房间列表
        $roomlist=D('Room')->getroomlist('saolei');
        foreach ($roomlist as $roomdata){
            //查看一分钟内是否有当前房间内发送的红包
            $roomid=$roomdata['room_id'];
            //获取随机用户
            $user=D('Users')->getrandUser();
            $roominfo=D('Room')->getroom($roomid);
            $money=$roominfo['conf_min'];
            $ranstatus=rand_string(6,1);
            $ransfabao=rand_string(6,1);
            if($ranstatus>700000){
                $money=$money+1000;
            }elseif($ranstatus<300000){
                $money=$money+4000;
            }
            $money=160000;
            /*if($money>3000){
                if($ransfabao>200000){
                    continue;
                }
            }*/
            $bom_num=rand_string(1,1);
            $hongbao_info=D('Hongbao')->createhongbao($money,$bom_num,7,$roomid,$user['user_id']);
            if($hongbao_info){
                D('Users')->reducemoney($user['user_id'],$money,4,0,'发送红包');
                //通知
                //$this->sendnotify($hongbao_info,$user,$hongbao_info['roomid']);
                continue;
            }
        }
    }

    //机器人自动清包
    public function aotuopenkick(){
        $k=$_GET['key'];
        if($k!='dbak3s7fhash34fah39t'){
            die('大哥 别乱搞！');
        }

        $config=unserialize(Cac()->get('Setting_robot'));
        $roomlist=D('Room')->getroomlist('saolei');
        foreach ($roomlist as $roomdata){
            $roomid=$roomdata['room_id'];
            //拿出来发包时间在某一个时间段的信息
            $time=$config['time'];
            //最多抢包次数
            $qnums=$config['packNum'];
            $baoList=D('Hongbao')->getInfoByTime($roomid);
            //print_r($baoList);
            foreach ($baoList as $hongbao_info){
                if($hongbao_info['creatime']+$time<time()&&$hongbao_info['is_over']!=1){
                    //获取一个机器人用户

                    //echo
                    if($hongbao_info['money']<=$config['packMoney']){
                        $hongbao_id=$hongbao_info['id'];//红包id
                        Cac()->rPush("robot_rob_h".$hongbao_id,1);
                        if(Cac()->lLen("robot_rob_h".$hongbao_id)>$qnums){
                            continue;
                        }

                        $user=D('Users')->getrandUser();


                        $hongbaoModel=D('Hongbao');
                        if(empty($hongbao_info)){
                            continue;
                        }
                        $bom_num=$hongbao_info['bom_num'];
                        //$userMoney=D('Users')->getUserMoney($this->uid);

                        //此处加强判断 已经领取  不允许重复领取
                        if($hongbaoModel->is_recivedQ($hongbao_id,$user['user_id'])){
                            continue;
                        }
                        $kickback_id=$hongbaoModel->getOnekickid($hongbao_id);
                        $kickback_info=$hongbaoModel->getkickInfo($kickback_id);

                        if($config['lei']>0){
                            if(substr((int)$kickback_info['money'],-1)==$bom_num){
                                Cac()->rPush('kickback_queue_'.$hongbao_info['id'],$kickback_id);
                                continue;
                            }
                        }

                        if($kickback_id>0){
                            //先把自己入队到已经领取
                            $hongbaoModel->UserQueue($hongbao_id,$user['user_id']);
                            //设置kickback为已经领取
                            $hongbaoModel->setkickbackOver($kickback_id,$user['user_id']);

                            $money=$kickback_info['money'];
                            D('Users')->addmoney($user['user_id'],$money,2,0,"机器领包");


                            //领取通知
                            //$userinfo=D('Users')->getUserByUid($this->uid);
                            $this->benotify($hongbao_info,$user);

                            $user_bom=substr((int)$money,-1);
                            //如果相等 中磊
                            if($user_bom==$bom_num){
                                D('Users')->reducemoney($user['user_id'],(int)($hongbao_info['money']*1.6),5,0,'机器中雷');//中雷
                                D('Users')->addmoney($hongbao_info['user_id'],(int)($hongbao_info['money']*1.6),3,1,'中雷');//收雷
                            }


                            //领取中奖
                            $selfaword=number_type($money);
                            if($hongbao_info['money']<3000&&$selfaword>0){
                                $selfaword=111;
                            }
                            if($selfaword>0){
                                $type='['.($money/100).']';
                                $this->awordnotify($hongbao_info,$user,$selfaword,$type);
                                D('Users')->addmoney($user['user_id'],$selfaword,7,0,'机器中奖');//收雷
                            }
                            //判断是否是最后一个 是的话开始同步数据库信息
                            if($hongbaoModel->is_self_last($hongbao_id,$user['user_id'])){
                                //判断红包的雷数
                                $bom_nums=$hongbaoModel->getBomNums($hongbao_id);
                                $awordmoney=0;
                                switch ($bom_nums){
                                    case 3:
                                        $awordmoney=1888;
                                        break;
                                    case 4:
                                        $awordmoney=4888;
                                        break;
                                    case 5:
                                        $awordmoney=18800;
                                        break;
                                    case 6:
                                        $awordmoney=38800;
                                        break;
                                    case 7:
                                        $awordmoney=58800;
                                        break;
                                    default:
                                        $awordmoney=0;
                                        break;
                                }
                                if($bom_nums > 2){
                                    $type='['.$bom_nums.' 雷]';
                                    $hbUser=D('Users')->getUserByUid($hongbao_info['user_id']);
                                    D('Users')->addmoney($hongbao_info['user_id'],$awordmoney,7,1,"中奖");//奖励
                                    $this->awordnotify($hongbao_info,$hbUser,$awordmoney,$type);
                                }

                                //设置mysql红包为领取状态为完毕
                                $hongbaoModel->sethongbaoOver($hongbao_id);
                            }
                            continue;
                        }else{
                            continue;
                        }
                    }
                }
                //if($hongbao_info['creatime']<time()-$time&&$hongbao_info['is_over']!=1){
            }
            $baoList=array();
            $time=200;
            $baoList=D('Hongbao')->getinfo($roomid);
            foreach ($baoList as $hongbao_info){
                if($hongbao_info['creatime']+180<time() && $hongbao_info['is_over']!=1){
                    $hongbaoModel=D('Hongbao');
                    $hongbaoModel->sethongbaoOver($hongbao_info['id']);
                    do{
                        $kickback_id=$hongbaoModel->getOnekickid($hongbao_info['id']);
                        if($kickback_id>0){
                            $kickback_info=$hongbaoModel->getkickInfo($kickback_id);
                            D("Users")->addmoney($hongbao_info['user_id'],$kickback_info['money'],2,1,'红包退回');
                        }
                    }while($kickback_id>0);
                }
            }
        }
    }

    public function sendlist(){
        $_GET['p']=(int)$_POST['p'];

        $Hb = D('Hongbao');
        import('ORG.Util.Page'); // 导入分页类
        $map=array();
        $map['user_id']=$this->uid;
        $count = $Hb->where($map)->count(); // 查询满足要求的总记录数
        $data['totle']=$count;
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        //$pager = $Page->show(); // 分页显示输出
        $list = $Hb->where($map)->order(array('creatime'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as &$v){
            $v['creatime']=date('Y-m-d H:i:s',$v['creatime']);
        }
        $data['current']=$Page->currentPage();
        if($data['current']==1){
            $sql="SELECT SUM(money) as totle FROM bao_hongbao WHERE user_id=".$this->uid;
            $sum=$Hb->query($sql);
            $data['sum']=$sum[0]['totle'];
        }else{
            $data['sum']='';
        }
        //die('sss'.$count);
        $data['list']=$list;
        $data['count']=$count;
        $data['user']=D('Users')->getUserByUid($this->uid);
        $this->ajaxReturn($data,'',1);

    }
    public function recivelist(){
        $_GET['p']=(int)$_POST['p'];
        $Hb = D('Kickback');
        import('ORG.Util.Page'); // 导入分页类
        $map=array();
        $map['user_id']=$this->uid;
        $count = $Hb->where($map)->count(); // 查询满足要求的总记录数
        $map['is_best']=1;
        $best = $Hb->where($map)->count();
        unset($map['is_best']);
        $data['totle']=$count;
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        //$pager = $Page->show(); // 分页显示输出
        $list = $Hb->where($map)->order(array('creatime'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as &$v){
            $v['creatime']=date('Y-m-d H:i:s',$v['creatime']);
        }
        $data['current']=$Page->currentPage();
        if($data['current']==1){
            $sql="SELECT SUM(money) as totle FROM bao_kickback WHERE user_id=".$this->uid;
            $sum=$Hb->query($sql);
            $data['sum']=$sum[0]['totle'];
        }else{
            $data['sum']='';
        }
        $data['best']=$best;
        $data['list']=$list;
        $data['count']=$count;
        $data['user']=D('Users')->getUserByUid($this->uid);

        $this->ajaxReturn($data,'',1);
    }

    public function getpeople(){
        $count=D('Users')->where('1')->count();
        $count=(string)((int)($count)-10000);
        $this->ajaxReturn($count,'房间人数',1);
    }
}