<?php
class JielongModel extends CommonModel
{
    protected $pk = 'id';
    protected $tableName = 'hongbao';


    /**获取红包信息
     * @param $id
     * @return bool|mixed 失败 则信息不存在  否则返回红包信息
     */
    public function getInfoById($id){
        $hongbao=M('hongbao_jielong');
        $datainfo=unserialize(Cac()->get('jl_hongbao_info_'.$id));

        if(empty($datainfo)){
            $datainfo=$hongbao->where(array('id'=>$id))->find();
            if(!empty($datainfo)){
                Cac()->set('jl_hongbao_info_'.$id,serialize($datainfo));
                return $datainfo;
            }else{
                return false;
            }
        }else{
            return $datainfo;
        }
    }

    //判断队列中是否第一个
    public function one_start($roomid){

        $start=$roomid.time().rand(1000,9999);
        Cac()->rPush('jl_start'.$roomid,$start);
        $one=Cac()->lGet('jl_start'.$roomid,0);
        if ($one == $start){
            return true;
        }else{
            return false;
        }
    }




    /**红包是否领取完毕
     * @param $id 红包id
     * @return bool 领取完毕 true 有待领取 false
     */
    public function isfinish($id){
        $value=Cac()->lGet('jl_kickback_queue_'.$id,0);
        if($value>0){
            return false;
        }else{
            return true;
        }
    }


