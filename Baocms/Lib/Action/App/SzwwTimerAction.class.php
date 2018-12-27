<?php


class SzwwTimerAction extends Action{

    /**自动给庄家领取红包 结算发给所有人
     * @param roomid 房间号
     * @param token 安全验证
     * @return
     */
    public function  zjgethongbao(){
       $data =  $_GET;
       if($data['token']=='3acf16259def65456fc2a68ab5e10d96'){
           //echo "进入定时器";
           $szwwsend = D("Szwwsend");
           //获取该房间未结束的红包
            $unfinishedlist =  $szwwsend->where(array("roomid"=>$data['roomid'],'is_freeze'=>0))->select();

            foreach ($unfinishedlist as $k=>$v){
                $timecha=time() - 60;
                if($v['is_over']==1 || $v['creatime']<$timecha){
                    $hbinfo = unserialize(Cac()->get("szww_send_".$v['id']));

                    //设置mysql红包为领取状态为完毕并改庄家解冻状态
                    $hbfinishedstatus =  $this->unfreeze($v['id']);
                    // 庄家发的红包的剩余金额和冻结金额的返还
                    $this->zjmoneyback($hbinfo);

                    //返佣扣除庄家赢得金额，结算金额入库
                    $this->zjfypay($hbinfo);

                    //闲家赔付入库
                    $this->xjfypay($hbinfo);
                    //将领取的结算结果发送给房间所有人
                    $szwwsend->hbgetlistnotify($hbinfo);

                    //如果庄家没有领取红包自动领包并更改已领取红包缓存
                    $szwwsend->savezjkickstatus($hbinfo['id'],$hbinfo['user_id']);

                    //记录日志
                    file_put_contents('./hbstatus.log',"红包结束定时器执行状态（1 成功 0 失败）：大红包id：".$v['id']."状态：".$hbfinishedstatus.PHP_EOL,FILE_APPEND);

                }

            }
       }else{
           echo "未进入定时器";

       }

    }

    /**庄家发的红包的剩余金额和冻结金额的返还
     * @param $hbinfo 大红包信息
     * @return
     *
     */
    private function zjmoneyback($hbinfo){

        $szwwsend = D("Szwwsend");
        $users =   D('Users');
        $money = $szwwsend->zjpaymoney($hbinfo);
        //file_put_contents('./token.txt','money'.$money.PHP_EOL,FILE_APPEND);
        $users->addmoney($hbinfo['user_id'],$money[2],73,$is_afect=1,'发包解冻（胜者）',$order_id=0);
        if($money[1] > 0){
            $users->addmoney($hbinfo['user_id'],$money[1],72,$is_afect=1,'发包返还（胜者）',$order_id=0);
        }

    }
    /**返佣扣除庄家赢得金额
     * @param $hb 红包信息
     */
    private function zjfypay($hb){
        $szwwget = D('Szwwget');
        $szwwfy =   D('Szwwfy');
        $users =   D('Users');
        $hb_id = $hb['id'];
        $uid =$hb['user_id'];
        $getmoneytotal = $szwwget->where("hb_id = $hb_id and user_id > 0")->sum('paymoney');
        if($getmoneytotal!=0){
            //结算庄家金额
            $users->addmoney($hb['user_id'],-$getmoneytotal,74,$is_afect=1,'结算（胜者）',$order_id=0);
        }

        if(-$getmoneytotal>0){
            //玩家盈利抽取5%
            $fymoney =-$getmoneytotal * 0.05;

            //扣除庄家赢得钱
            $users->reducemoney($uid,$fymoney,81,$is_afect=1,'盈利扣除（胜者）',$order_id=0);
            //闲家的返佣
            $szwwfy->fanyong($uid,$fymoney,'szww');

        }
    }
    /**返佣扣除庄家赢得金额
     * @param $hb 红包信息
     */
    private function xjfypay($hb){
        $szwwfy =   D('Szwwfy');
        $users =   D('Users');
        $hb_id = $hb['id'];
        $type = '8';
        $zjuid =$hb['user_id'];
        $remark='金额赔付（胜者）';
        $uids = Cac()->lRange('szwwback_user_'.$hb_id,0,-1);

        foreach ($uids as $v){
           // print_r($v);
            if($v != $zjuid){
                $money= Cac()->get('szww_paymoney_'.$hb_id.$v);

                //闲家抢包解冻
                $users->addmoney($v,$hb['money'],73,$is_afect=1,'抢包解冻（胜者）',$order_id=0);
                //闲家赔付记录表
                $users->addmoney($v,$money,$type,$is_afect=1,$remark,$order_id=0);
                if($money>0){
                    //闲家盈利抽取5%
                    $fymoney = $money*0.05;

                    //扣除闲家赢得钱
                    $users->reducemoney($v,$fymoney,81,$is_afect=1,'盈利扣除（胜者）',$order_id=0);
                    //闲家的返佣
                    $szwwfy->fanyong($v,$fymoney,'szww');
                }
            }


        }


    }

    /**庄家金额1分钟之后进行解冻 红包领取结束
     * @param $hbinfo 大红包信息
     * @return
     *
     */
    public function unfreeze($hongbao_id){
        $szwwsend = D("Szwwsend");

        $data=array('is_over'=>1,'overtime'=>time(),'is_freeze'=>1);
        $savestatus = $szwwsend->where(array('id'=>$hongbao_id))->save($data);
        $hongbao_info=$szwwsend->getInfoById($hongbao_id);
        Cac()->set('szww_send_'.$hongbao_info['id'],serialize($hongbao_info));
        return $savestatus;

    }






}




?>