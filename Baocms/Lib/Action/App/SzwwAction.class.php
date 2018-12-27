<?php


require_once LIB_PATH.'/GatewayClient/Gateway.php';

use GatewayClient\Gateway;

class SzwwAction extends CommonAction{
//class SzwwAction extends Action{

    /**胜者为王庄家开始发包
     * @param $money 发的钱
     * @param $num 红包数量
     * @param $user_id 用户id
     * @param $creatime 创建时间
     * @param $roomid 房间号
     */
    public function szwwsend(){
        //money 以分为单位 所以获取到要*100
        //$money ='1000';$num ='5';$this->uid = "2";$this->member = "";$roomid = '3';
        $_a= $_POST;
        $money = (int)$_a['money']*100;
        $num = (int)$_a['num'];
        $roomid = (int)$_a['roomid'];
        $hongbaomoney = 88*$num;
        //含冻结金额，不翻倍
        $freezemoney = $money*($num-1);
        $totalmoney = $hongbaomoney +$freezemoney;
        $users =   D('Users');
        $szwwsend = D("Szwwsend");

        //加锁
        $nostr=time().rand_string(6,1);
        if(!$szwwsend->qsendbaoLock($this->uid,$nostr)){
            $this->ajaxReturn('','频繁操作',0);
        }

        $roomData=D('Room')->getRoomData($roomid);
        if(empty($roomData)){
            $szwwsend->opensendbaoLock($this->uid);
            $this->ajaxReturn('','房间不存在!',0);
        }
        //金额判断
//        if($money>$roomData['conf_max']||$money<$roomData['conf_min']){
//            $szwwsend->opensendbaoLock($this->uid);
//            $this->ajaxReturn('','请选择正确的金额 '.$roomData['conf_min'].'-'.$roomData['conf_max'],0);
//        }
        //红包个数判断
//        if($num>100||$num<4){
//            $szwwsend->opensendbaoLock($this->uid);
//            $this->ajaxReturn('','请选择正确的金额 4-100',0);
//        }

        //余额判断判断
        $userMoney=$users->getUserMoney($this->uid);

        if($userMoney<$totalmoney){
            $szwwsend->opensendbaoLock($this->uid);
            $this->ajaxReturn('','余额不足，请充值!',0);
        }
        //生成红包
        $hongbao_info=$szwwsend->createhongbao($money,$hongbaomoney,$num,$roomid,$this->uid);
        if($hongbao_info){
            //解锁
            $szwwsend->opensendbaoLock($this->uid);
            //将发红包的冻结金额存表
            D('Users')->reducemoney($this->uid,$hongbaomoney,70,1,'发送红包（胜者）');
            D('Users')->reducemoney($this->uid,$freezemoney,71,1,'发包冻结（胜者）');
            //通知
            $this->sendnotify($hongbao_info,$this->member);
            $this->ajaxReturn('','发送完毕!',1);
        }else{
            $szwwsend->opensendbaoLock($this->uid);

            $this->ajaxReturn('','红包发送失败！',0);
        }


    }
    /**胜者为王点击检查红包情况
     * @param $hongbao_id 红包id
     */

