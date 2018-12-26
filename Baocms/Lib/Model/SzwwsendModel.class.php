<?php

require_once LIB_PATH.'/GatewayClient/Gateway.php';

use GatewayClient\Gateway;

class SzwwsendModel extends CommonModel{

    /**大红包已被领取多少个小红包，共多少个红包
     * @param $hongbao_id 大红包id
     * @return $getlist 数组 小红包所有已领取数据
     */
    public function szwwgetlistnum($hongbao_id){
        $getlistnum =  Cac()->lLen('szwwback_user_'.$hongbao_id);
        return $getlistnum;
    }

    /**获取庄家小红包id
     * @param $hongbao_id 大红包id
     * @return $szwwbigbaoid
     */
    public function szwwgetzjid($hongbao_id){
        $szwwbigbaoid = Cac()->lIndex('szwwget_queue_back_'.$hongbao_id,0);

        return $szwwbigbaoid;

    }

    /**用户与商家计算赔付
     * @param $hongbao_id 大红包id
     * @param $kickback_id 领取的小红包id
     * @param $uid 领取红包的用户id
     */
    public function szwwcompensation($hongbao_id,$kickback_id,$uid){
        $users =   D('Users');
        $szwwbigbaoid = $this->szwwgetzjid($hongbao_id);
        //庄家的小红包信息
        $szwwzjsmallbao = unserialize(Cac()->get('szwwget_id_'.$szwwbigbaoid));
        //平民的小红包信息
        $szwwsmallbao = unserialize(Cac()->get('szwwget_id_'.$kickback_id));
        //获取大红包信息
        $szwwbigbao = unserialize(Cac()->get('szww_send_'.$hongbao_id)) ;

        $money =$szwwbigbao['money'];
        $type = '8';
        $remark='金额赔付（胜者）';
        $fymoney = $money*0.05;
        if($szwwzjsmallbao['money']>= $szwwsmallbao['money']){
            //闲赔记录表
            //$users->reducemoney($uid,$fymoney,$type,$is_afect=1,$remark,$order_id=0);
            //闲赔记录小红包szwwget表格
            $this->paymoney($szwwsmallbao,$kickback_id,-$money);

            Cac()->set('szww_paymoney_'.$hongbao_id.$uid,-$money);

        }else{

            //闲赢记录表
            //$users->addmoney($uid,$money,$type,$is_afect=1,$remark,$order_id=0);
            //闲赢记录小红包szwwget表格
            $this->paymoney($szwwsmallbao,$kickback_id,$money);
            //存入缓存
            Cac()->set('szww_paymoney_'.$hongbao_id.$uid,$money);
            //扣除闲家赢得钱
            //$users->reducemoney($uid,$fymoney,81,$is_afect=1,'盈利扣除（胜者）',$order_id=0);
            //闲家的返佣
            //D('Szwwfy')->fanyong($uid,$fymoney,'szww');
        }

    }
    /**闲赔与赢记录到小红包szwwget表格
     * @param $hongbao_id 大红包id
     * @param $kickback_id 领取的小红包id
     * @param $money 赔付金额
     */
    private function paymoney($szwwsmallbao,$kickback_id,$money){
        $szwwget = D('szwwget');
        $szwwget->where(array('id'=>$kickback_id))->save(array('paymoney'=>$money));
        $szwwsmallbao['paymoney']=$money;

        Cac()->set('szwwget_id_'.$kickback_id,serialize($szwwsmallbao));

    }
    /**庄家解冻金额
     * @param $hbinfo 大红包信息

     * @param $money 解冻金额
     */
    public function  zjpaymoney($hbinfo){

        $szwwget = D("Szwwget");
        $hb_id = $hbinfo['id'];
        $getmoneytotal = $szwwget->where("hb_id = $hb_id and user_id > 0")->sum('money');
        $money[1] =  88*$hbinfo['num'] - $getmoneytotal;
        $money[2] = $hbinfo['money']*($hbinfo['num'] - 1);
        return $money;
    }

    //发包队列锁
    public function qsendbaoLock($uid,$str){
        Cac()->rPush('szwwsendbaoLock'.$uid,$str);
        $value=Cac()->lGet('szwwsendbaoLock'.$uid,0);
        if($value==$str){
            return true;
        }else{
            return false;
        }
    }
    //发包队列开锁
    public function opensendbaoLock($uid){
        Cac()->delete('szwwsendbaoLock'.$uid);
    }

