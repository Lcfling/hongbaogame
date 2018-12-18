<?php
/**
 *
 */

require_once LIB_PATH.'/GatewayClient/Gateway.php';

use GatewayClient\Gateway;
class ErbaModel extends CommonModel
{
    protected $pk   = 'dayu_id';
    protected $tableName =  'dayu_tag';



   // todo 抢庄金额冻结
    public function qz_dongjie($uid,$money,$remark='28抢庄冻结'){
        $info['order_id']=0;
        $info['money']=-$money*100;
        $info['user_id']=$uid;
        $info['creatime']=time();
        $info['type']=280;
        $info['remark']=$remark;
        $info['is_afect']=1;

        $m=D('Paid');
        if($m->add($info)){
            return true;
        }else{
            return false;
        }
    }
    // todo 抢庄金额冻结
    public function lz_dongjie($uid,$money,$remark='28连庄冻结'){
        $info['order_id']=0;
        $info['money']=-$money;
        $info['user_id']=$uid;
        $info['creatime']=time();
        $info['type']=280;
        $info['remark']=$remark;
        $info['is_afect']=1;

        $m=D('Paid');
        if($m->add($info)){
            return true;
        }else{
            return false;
        }
    }



    //todo 抢庄金额解冻
    public function qz_jiedong($uid,$money,$remark='28抢庄解冻'){
        $info['order_id']=0;
        $info['money']=$money;
        $info['user_id']=$uid;
        $info['creatime']=time();
        $info['type']=281;
        $info['remark']=$remark;
        $info['is_afect']=1;

        $m=D('Paid');
        if($m->add($info)){
            return true;
        }else{
            return false;
        }
    }
    //todo 存入抢庄人信息
    public function set_qz($room_id,$user_id,$data){
        Cac()->lPush("qz_user_".$room_id,$user_id);
        Cac()->set("qz_user_info_".$user_id,serialize($data));
    }
    //todo 取出抢庄人信息
    public function get_qz($room_id){
        $data=Cac()->LRANGE("qz_user_".$room_id,0,-1);

        return $data;
    }

    // todo 获取抢庄金钱最大
    public function max_money($data){


        foreach ($data as $k=>$v){
            $list[]= unserialize(Cac()->get("qz_user_info_".$data[$k]));
            Cac()->del("qz_user_info_".$v['user_id']);
        }


        $max=$list[0];
        foreach ($list as $k=>$v){
            if ($max['money']<$list[$k]['money']){
                $max = $list[$k];
            }
        }

        foreach ($list as $k=>$v){
            if ($list[$k]['user_id'] !=$max['user_id']){
                $this->qz_jiedong($list[$k]['user_id'],$list[$k]['money']);
            }
        }
        return$max;
    }

    //todo  庄家信息入库
    public function add_zhuang($list,$room_id){
       $Erba= M('Erba_zhuang');
       $data['user_id']=$list['user_id'];
       $data['money']=$list['money'];
       $data['time']=time();
       $data['room_id']=$room_id;
        $info_id=$Erba->add($data);

        if ( $info_id){
            //设置庄金额入缓冲
            Cac()->set('qz_money_'.$room_id,$list['money']);
            $where['room_id']=$room_id;
            $where['chang_id']=$info_id;
            $user_info=$Erba->where($where)->find();

            return $user_info;
        }else{
            return false;
        }
    }
    //todo 获取庄家信息
    public function zhuang_info($room_id,$chang_id){

        $Erba= M('Erba_zhuang');
        $where['room_id']=$room_id;
        $where['chang_id']=$chang_id;
        $data=$Erba->where($where)->find();
        return $data;
    }

    // todo 判断下注时间
    public function if_time($room_id,$chang_id){

        $data=$this->zhuang_info($room_id,$chang_id);
        if (time()>$data['time']+60){
            return false;
        }else{
            return true;
        }
    }
    //todo 判断下注金额
    public function if_money($room_id,$money){

        //  获取庄家剩余金钱
        $qz_money=Cac()->get('qz_money_'.$room_id);
        if ($money*100*5 >$qz_money){
            return false;
        }else{
            return true;
        }
    }
    //todo 判断是否重复下注
    public function xiazhus($user_id,$chang_id,$room_id,$money){
        $Erba_xz= M('Erba_xiazhu');
        $where['user_id']=$user_id;
        $where['chang_id']=$chang_id;
        $where['room_id']=$room_id;
        $xiazhu=$Erba_xz->where($where)->find();
        if ($xiazhu){
           return true;
        }else{
            return false;
        }
    }