    public function clickszwwback(){
        //$hongbao_id = '1';
        $hongbao_id=(int)$_POST['hongbao_id'];
        $users =   D('Users');
        $szwwsend = D("Szwwsend");
        //检测红包是否存在
        $hongbao_info=$szwwsend->getInfoById($hongbao_id);
        //获取发包人的信息
        $userInfo=$users->getUserByUid($hongbao_info['user_id']);
        $hongbao_info['username']=$userInfo['nickname'];
        //Cac()->set('alluserin',count(Gateway::getAllClientInfo()));
        if($hongbao_info['creatime']<time()-60){
            $hongbao_info['type']=2;
            $hongbao_info['remark']='红包过期';
            $this->ajaxReturn($hongbao_info,'红包过期!',1);

        }
        if($szwwsend->isfinish($hongbao_id)){
            $hongbao_info['type']=3;
            $hongbao_info['remark']='红包已经领取完毕!';
            $this->ajaxReturn($hongbao_info,'红包已经领取完毕!',1);
        }
        //此处加强判断 已经领取  不允许重复领取
        if($szwwsend->is_recived($hongbao_id,$this->uid)){
            $hongbao_info['type']=4;
            $hongbao_info['remark']='已经领取过该红包!';
            $this->ajaxReturn($hongbao_info,'已经领取过该红包!',1);
        }

        $hongbao_info['type']=1;
        $hongbao_info['remark']='可以领取';
        $this->ajaxReturn($hongbao_info,'可以领取',1);

    }
    /**胜者为王用户抢包
     * @param $hongbao_id 红包id
     */
    public function openszwwback(){

        //$hongbao_id='';
        //$this->uid = '3';
        $hongbao_id=(int)$_POST['hongbao_id'];//红包id
        $users =   D('Users');
        $szwwsend = D("Szwwsend");

        //获取大红包数据
        $hongbao_info=$szwwsend->getInfoById($hongbao_id);
        if(empty($hongbao_info)){
            $this->ajaxReturn('','红包不存在！',0);
        }

        $userMoney=$users->getUserMoney($this->uid);
        if($userMoney<$hongbao_info['money']){
            if($hongbao_info['user_id']!=$this->uid){
                $info['type']=5;
                $info['remark']='余额不足!';
                $this->ajaxReturn('','余额不足!',0);
            }
        }
        //此处加强判断 已经领取  不允许重复领取
        if($szwwsend->is_recivedQ($hongbao_id,$this->uid)){
            $this->ajaxReturn('','已经领取过该红包!',0);
        }
        if($hongbao_info['user_id']==$this->uid) {
            //是庄家判断
            //获取庄家小红包id
            $kickback_id = $szwwsend->szwwgetzjid($hongbao_id);
            //庄家领包处理
            $this->zjpmreceive($hongbao_id,$kickback_id,$this->uid,$hongbao_info,2);
        }else{
            //将列表第一个小红包移除队列并返回小红包id
            $kickback_id=$szwwsend->getOnekickid($hongbao_id);
            if($kickback_id>0){
                //闲家抢包冻结
                D('Users')->reducemoney($this->uid,$hongbao_info['money'],71,1,'抢包冻结（胜者）');
                //闲家领包处理
                $this->zjpmreceive($hongbao_id,$kickback_id,$this->uid,$hongbao_info,1);
            }else{
                $this->ajaxReturn('','手慢了，领取完了!',0);
            }
        }
    }
    /**胜者为王庄家和平民调用的公共方法
     * @param $hongbao_id 大红包id
     * @param $kickback_id 庄家小红包id
     * @param $uid 用户id
     * @param $hongbao_info 大红包数据
     * @param $status 状态 1 平民进来 2 庄家进来
     */
    private function zjpmreceive($hongbao_id,$kickback_id,$uid,$hongbao_info,$status){
        $users =   D('Users');
        $szwwsend = D("Szwwsend");
        //先把自己入队到已经领取
        $szwwsend->UserQueue($hongbao_id, $uid);

        //设置szwwget为已经领取
        $szwwsend->setkickbackOver($kickback_id, $uid);
        //获取小红包的信息，红包已被领取，缓存重新更改
        $kickback_info = $szwwsend->getkickInfo($kickback_id);

        $money = $kickback_info['money'];
        //抢的红包入paid表
        $users->addmoney($uid, $money, 5, 1, "领红包（胜者）");
        if($status==1){
            //1 平民进来 2庄家进来
            //与庄家赔付
            $szwwsend->szwwcompensation($hongbao_id, $kickback_id, $uid);

        }

        $userinfo = $users->getUserByUid($uid);

        //领取通知庄家
        $this->benotify($hongbao_info, $userinfo);
        //判断是否是最后一个 是的话开始同步数据库信息
        if($szwwsend->is_self_last($hongbao_id,$this->uid)){

            //设置mysql红包为领取状态为完毕
            $szwwsend->sethongbaoOver($hongbao_id);
            $this->ajaxReturn('','领取成功!',1);
        }else{
            $this->ajaxReturn('','领取成功!',1);
        }
    }