    /**获取小红包的队列
     * @param $hongbao_id 大红包id
     * @param $uid 用户id
     */
    public function getkickListInfo($hongbao_id,$uid=0){
        $list=array();
        $nums=0;
        $money=0;
        $numsArr=Cac()->lRange('szwwget_queue_back_'.$hongbao_id,0,-1);
        foreach ($numsArr as $v){
            $tempArr=array();
            $tempArr=$this->getkickInfo($v);
            if($tempArr['user_id']>0 && $tempArr['is_receive']==1){
                $nums++;
                if($uid>0&&$tempArr['user_id']==$uid){
                    $money=$tempArr['money'];
                }
                $list[]=$tempArr;
            }
        }
        $res['num']=$nums;
        $res['money']=$money;
        $res['list']=$list;
        return $res;
    }

    /**发包调用
     * @param $money 红包金额
     * @param $uid 用户id
     * @param $roomid 房间号
     * @param $num 红包数量
     */
    public function createhongbao($money,$hongbaomoney,$num,$roomid,$uid){
        $token=md5('szww_'.genRandomString(6).time().$uid);
        $data=array();
        $data['token']=$token;
        $data['money']=$money;
        $data['num']=$num;
        $data['roomid']=$roomid;
        $data['user_id']=$uid;
        $data['is_over']=0;
        $data['overtime']=0;
        $data['creatime']=time();
        $this->add($data);//大红包添加完毕

        //取出红包加入缓存
        $hongbao_info=$this->where(array('token'=>$token))->find();

        if(empty($hongbao_info)){
            return false;
        }
        //将大红包存入redis
        Cac()->set('szww_send_'.$hongbao_info['id'],serialize($hongbao_info));
        //根据金额
        $kickarr=$this->getkicklist($hongbaomoney,$num);

        //小红包入库
        foreach($kickarr as $k=>$value){
            if($k==0){
                $data['user_id']=0;
                $data["hb_id"]=$hongbao_info['id'];
                $data["is_robot"]=1;//是否是机器人？
                $data["is_receive"]=1;//是否已经领取
                $data["money"]=$value;
                $data['recivetime']=time();
                $data["creatime"]=time();
                D('szwwget')->add($data);


            }else{
                $data['user_id']=0;
                $data["hb_id"]=$hongbao_info['id'];
                $data["is_robot"]=0;
                $data["is_receive"]=0;
                $data["money"]=$value;
                $data['recivetime']=0;
                $data["creatime"]=time();
                D('szwwget')->add($data);
            }
        }
        //获取小红包
        $new_kicklist=D('szwwget')->where(array('hb_id'=>$hongbao_info['id']))->select();

        foreach ($new_kicklist as $k=>$v){
            if($v['is_receive']==0){
                Cac()->rPush('szwwget_queue_'.$hongbao_info['id'],$v['id']);
                Cac()->rPush('szwwget_queue_back_'.$hongbao_info['id'],$v['id']);//复制一条队列  用于遍历数据
                Cac()->set('szwwget_id_'.$v['id'],serialize($v));
            }else{
                Cac()->lPush('szwwget_queue_back_'.$hongbao_info['id'],$v['id']);//复制一条队列  用于遍历数据
                Cac()->set('szwwget_id_'.$v['id'],serialize($v));
            }

        }

        $len=Cac()->lLen('szwwget_queue_'.$hongbao_info['id']);
        if($len==$num-1){
            return $hongbao_info;
        }else{
            return false;
        }

    }
    /**获取红包信息
     * @param $id 大红包id
     * @return bool|mixed 失败 则信息不存在  否则返回红包信息
     */
    public function getInfoById($id){
        $datainfo=unserialize(Cac()->get('szww_send_'.$id));
        if(empty($datainfo)){
            $datainfo=$this->where(array('id'=>$id))->find();
            if(!empty($datainfo)){
                Cac()->set('szww_send_'.$id,serialize($datainfo));
                return $datainfo;
            }else{
                return false;
            }
        }else{
            return $datainfo;
        }
    }
    /**红包是否领取完毕
     * @param $id 红包id
     * @return bool 领取完毕 true 有待领取 false
     */
    public function isfinish($id){
        $value=Cac()->lGet('szwwget_queue_back_'.$id,0);
        if($value>0){
            return false;
        }else{
            return true;
        }
    }
    /**是否已经领取过该红包
     * @param $id 红包id
     * @return bool 领取完毕 true 有待领取 false
     */