    /**是否领取过此红包  此过程为原子执行
     *
     * @param $hongbao_id 红包id
     *
     * @param $uid        用户id
     *
     * @return bool       领取过 true  未领取 false
     */
    public function is_recivedQ($hongbao_id,$uid){
        $rands=genRandomString(6);
        Cac()->rPush('jl_recive_queue_'.$hongbao_id.'_'.$uid,$rands);
        if(Cac()->lget('jl_recive_queue_'.$hongbao_id.'_'.$uid,0)==$rands){
            $list=Cac()->lRange('jl_kickback_user_'.$hongbao_id, 0, -1);
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
        $list=Cac()->lRange('jl_kickback_user_'.$hongbao_id, 0, -1);
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

    /**从队列中取出一个红包id   出队
     *
     * @param $hongbao_id
     *
     * @return $kickbackid 0 或  大于0
     *
     * 缓存队列键 kickback_queue_187
     */
    public function getOnekickid($hongbao_id){
        $kickbackid=Cac()->lPop('jl_kickback_queue_'.$hongbao_id);
        return $kickbackid;
    }




    /**领取完毕后 入队已经领取
     * @param $hongbao_id
     * @param $uid
     *
     * 缓存键 kickback_userin_198   198用户id
     */
    public function UserQueue($hongbao_id,$uid){
        Cac()->rPush('jl_kickback_user_'.$hongbao_id,$uid);
    }

    /**设置小红包为已经领取
     * @param $kickbackid
     * @param $uid  领取人id
     *
     * 先改数据库 再更新缓存
     */
    public function setkickbackOver($kickbackid,$uid){
        if(D('Kickback_jielong')->where(array('id'=>$kickbackid))->save(array('user_id'=>$uid,'is_receive'=>1,'recivetime'=>time()))){
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
        $ts=unserialize(Cac()->get('jl_kickback_id_'.$kickback_id));
        if(!empty($ts)){
            $ts['user_id']=$uid;
            $ts['recivetime']=time();
            Cac()->set('jl_kickback_id_'.$kickback_id,serialize($ts));
            return true;
        }else{
            return false;
        }
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
        $value=Cac()->lGet('jl_kickback_user_'.$hongbao_id,3);
        if($value==$uid){
            return true;
        }else{
            return false;
        }
    }


    /**获取中雷红包个数 大于3全部设置为3
     * @param $hongbao_id
     * @return $count 中雷个数
     */
    public function getBomNums($hongbao_id){
        $hongbao_info=$this->getInfoById($hongbao_id);
        $numsArr=Cac()->lRange('jl_kickback_queue_back_'.$hongbao_id,0,-1);
        $count=0;
        foreach ($numsArr as $v){
            $tempArr=array();
            $tempArr=$this->getkickInfo($v);
            if(substr($tempArr['money'],-1)==$hongbao_info['bom_num']){
                $count++;
            }
        }
        return $count;
    }



    /**设置红包状态为领取完毕
     * @param $hongbao_id
     */

    public function sethongbaoOver($hongbao_id){
        $hongbao=M('hongbao_jielong');
        $data=array('is_over'=>1,'overtime'=>time());
        $hongbao->where(array('id'=>$hongbao_id))->save($data);
        $hongbao_info=$this->getInfoById($hongbao_id);
        $hongbao_info['is_over']=1;
        $hongbao_info['overtime']=time();
        Cac()->set('jl_hongbao_info_'.$hongbao_info['id'],serialize($hongbao_info));
    }


    /**获取小红包的信息
     * @param $kickback_id
     * @return mixedisfinish
     */
    public function getkickInfo($kickback_id){
        $kickInfo=unserialize(Cac()->get('jl_kickback_id_'.$kickback_id));
        if(empty($kickInfo)){
            $kickInfo=D('kickback_jielong')->where(array('id'=>$kickback_id))->find();
            if(!empty($kickInfo)){
                Cac()->set('jl_kickback_id_'.$kickback_id,serialize($kickInfo));
            }else{
                return false;
            }
        }
        return $kickInfo;

    }
    public function getkickListInfo($hongbao_id,$uid=0){
        $list=array();
        $nums=0;
        $money=0;
        $check=1;
        $numsArr=Cac()->lRange('jl_kickback_queue_back_'.$hongbao_id,0,-1);
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
                        Cac()->set('jl_kickback_id_'.$tempArr['id'],serialize($tempArr));
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

    //获取最小的红包信息
    public function min_user($hongbao_id){

        $hongbao=M('kickback_jielong');
        $where['hb_id']=$hongbao_id;
        $where['user_id']=array('NEQ','0');
        $minuser= $hongbao->where($where)->select();
        $min=$minuser[0];
        foreach ($minuser as $k=>$v){
            if ($min['money']>$minuser[$k]['money']){
                $min = $minuser[$k];
            }
        }

       $user= D('Users')->getuserbyuid($min['user_id']);
        return $user;
    }
    //清除缓存
    public function delete_start($roomid){
        Cac()->delete('jl_start'.$roomid);
    }


     //生成红包
    public function createhongbao($money,$num,$roomid,$uid){
        $hongbao=M('hongbao_jielong');
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
        $hongbao->add($data);//大红包添加完毕
        //取出红包加入缓存
        $hongbao_info=$hongbao->where(array('token'=>$token))->find();
        if(empty($hongbao_info)){
            return false;
        }
        Cac()->set('jl_hongbao_info_'.$hongbao_info['id'],serialize($hongbao_info));
        //根据金额 生成7个红包
        $kickarr=$this->getkicklist($money,$num);

        //小红包入库
        foreach($kickarr as $k=>$value){
            if($k==0){
                $data['user_id']=0;
                $data["hb_id"]=$hongbao_info['id'];
                $data["is_robot"]=1;
                $data["is_receive"]=1;
                $data["money"]=$value;
                $data['recivetime']=time();
                $data["creatime"]=time();
                D('kickback_jielong')->add($data);
                D('Fanyong')->fanyong($uid,$data["money"],"jielong");
            }else{
                $data['user_id']=0;
                $data["hb_id"]=$hongbao_info['id'];
                $data["is_robot"]=0;
                $data["is_receive"]=0;
                $data["money"]=$value;
                $data['recivetime']=0;
                $data["creatime"]=time();
                D('kickback_jielong')->add($data);
            }
        }
        //获取小红包
        $new_kicklist=D('kickback_jielong')->where(array('hb_id'=>$hongbao_info['id']))->select();
        //
        $maxArr=array();
        $maxN=0;
        foreach ($new_kicklist as $k=>$v){
            if($v['is_receive']==0){
                Cac()->rPush('jl_kickback_queue_'.$hongbao_info['id'],$v['id']);
                Cac()->rPush('jl_kickback_queue_back_'.$hongbao_info['id'],$v['id']);//复制一条队列  用于遍历数据
                Cac()->set('jl_kickback_id_'.$v['id'],serialize($v));
            }else{
                Cac()->lPush('jl_kickback_queue_back_'.$hongbao_info['id'],$v['id']);//复制一条队列  用于遍历数据
                Cac()->set('jl_kickback_id_'.$v['id'],serialize($v));
            }
            //拿出最大的队列
            if($v['money']>$maxN){
                $maxN=$v['money'];
                $maxArr=$v;
            }
        }
        $saveData['is_best']=1;
        D('kickback_jielong')->where('id='.$maxArr['id'])->save($saveData);
        Cac()->lLen('jl_kickback_queue_'.$hongbao_info['id']);
        return $hongbao_info;

    }


    public function createhongbao_xt($money,$num,$roomid,$uid){
        $hongbao=M('hongbao_jielong');
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
        $data['is_start']=1;
        $hongbao->add($data);//大红包添加完毕
        //取出红包加入缓存
        $hongbao_info=$hongbao->where(array('token'=>$token))->find();

        if(empty($hongbao_info)){
            return false;
        }
        Cac()->set('jl_hongbao_info_'.$hongbao_info['id'],serialize($hongbao_info));
        //根据金额 生成红包
        $kickarr=$this->getkicklist($money,$num);


        //小红包入库
        foreach($kickarr as $k=>$value){
            if($k==0){
                $data['user_id']=0;
                $data["hb_id"]=$hongbao_info['id'];
                $data["is_robot"]=1;
                $data["is_receive"]=1;
                $data["money"]=$value;
                $data['recivetime']=time();
                $data["creatime"]=time();
                $data['is_start']=1;
                D('kickback_jielong')->add($data);
            }else{
                $data['user_id']=0;
                $data["hb_id"]=$hongbao_info['id'];
                $data["is_robot"]=0;
                $data["is_receive"]=0;
                $data["money"]=$value;
                $data['recivetime']=0;
                $data["creatime"]=time();
                $data['is_start']=1;
                D('kickback_jielong')->add($data);
            }
        }
        //获取小红包
        $new_kicklist=D('kickback_jielong')->where(array('hb_id'=>$hongbao_info['id']))->select();
        //
        $maxArr=array();
        $maxN=0;
        foreach ($new_kicklist as $k=>$v){
            if($v['is_receive']==0){
                Cac()->rPush('jl_kickback_queue_'.$hongbao_info['id'],$v['id']);
                Cac()->rPush('jl_kickback_queue_back_'.$hongbao_info['id'],$v['id']);//复制一条队列  用于遍历数据
                Cac()->set('jl_kickback_id_'.$v['id'],serialize($v));
            }else{
                Cac()->lPush('jl_kickback_queue_back_'.$hongbao_info['id'],$v['id']);//复制一条队列  用于遍历数据
                Cac()->set('jl_kickback_id_'.$v['id'],serialize($v));
            }
            //拿出最大的队列
            if($v['money']>$maxN){
                $maxN=$v['money'];
                $maxArr=$v;
            }
        }
        $saveData['is_best']=1;
        D('kickback_jielong')->where('id='.$maxArr['id'])->save($saveData);
       Cac()->lLen('jl_kickback_queue_'.$hongbao_info['id']);

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

    //
    public function issendIntime($roomid,$time){
        $hongbao=M('hongbao_jielong');
        $res=$hongbao->where('roomid='.$time.' AND creatime<'.time()-$time)->Filed('id')->select();
        if(empty($res)){
            return false;
        }else{
            return true;
        }
    }

    public function getInfoByTime($roomid,$time,$endtime=180){
        $hongbao=M('hongbao_jielong');
        $t=time()-$time;
        $e=time()-$endtime;
        $res=$hongbao->where('roomid='.$roomid.' AND is_over=0 AND creatime<'.$t.' AND creatime>'.$e)->select();
        return $res;
    }

    //冻结金额
    public function freezeMoney($uid,$money,$remark='接龙资金冻结'){
        $info['order_id']=0;
        $info['money']=-$money;
        $info['user_id']=$uid;
        $info['creatime']=time();
        $info['type']=41;
        $info['remark']=$remark;
        $info['is_afect']=1;

        $m=D('Paid');
        if($m->add($info)){
            return true;
        }else{
            return false;
        }
    }

    //资金解冻
    public function unfrozen($uid,$money,$remark='接龙资金解冻'){
        $info['order_id']=0;
        $info['money']=$money;
        $info['user_id']=$uid;
        $info['creatime']=time();
        $info['type']=42;
        $info['remark']=$remark;
        $info['is_afect']=1;

        $m=D('Paid');
        if($m->add($info)){
            return true;
        }else{
            return false;
        }
    }

    //解冻金额
    public function jiedong($hongbao_id,$money){

        $where['hb_id']=$hongbao_id;
        $where['user_id']=array('NEQ','0');
        $kickInfo=D('Kickback_jielong')->where($where)->select();

        foreach ($kickInfo as $k=>$v){
            $this->unfrozen($kickInfo[$k]['user_id'],$money);
        }
    }

    public function is_start($hongbao_id){

      $start=$this->getInfoById($hongbao_id);
        if ($start['is_start'] =='1'){
        return true;
        }else{
        return false;
     }

    }

  public function  RoomData($room_id){
        $data['room_id']=$room_id;
       $list= D('Room')->where($data)->find();
       return $list;
  }

}