    public function zhuijia_money($user_id,$chang_id,$room_id,$money){
        //查询已下注记录
        $Erba_xz= M('Erba_xiazhu');
        $where['user_id']=$user_id;
        $where['chang_id']=$chang_id;
        $where['room_id']=$room_id;
        $xiazhu=$Erba_xz->where($where)->find();

        //追加下注
        $where1['user_id']=$user_id;
        $where1['chang_id']=$chang_id;
        $where1['room_id']=$room_id;
        $save['money']=$xiazhu['money']+$money*100;
        $Erba_xz->where($where1)->save($save);

        //冻结下注金额
        $pai=M('Paid');
        $info['order_id']=0;
        $info['money']=-$money*100*5;
        $info['user_id']=$user_id;
        $info['creatime']=time();
        $info['type']=282;
        $info['remark']="28下注冻结";
        $info['is_afect']=1;
        $pai->add($info);

        //  减少庄家剩余金钱
        Cac()->decrBy('qz_money_'.$room_id,$money*100*5);

        $where2['user_id']=$user_id;
        $where2['chang_id']=$chang_id;
        $where2['room_id']=$room_id;
        $xiazhus=$Erba_xz->where($where2)->find();

        return $xiazhus;
    }


    //todo 用户下注
    public function xiazhu($user_id,$money,$chang_id,$room_id){

        $Erba_xz= M('Erba_xiazhu');
        $data['user_id']=$user_id;
        $data['money']=$money*100;
        $data['chang_id']=$chang_id;
        $data['room_id']=$room_id;
        $data['time']=time();
        $xiazhu_id=$Erba_xz->add($data);

        $pai=M('Paid');
        $info['order_id']=0;
        $info['money']=-$money*100*5;
        $info['user_id']=$user_id;
        $info['creatime']=time();
        $info['type']=282;
        $info['remark']="28下注冻结";
        $info['is_afect']=1;

        if ( $pai->add($info)){
            //  减少庄家剩余金钱
         Cac()->decrBy('qz_money_'.$room_id,$money*100*5);
         $where['user_id']=$user_id;
         $where['id']=$xiazhu_id;
         $xiazhu_info=$Erba_xz->where($where)->find();
         return $xiazhu_info;
        }else{
            return false;
        }
    }

    // todo 获取下注人信息
    public function get_xiazhu($chang_id){
        $kicklist=D('Erba_xiazhu')->where(array('chang_id'=>$chang_id))->select();
        return $kicklist;

    }

    //todo 生成红包
    public function createhongbao($money,$num,$roomid,$chang_id,$uid){
        $hongbao=M('hongbao_erba');
        $token=md5(genRandomString(6).time().$uid);
        $data=array();
        $data['token']=$token;
        $data['roomid']=$roomid;
        $data['user_id']=$uid;
        $data['money']=$money;
        $data['num']=$num;
        $data['is_over']=0;
        $data['overtime']=0;
        $data['creatime']=time();
        $data['chang_id']=$chang_id;
        $hongbao->add($data);//大红包添加完毕
        //取出红包加入缓存
        $hongbao_info=$hongbao->where(array('token'=>$token))->find();
        if(empty($hongbao_info)){
            return false;
        }
        Cac()->set('eb_hongbao_info_'.$hongbao_info['id'],serialize($hongbao_info));
        //根据金额 生成7个红包
        $kickarr=$this->getkicklist($money,$num);

        //小红包入库
        foreach($kickarr as $k=>$value){

                $data['user_id']=0;
                $data["hb_id"]=$hongbao_info['id'];
                $data["is_robot"]=0;
                $data["is_receive"]=0;
                $data["money"]=$value;
                $data['recivetime']=0;
                $data["creatime"]=time();
                D('kickback_erba')->add($data);

        }
        //获取小红包
        $new_kicklist=D('kickback_erba')->where(array('hb_id'=>$hongbao_info['id']))->select();


        //
        $maxArr=array();
        $maxN=0;
        foreach ($new_kicklist as $k=>$v){
            if($v['is_receive']==0){
                Cac()->rPush('eb_kickback_queue_'.$hongbao_info['id'],$v['id']);
                Cac()->rPush('eb_kickback_queue_back_'.$hongbao_info['id'],$v['id']);//复制一条队列  用于遍历数据
                Cac()->set('eb_kickback_id_'.$v['id'],serialize($v));
            }else{
                Cac()->lPush('eb_kickback_queue_back_'.$hongbao_info['id'],$v['id']);//复制一条队列  用于遍历数据
                Cac()->set('eb_kickback_id_'.$v['id'],serialize($v));
            }
            //拿出最大的队列
            if($v['money']>$maxN){
                $maxN=$v['money'];
                $maxArr=$v;
            }
        }
        $saveData['is_best']=1;
        D('kickback_erba')->where('id='.$maxArr['id'])->save($saveData);
        Cac()->lLen('eb_kickback_queue_'.$hongbao_info['id']);
        return $hongbao_info;

    }

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