    public function is_recived($hongbao_id,$uid){
        $list=Cac()->lRange('szwwback_user_'.$hongbao_id, 0, -1);
        if(!empty($list)){
            foreach ($list as $v){
                if($v==$uid){
                    return true;
                }
            }
            return false;
        }else{
            return false;
        }
    }

    /**是否领取过此红包  此过程为原子执行
     * @param $hongbao_id 红包id
     * @param $uid        用户id
     * @return bool       领取过 true  未领取 false
     */
    public function is_recivedQ($hongbao_id,$uid){
        $rands=genRandomString(6);
        Cac()->rPush('szwwrecive_queue_'.$hongbao_id.'_'.$uid,$rands);
        if(Cac()->lget('szwwrecive_queue_'.$hongbao_id.'_'.$uid,0)==$rands){
            $list=Cac()->lRange('szwwback_user_'.$hongbao_id, 0, -1);
            if(!empty($list)){
                foreach ($list as $v){
                    if($v==$uid){
                        return true;
                    }
                }
                return false;
            }else{
                return false;
            }
        }else{
            return true;
        }
    }


    /**胜者为王红包结算的结果发送给所有人
     * @param $hb 大红包数据

     */

    public function hbgetlistnotify($hb){

        Gateway::$registerAddress = '127.0.0.1:1238';
        $szwwget = D('Szwwget');
        $users =   D('Users');
        $hb_id = $hb['id'];
        $zjmoney = $szwwget->where(array('hb_id'=>$hb_id,'is_robot'=>1))->getField('money');
        $getmoneytotal = $szwwget->where("hb_id = $hb_id and user_id > 0")->sum('paymoney');
        $data = $this->timergetkicklist($hb_id,$hb['user_id']);
        $userInfo=$users->getUserByUid($hb['user_id']);
        $data['username']=$userInfo['nickname'];
        $data['roomid'] = $hb['roomid'];
        $data['user_id'] = $hb['user_id'];
        $data['money'] = $hb['money'];
        $data['zjmoney'] = $zjmoney ;
        $data['paymoneytotal'] = -$getmoneytotal;
        $data['m']=7;
        $data = array_reverse($data);
        //print_r($data);
        $data=json_encode($data);
        Gateway::sendToAll($data);
    }

    /**定时器获取已领取的小红包的队列
     * @param $hongbao_id 大红包id
     * @param $uid 用户id
     */
    public function timergetkicklist($hongbao_id,$zjid){
        $users =   D('Users');
        $list=array();
        $nums = 0;
        $numsArr=Cac()->lRange('szwwback_user_'.$hongbao_id,0,-1);
        foreach ($numsArr as $v){
            $tempArr=array();

            if($v != $zjid){

                $tempArr=$this->uidgetkicklist($hongbao_id,$v);
                $userInfo=$users->getUserByUid($tempArr['user_id']);
                $tempArr['username']=$userInfo['nickname'];

                $list[]=$tempArr;
            }

        }
        $res['list']=$list;
        return $res;
    }
    /**定时器 如果庄家没有领取红包自动领包并更改已领取红包缓存
     * @param $hongbao_id 大红包信息
     * @param $uid 庄家id
     * @return
     *
     */
    public function savezjkickstatus($hongbao_id,$uid){
        $szwwget = D("Szwwget");
        $status = $this->judgezjkickstatus($hongbao_id,$uid);
        if($status == 0){
            $kickid = $szwwget->where(array('hb_id'=>$hongbao_id,'is_robot'=>1))->getField('id');
            $szwwget->where(array('id'=>$kickid))->field('user_id')->save(array('user_id'=>$uid));
            $zjkicklist = $szwwget->where(array('id'=>$kickid))->find();
            Cac()->set('szwwget_id_'.$kickid,serialize($zjkicklist));
        }
        return true;
    }
    /**定时器查询庄家是否已入领取队列未领取存入
     * @param $hongbao_id 大红包id
     * @param $uid 庄家id
     */
    private function judgezjkickstatus($hongbao_id,$uid){

        $useidArr=Cac()->lRange('szwwback_user_'.$hongbao_id,0,-1);
        if(in_array($uid, $useidArr)){
            $status = 1;
        } else {
            Cac()->rPush('szwwback_user_'.$hongbao_id,$uid);
            $status = 0;
        }
        return $status;
    }