    /**红包详情
     * @param $hongbao_id 大红包id
     *
     *
     */
    public function getrecivelist(){
        //判断自己是否在
        $hongbao_id=(int)$_POST['hongbao_id'];
        //$this->uid = 3 ;
        $szwwsend = D("Szwwsend");
        $hongbao_info=$szwwsend->getInfoById($hongbao_id);

        if(empty($hongbao_info)){
            $this->ajaxReturn('','红包不存在！',0);
        }
        $hbUser=D('Users')->getUserByUid($hongbao_info['user_id']);
        //判断超时
        if($hongbao_info['creatime']<time()-60){
            $timeout=1;
        }else{
            $timeout=0;
        }

        if($szwwsend->isfinish()){
            $finish=1;
        }else{
            $finish=0;
        }
        $kickList=$szwwsend->getkickListInfo($hongbao_id,$this->uid);
        $kickList['list']=array_reverse($kickList['list']);


        if($szwwsend->is_recived($hongbao_id,$this->uid)){
            $recived=1;
            $res['is_selfin']=1;
            $res['selfmoney']=$kickList['money'];
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
        $res['money']=$hongbao_info['num']*88;//发包总金额
        $res['recive_num']=$kickList['num'];
        $res['check']=$kickList['check'];
        $res['nums']=$hongbao_info['num'];
        $res['list']=$kickList['list'];
        foreach ($res['list'] as &$v){
            $v['recivetime']=date('H:i:s',$v['recivetime']);

            $userTemp=D('Users')->getUserByUid($v['user_id']);
            $v['username']=$userTemp['nickname'];
            $v['avatar']=$userTemp['face'];
            if($v['avatar']==""){
                $v['avatar']="img/avatar.png";
            }

        }

        if($timeout==0&&$finish==0&&$recived==0&&$hongbao_info['user_id']!=$this->uid){
            $this->ajaxReturn('','红包未领取！',0);
        }
        $this->ajaxReturn($res,'请求成功！',1);

    }

    public function sendlist(){
        $_GET['p']=(int)$_POST['p'];

        $Hb = D('Szwwsend');
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
            $sql="SELECT SUM(money) as totle FROM bao_szwwsend WHERE user_id=".$this->uid;
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
        $Hb = D('Szwwget');
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
            $sql="SELECT SUM(money) as totle FROM bao_szwwget WHERE user_id=".$this->uid;
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

    /**胜者为王用户领包全局通知
     * @param $hb 大红包信息
     * @param $userinfo 用户信息

     */
    private function sendnotify($hb,$userinfo)
    {
        Gateway::$registerAddress = '127.0.0.1:1238';
        if($userinfo['face']==''){
            $userinfo['face']="img/avatar.png";
        }
        $data=array(
            'roomid'=>$hb['roomid'],
            'm'=>5,
            'data'=>array(
                'username'=>$userinfo['nickname'],
                'user_id'=>$userinfo['user_id'],
                'avatar'=>$userinfo['face'],
                'hongbao_id'=>$hb['id'],
                'money'=>$hb['money'],
                'overtime'=>null,
                'creatime'=>$hb['creatime'],
                'num'=>$hb['num'],
                'token'=>$hb['token'],
                'is_over'=>0,
                'is_freeze'=>0,
            )
        );
        $data=json_encode($data);
        Gateway::sendToAll($data);
    }
    /**红包被领取通知庄家
     * @param $userinfo 用户的数据信息
     * @param $hb  大红包信息
     */

    private function benotify($hb,$userinfo){
        Gateway::$registerAddress = '127.0.0.1:1238';
        if($userinfo['face']==''){
            $userinfo['face']="img/avatar.png";
        }
        $data=array(
            'roomid'=>$hb['roomid'],
            'm'=>6,
            'data'=>array(
                'username'=>$userinfo['nickname'],
                'user_id'=>$userinfo['user_id'],
                'avatar'=>$userinfo['face'],
                'hongbao_id'=>$hb['id'],
                'money'=>$hb['money']
            )
        );
        $data=json_encode($data);
        Gateway::sendToUid($hb['user_id'],$data);
    }
    /**红包被领取通知领取用户
     * @param $getlist  已领取的所有小红包数据
     * @param $userinfo 用户的数据信息
     * @param $hb  大红包信息
     */
    private function szwwgetlistsend($hb,$getlist,$userinfo,$getlistnum){
        Gateway::$registerAddress = '127.0.0.1:1238';
        if($userinfo['face']==''){
            $userinfo['face']="img/avatar.png";
        }
        $data=array(
            'roomid'=>$hb['roomid'],
            'm'=>5,
            'data'=>array(
                'username'=>$userinfo['nickname'],
                'user_id'=>$userinfo['user_id'],
                'avatar'=>$userinfo['face'],
                'hongbao_id'=>$hb['id'],
                'num'=>$hb['num'],
                'getnum'=>$getlistnum,
                'hongbaolist'=>$getlist

            )
        );
        $data=json_encode($data);
        Gateway::sendToUid($userinfo['user_id'],$data);
    }


}

?>