    /**todo 获取红包信息
     * @param $id
     * @return bool|mixed 失败 则信息不存在  否则返回红包信息
     */
    public function getInfoById($id){
        $hongbao=M('hongbao_erba');
        $datainfo=unserialize(Cac()->get('eb_hongbao_info_'.$id));

        if(empty($datainfo)){
            $datainfo=$hongbao->where(array('id'=>$id))->find();
            if(!empty($datainfo)){
                Cac()->set('eb_hongbao_info_'.$id,serialize($datainfo));
                return $datainfo;
            }else{
                return false;
            }
        }else{
            return $datainfo;
        }
    }
    /**todo 红包是否领取完毕
     *
     * @return bool 领取完毕 true 有待领取 false
     */
    public function isfinish($id){
        $value=Cac()->lGet('eb_kickback_queue_'.$id,0);
        if($value>0){
            return false;
        }else{
            return true;
        }
    }
    /**todo 是否领取过此红包  此过程为原子执行
     *

     */
    public function is_recivedQ($hongbao_id,$uid){
        $rands=genRandomString(6);
        Cac()->rPush('eb_recive_queue_'.$hongbao_id.'_'.$uid,$rands);
        if(Cac()->lget('eb_recive_queue_'.$hongbao_id.'_'.$uid,0)==$rands){
            $list=Cac()->lRange('eb_kickback_user_'.$hongbao_id, 0, -1);
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
    public function is_recived($hongbao_id,$uid){
        $list=Cac()->lRange('eb_kickback_user_'.$hongbao_id, 0, -1);
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
    //todo 判断是否下注的人点包
    public function xiazhu_info($chang_id,$user_id){
        $xiazhu=M('Erba_xiazhu');
        $where['chang_id']=$chang_id;
        $where['user_id']=$user_id;
        $data=$xiazhu->where($where)->find();
        if ($data){
            return true;
        }else{
            $zhuang=M('erba_zhuang');
            $where['chang_id']=$chang_id;
            $where['user_id']=$user_id;
            $zhuanginfo=$zhuang->where($where)->find();
            if ($zhuanginfo){
                return true;
            }else{
                return false;
            }

        }
    }

    /**todo 从队列中取出一个红包id   出队
     *
     * @param $hongbao_id
     *
     * @return $kickbackid 0 或  大于0
     *
     * 缓存队列键 kickback_queue_187
     */
    public function getOnekickid($hongbao_id){

        $kickbackid=Cac()->lPop('eb_kickback_queue_'.$hongbao_id);

        return $kickbackid;
    }

    /** todo 领取完毕后 入队已经领取
     * @param $hongbao_id
     * @param $uid
     *
     * 缓存键 kickback_userin_198   198用户id
     */
    public function UserQueue($hongbao_id,$uid){
        Cac()->rPush('eb_kickback_user_'.$hongbao_id,$uid);
    }

    /**todo 设置小红包为已经领取
     * @param $kickbackid
     *
     *
     * 先改数据库 再更新缓存
     */
    public function setkickbackOver($kickbackid,$uid,$room_id,$chang_id){

            //判断是否庄家领取红包
            $data=$this->zhuang_info($room_id,$chang_id);
            if ($uid == $data['user_id']){
                $where['id']=$kickbackid;
                $save['user_id']=$uid;
                $save['is_zhuang']=1;
                $save['is_rective']=1;
                $save['recivetime']=time();
                $status= D('kickback_erba')->where($where)->save($save);

             //  $status= D('kickback_Erba')->where(array('id'=>$kickbackid))->save(array('user_id'=>$uid, 'is_zhuang'=>1, 'is_receive'=>1,'recivetime'=>time()));

                if($status){

                    $info=D('kickback_erba')->where(array('id'=>$kickbackid))->find();


                    // 更新庄家点数
                    $num=$this->str($info['money']);

                    //更新庄家领取金钱
                    $Erba= M('Erba_zhuang');
                    $where['room_id']=$room_id;
                    $where['chang_id']=$chang_id;
                    $save['hb_money']=$info['money'];
                    $save['num']=$num;
                    $save['is_get']=1;
                    $Erba->where($where)->save($save);

                  //  D('kickback_erba')->where(array('id'=>$kickbackid))->save(array('num'=>$num));

                    if($this->setkickbackCacheOver($kickbackid,$uid)){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }else{

                if(D('kickback_erba')->where(array('id'=>$kickbackid))->save(array('user_id'=>$uid,'is_receive'=>1,'recivetime'=>time()))){
                    $info=D('kickback_erba')->where(array('id'=>$kickbackid))->find();
                    //更新玩家点数
                    $num= $this->str($info['money']);
                    D('kickback_erba')->where(array('id'=>$kickbackid))->save(array('num'=>$num));
                    D('erba_xiazhu')->where(array('room_id'=>$room_id,'chang_id'=>$chang_id,'user_id'=>$uid))->save(array('hb_money'=>$info['money'],'num'=>$num,'is_get'=>1));

                    if($this->setkickbackCacheOver($kickbackid,$uid)){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }

    }
    /**todo 设置小红包的缓存为已经领取
     * @param $kickback_id
     * @param $uid
     * @return bool
     */
    public function setkickbackCacheOver($kickback_id,$uid){
        $ts=unserialize(Cac()->get('eb_kickback_id_'.$kickback_id));
        if(!empty($ts)){
            $ts['user_id']=$uid;
            $ts['recivetime']=time();
            Cac()->set('eb_kickback_id_'.$kickback_id,serialize($ts));
            return true;
        }else{
            return false;
        }
    }
    /**todo 获取小红包的信息
     * @param $kickback_id
     *
     */
    public function getkickInfo($kickback_id){
        $kickInfo=unserialize(Cac()->get('eb_kickback_id_'.$kickback_id));
        if(empty($kickInfo)){
            $kickInfo=D('kickback_erba')->where(array('id'=>$kickback_id))->find();
            if(!empty($kickInfo)){
                Cac()->set('eb_kickback_id_'.$kickback_id,serialize($kickInfo));
            }else{
                return false;
            }
        }
        return $kickInfo;
    }
    /** todo 从已经领取队列中 判断自己是否是最后一位
     *
     * @param $hongbao_id
     *
     * @param $uid
     *
     * @return bool
     */

    public function is_self_last($hongbao_id,$uid,$room_id){
       // $count=Cac()->lLen('eb_kickback_user_'.$hongbao_id);
        $count=Cac()->get("qz_count_".$room_id);
        $value=Cac()->lGet('eb_kickback_user_'.$hongbao_id,$count);

        if($value==$uid){
            return true;
        }else{
            return false;
        }
    }

    //todo 用户给庄家进行对比 赔付
    public function  peifu($xiazhu_info,$zhuang_info){

        foreach ($xiazhu_info as $k=>$v){
            if ($zhuang_info['num'] > $xiazhu_info[$k]['num']){
                // 庄赢   判断倍数
                   $bei=$this->bei($zhuang_info['num']);
                   //用户下注金额解冻
                  D('Users')->addmoney($xiazhu_info[$k]['user_id'],$xiazhu_info[$k]['money']*5,283,1,'28下注解冻');
                  //用户赔付
                  D('Users')->addmoney($xiazhu_info[$k]['user_id'],-($xiazhu_info[$k]['money']*$bei),283,1,'28下注赔付');
                  //赔付给庄家金钱
                  D('Users')->addmoney($zhuang_info['user_id'],$xiazhu_info[$k]['money']*$bei,283,1,'28赔付');



                  // 更新下注人信息
                $where1['user_id']=$xiazhu_info[$k]['user_id'];
                $where1['chang_id']=$xiazhu_info[$k]['chang_id'];
                $sava1['peifu']=-$xiazhu_info[$k]['money']*$bei;
                $sava1['remark']='庄赢';
                D('Erba_xiazhu')->where($where1)->save($sava1);


            }else if ($zhuang_info['num'] < $xiazhu_info[$k]['num']){
                //  闲赢 判断倍数
                $bei=$this->bei($xiazhu_info[$k]['num']);
                //用户下注解冻
                D('Users')->addmoney($xiazhu_info[$k]['user_id'],$xiazhu_info[$k]['money']*5,283,1,'28下注解冻');
                //用户下注赔付
                D('Users')->addmoney($xiazhu_info[$k]['user_id'],$xiazhu_info[$k]['money']*$bei,283,1,'28赔付');
                //庄家扣除赔付金钱
                D('Users')->addmoney($zhuang_info['user_id'],-$xiazhu_info[$k]['money']*$bei,283,1,'28庄家赔付');

                     // 闲家赢钱 扣除5%返佣金额
                D('Users')->addmoney($xiazhu_info[$k]['user_id'],-$xiazhu_info[$k]['money']*$bei*0.05,284,1,'28返佣');
                    //进行返佣
                D('Fanyong')->fanyong($xiazhu_info[$k]['user_id'],$xiazhu_info[$k]['money']*$bei*0.05*0.6,'erba');


                // 更新下注人信息
                $where2['user_id']=$xiazhu_info[$k]['user_id'];
                $where2['chang_id']=$xiazhu_info[$k]['chang_id'];
                $sava2['peifu']=$xiazhu_info[$k]['money']*$bei;
                $sava2['remark']='闲赢';
                D('Erba_xiazhu')->where($where2)->save($sava2);

                // 点数相同 判断领取的钱
            }else if ($zhuang_info['num'] == $xiazhu_info[$k]['num']){

                if ($zhuang_info['hb_money'] < $xiazhu_info[$k]['hb_money']){
                    //  闲赢 判断倍数
                    $bei=$this->bei($xiazhu_info[$k]['num']);
                    //用户下注解冻
                    D('Users')->addmoney($xiazhu_info[$k]['user_id'],$xiazhu_info[$k]['money']*5,283,1,'28下注解冻');
                    //用户下注赔付
                    D('Users')->addmoney($xiazhu_info[$k]['user_id'],$xiazhu_info[$k]['money']*$bei,283,1,'28赔付');
                    //庄家扣除赔付金钱
                    D('Users')->addmoney($zhuang_info['user_id'],-$xiazhu_info[$k]['money']*$bei,283,1,'28庄家赔付');

                    // 闲家赢钱 扣除5%返佣金额
                    D('Users')->addmoney($xiazhu_info[$k]['user_id'],-$xiazhu_info[$k]['money']*$bei*0.05,284,1,'28返佣');
                    //进行返佣
                    D('Fanyong')->fanyong($xiazhu_info[$k]['user_id'],$xiazhu_info[$k]['money']*$bei*0.05*0.6,'erba');

                    // 更新下注人信息
                    $where3['user_id']=$xiazhu_info[$k]['user_id'];
                    $where3['chang_id']=$xiazhu_info[$k]['chang_id'];
                    $sava3['peifu']=$xiazhu_info[$k]['money']*$bei;
                    $sava3['remark']='闲赢';
                    D('Erba_xiazhu')->where($where3)->save($sava3);
                }else{

                    // 庄赢   判断倍数
                    $bei=$this->bei($zhuang_info['num']);
                    //用户下注解冻
                    D('Users')->addmoney($xiazhu_info[$k]['user_id'],$xiazhu_info[$k]['money']*5,283,1,'28下注解冻');
                    //用户下注赔付
                    D('Users')->addmoney($xiazhu_info[$k]['user_id'],-($xiazhu_info[$k]['money']*$bei),283,1,'28下注赔付');
                    //庄家得到赔付
                    D('Users')->addmoney($zhuang_info['user_id'],$xiazhu_info[$k]['money']*$bei,283,1,'28赔付');



                    // 更新下注人信息
                    $where4['user_id']=$xiazhu_info[$k]['user_id'];
                    $where4['chang_id']=$xiazhu_info[$k]['chang_id'];
                    $sava4['peifu']=-$xiazhu_info[$k]['money']*$bei;
                    $sava4['remark']='庄赢';
                    D('Erba_xiazhu')->where($where4)->save($sava4);
                }
            }

        }

                    //统计庄家的输赢

                $xiazhu_where['chang_id']=$zhuang_info['chang_id'];
                $money= D('Erba_xiazhu')->where($xiazhu_where)->field('sum(peifu) as money')->select();

                if ($money[0]['money'] <0){
                    $money[0]['money']=abs($money[0]['money']);
                }else{
                    $money[0]['money']=-$money[0]['money'];
                }

                if ($money[0]['money']>0){
                    //庄家赢钱  扣除5%返佣金额
                    D('Users')->addmoney($zhuang_info['user_id'],-$money[0]['money']*0.05,284,1,'28返佣');
                    //进行返佣
                    D('Fanyong')->fanyong($zhuang_info['user_id'],$money[0]['money']*0.05*0.6,'erba');

                }

                $where['user_id']=$zhuang_info['user_id'];
                $where['chang_id']=$zhuang_info['chang_id'];
                $sava['shuying']=$money[0]['money'];
                D('Erba_zhuang')->where($where)->save($sava);

    }

    // 庄家金钱解冻
    public function zhuang_jiedong($zhuang_info){

        $info['order_id']=0;
        $info['money']=$zhuang_info['money'];
        $info['user_id']=$zhuang_info['user_id'];
        $info['creatime']=time();
        $info['type']=281;
        $info['remark']='28庄家解冻';
        $info['is_afect']=1;

        $m=D('Paid');
        $m->add($info);


    }


    /**设置红包状态为领取完毕
     * @param $hongbao_id
     */

    public function sethongbaoOver($hongbao_id){
        $hongbao=M('hongbao_erba');
        $data=array('is_over'=>1,'overtime'=>time());
        $hongbao->where(array('id'=>$hongbao_id))->save($data);
        $hongbao_info=$this->getInfoById($hongbao_id);
        $hongbao_info['is_over']=1;
        $hongbao_info['overtime']=time();
        Cac()->set('eb_hongbao_info_'.$hongbao_info['id'],serialize($hongbao_info));
    }

    //游戏信息
    public function hb_info($room_id,$chang_id){
       $xiazhu= M('erba_xiazhu');
        $where['room_id']=$room_id;
        $where['chang_id']=$chang_id;
        $info=$xiazhu->where($where)->select();
        return $info ;
    }
    //todo 点数
    public function str($str){

        //个位
        $str1=$str%10;
        //十位
        $str2=intval($str/10)%10;
        //对子
        if ($str1 == 0 & $str2 == 0){
            return  10;
        }

        //对子
        if ($str1 == $str2){
           return  $str2.$str1;

        }
        // 二八
        if ( ($str1 == 2 && $str2 ==8) || ($str1 ==8 && $str2 == 2)){
           return 100;
        }
        //点数
        $sum=$str1+$str2;
        return $sum%10;
    }

     //倍数
    public function  bei($num){

        if ($num <=6){
             return 1;
        }
        if ($num ==7){
            return 2;
        }
        if ($num ==8 || $num == 9){
            return 3;
        }
        if ($num ==10){
            return 4;
        }
        if ($num >10 && $num<100){
            return 4;
        }
        if ($num ==100){
            return 5;
        }

    }

    public function getkickListInfo($hongbao_id,$uid=0){
        $list=array();
        $nums=0;
        $money=0;
        $check=1;
        $numsArr=Cac()->lRange('eb_kickback_queue_back_'.$hongbao_id,0,-1);
        foreach ($numsArr as $v){
            $tempArr=array();
            $tempArr=$this->getkickInfo($v);
            //print_r($tempArr);
            if($tempArr['user_id']>0||$tempArr['is_receive']==1){
                //$list[]=$tempArr;

                $nums++;
                if($uid>0&&$tempArr['user_id']==$uid){
                    $money=$tempArr['money'];
                    if($tempArr['is_receive']==0){
                        //print_r($tempArr);
                        $tempArr['is_receive']='1';
                        Cac()->set('eb_kickback_id_'.$tempArr['id'],serialize($tempArr));
                        //print_r(unserialize(Cac()->get('kickback_id_'.$tempArr['id'])));
                        $check=0;
                    }

                }
                $list[]=$tempArr;
            }
        }
        $res['num']=$nums;
        $res['money']=$money;
        $res['check']=$check;
        $res['list']=$list;
        return $res;
    }
}