    private function uidgetkicklist($hongbao_id,$uid){
        $szwwget = D('Szwwget');
        $kickid = $szwwget->where(array('hb_id'=>$hongbao_id,'user_id'=>$uid))->getField('id');
        $tempArr=unserialize(Cac()->get('szwwget_id_'.$kickid));
        return $tempArr;
    }

    /**从队列中取出一个红包id   出队
     *
     * @param $hongbao_id
     *
     * @return $kickbackid 0 或  大于0
     *
     * 缓存队列键 kickback_queue_187
     */
    public function getOnekickid($hongbao_id){
        $kickbackid=Cac()->lPop('szwwget_queue_'.$hongbao_id);
        return $kickbackid;
    }

    /**领取完毕后 入队已经领取
     * @param $hongbao_id
     * @param $uid
     *
     * 缓存键 kickback_userin_198   198用户id
     */
    public function UserQueue($hongbao_id,$uid){
        Cac()->rPush('szwwback_user_'.$hongbao_id,$uid);
    }

    /**设置红包状态为领取完毕
     * @param $hongbao_id
     */

    public function sethongbaoOver($hongbao_id){
        $data=array('is_over'=>1,'overtime'=>time());
        $savestatus = $this->where(array('id'=>$hongbao_id))->save($data);
        $hongbao_info=$this->getInfoById($hongbao_id);
        $hongbao_info['is_over']=1;
        $hongbao_info['overtime']=time();
        Cac()->set('szww_send_'.$hongbao_info['id'],serialize($hongbao_info));
        return $savestatus;
    }

    /**设置小红包为已经领取
     * @param $kickbackid
     * @param $uid  领取人id
     * @param $status 1 平民 2 庄家
     * 先改数据库 再更新缓存
     */
    public function setkickbackOver($kickbackid,$uid){
        if(D('Szwwget')->where(array('id'=>$kickbackid))->save(array('user_id'=>$uid,'is_receive'=>1,'recivetime'=>time()))){

            if($this->setkickbackCacheOver($kickbackid,$uid)){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    /**设置小红包的缓存为已经领取
     * @param $kickback_id
     * @param $uid
     * @return bool
     */
    public function setkickbackCacheOver($kickback_id,$uid){
        $ts=unserialize(Cac()->get('szwwget_id_'.$kickback_id));
        if(!empty($ts)){
            $ts =  D('Szwwget')->where(array('id'=>$kickback_id))->find();

            Cac()->set('szwwget_id_'.$kickback_id,serialize($ts));
            return true;
        }else{
            return false;
        }
    }
    /**获取小红包的信息
     * @param $kickback_id
     * @return mixed
     */
    public function getkickInfo($kickback_id){
        $kickInfo=unserialize(Cac()->get('szwwget_id_'.$kickback_id));

        if(empty($kickInfo)){
            $kickInfo=D('Szwwget')->where(array('id'=>$kickback_id))->find();
            if(!empty($kickInfo)){
                Cac()->set('szwwget_id_'.$kickback_id,serialize($kickInfo));
            }else{
                return false;
            }
        }

        return $kickInfo;

    }
    /**从已经领取队列中 判断自己是否是最后一位
     *
     * @param $hongbao_id
     *
     * @param $uid
     *
     * @return bool
     */
    public function is_self_last($hongbao_id,$uid){
         //获取大红包信息
        $szwwbigbao =unserialize(Cac()->get('szww_send_'.$hongbao_id)) ;
        $value=Cac()->lIndex('szwwback_user_'.$hongbao_id,$szwwbigbao['num']-1);
        if($value==$uid){
            return true;
        }else{
            return false;
        }
    }
    /**红包分几个小包
     * @param $money 总钱数 单位：分
     * @param $num 小包数量
     * @return $money_arr 数组
     */
    private function getkicklist($money,$num){
        $totle=$money;
        if($num>1){
            $nums_arr=array();

            while (count($nums_arr)<$num-1){
                $point=rand(1,$totle-1);
                while(in_array($point,$nums_arr)){
                    $point=rand(1,$totle-1);
                }
                $nums_arr[]=$point;
            }
            arsort($nums_arr);
        }else{
            $nums_arr[]=0;
        }
        $maxkey=$totle;
        $money_arr=array();
        foreach($nums_arr as $k=>$value){
            $money_arr[]=$maxkey-$value;
            $maxkey=$value;
        }
        if($num>1){
            $money_arr[]=$maxkey;
        }
        return $money_arr;
    }


}







?>