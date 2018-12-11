<?php
class UsersModel extends CommonModel
{
    protected $pk = 'user_id';
    protected $tableName = 'users';
    //protected $_integral_type = array('share' => '发帖分享', 'reply' => '回复帖子', 'mobile' => '手机认证', 'email' => '邮件认证');

    /**根据用户id获取用户信
     * @param $user_id
     * @param bool $cleanCache
     * @return $userinfo
     */
    public function getUserByUid($user_id,$cleanCache=false){
        if($cleanCache){
            Cac()->set('userinfo_'.$user_id,null);
            $data = $this->find(array('where' => array('user_id' => $user_id)));
        }else{
            $data=Cac()->get('userinfo_'.$user_id);
            $data=unserialize($data);
            if(!empty($data)){
                return $this->_format($data);
            }else{
                $data = $this->find(array('where' => array('user_id' => $user_id)));
                Cac()->set('userinfo_'.$user_id,serialize($data));
            }
        }

        //$data = $this->find(array('where' => array('account' => $account)));
        return $this->_format($data);
    }
    /**根据用户mobile获取用户信
     * @param $user_id
     * @param bool $cleanCache
     * @return $userinfo
     */
    public function getUserByMobile($mobile,$cleanCache=false){
        if($cleanCache){
            //Cac()->set('userinfo_'.$mobile,null);
            $data = $this->find(array('where' => array('account' => $mobile)));
            Cac()->set('userinfo_mobile_'.$mobile,null);
        }else{
            $data=Cac()->get('userinfo_mobile_'.$mobile);
            if($data!=null){
                $data=unserialize($data);
            }
            if(!empty($data)){
                return $this->_format($data);
            }else{
                $data = $this->find(array('where' => array('user_id' => $mobile)));
                Cac()->set('userinfo_mobile_'.$mobile,serialize($data));
            }
        }
        //$data=$this->where(array('account'=>(String)$mobile))->find();
        //$data = $this->find(array('where' => array('account' => (String)$mobile)));

        return $data;
    }

    /**获取用户余额
     * @param $uid
     * @return mixed
     */
    public function getUserMoney($uid){
        $sql="SELECT SUM(money) AS usermoney FROM __PREFIX__paid WHERE user_id=$uid";
        $res=$this->Query($sql);
        $money=$res[0]['usermoney'];
        return $money;
    }

    /**更新用户缓存
     * @param $userInfo
     * @return mixed
     */
    public function updateLoginCache($userInfo){
        $userInfo['last_ip']=$data['last_ip']=getip();

        $userInfo['last_time']=$data['last_time']=time();
        $token=rand_string(6,1);
        $userInfo['token']=$data['token']=md5($token);

        $this->where(array('account'=>(string)$userInfo['account']))->save($data);
        Cac()->set('userinfo_'.$userInfo['user_id'],serialize($userInfo));
        Cac()->set('userinfo_mobile_'.$userInfo['account'],serialize($userInfo));
        return $userInfo;
    }

    public function insertUserInfo($mobile,$pid=0){
        $info['account']=$mobile;
        $info['password']=md5(rand_string(11,1));
        $info['nickname']=rand_string(6,1);
        $info['money']=0;
        $info['mobile']=$mobile;
        $info['reg_ip']=$info['last_ip']=getip();
        $info['reg_time']=$info['last_time']=time();
        $token=$info['token']=md5(rand_string(6,1));
        if($pid==0||$pid==""||$pid==null)
            $pid=0;
        $info['pid']=$pid;
        $this->add($info);
        $userInfo=$this->find(array('where'=>array('account'=>$mobile)));
        $userInfo['nickname']='*'.$userInfo['user_id'];
        $data['nickname']='*'.$userInfo['user_id'];
        $this->where(array('account'=>$mobile))->save($data);
        Cac()->set('userinfo_'.$userInfo['user_id'],serialize($userInfo));
        Cac()->set('userinfo_mobile_'.$userInfo['account'],serialize($userInfo));
        return $userInfo;
    }

    /**添加用户金额
     * @param $uid
     * @param $money
     * @param $type
     * @param int $is_afect
     * @param string $remark
     * @return bool
     */
    public function addmoney($uid,$money,$type,$is_afect=1,$remark='',$order_id=0){
        $info['order_id']=0;
        $info['money']=$money;
        $info['user_id']=$uid;
        $info['creatime']=time();
        $info['type']=$type;
        $info['remark']=$remark;
        $info['is_afect']=$is_afect;
        $info['order_id']=$order_id;
        $m=D('Paid');
        if($m->add($info)){
            return true;
        }else{
            return false;
        }
    }

    public function reducemoney($uid,$money,$type,$is_afect=1,$remark=''){
        $info['order_id']=0;
        $info['money']=-$money;
        $info['user_id']=$uid;
        $info['creatime']=time();
        $info['type']=$type;
        $info['remark']=$remark;
        $info['is_afect']=$is_afect;

        $m=D('Paid');
        if($m->add($info)){
            return true;
        }else{
            return false;
        }
    }
    //获取一个随机机器用户信息
    public function getrandUser(){
        $idLen=Cac()->lLen('randUserList');
        if(!$idLen>0){
            $ids=$this->where(array('is_robot'=>1))->field('user_id')->select();
            shuffle($ids);
            foreach ($ids as $id){
                Cac()->rPush('randUserList',$id['user_id']);
            }
        }
        $Uid=Cac()->lPop('randUserList');
        $User=$this->getUserByUid($Uid);
        Cac()->rPush('randUserList',$Uid);
        return $User;
    }
    //获取一个随机机器用户信息
    public function uprandUserCache(){
        Cac()->delete('randUserList');
        $ids=$this->where(array('is_robot'=>1))->field('user_id')->select();
        foreach ($ids as $id){
            $s=$this->getUserByUid($id['user_id'],true);
            print_r($s);
            Cac()->rPush('randUserList',$id['user_id']);
        }

    }

    //冻结金额
    public function frozen($uid,$money,$remark='资金冻结'){
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
    public function unfrozen($uid,$money,$remark='资金冻结'){
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
    /**修改用户资料
     * @param $user_id
     * @param $user_name
     * @param $user_img
     */
    public function setuserinfo($user_id,$user_name){

        $user=D('Users');
        $where['user_id']=$user_id;
        $data['nickname']=$user_name;
        $list=$user->where($where)->save($data);
        if ($list){
            Cac()->set('userinfo_'.$user_id,null);
            return true;
        }else{
            return false;
        }
    }

    public function setface($user_id,$user_img){
        $user=M("users");
        $where['user_id']=$user_id;
        $data['face']=$user_img;
        $list=$user->where($where)->save($data);
        if ($list){
            Cac()->delete('userinfo_'.$user_id);
            return true;
        }else{
            return false;
        }

    }
    /** 绑定银行卡 */
    public function add_bankinfo($user_id,$user_name,$bank_num,$bank_info){
        $bank=M('Bank');
        $data['user_id']=$user_id;
        $data['user_name']=$user_name;
        $data['bank_num']=$bank_num;
        $data['bank_info']=$bank_info;

        if ($bank->add($data)){
            return true;
        }else{
            return false;
        }

    }

    /**绑定用户支付宝
     * @param $user_id
     * @param $user_zfb
     */
    public function setzyb($user_id,$name,$zfb_num){

        $users=D('Users');
        $where['user_id']=$user_id;
        $data['name']=$name;
        $data['zfb_num']=$zfb_num;
        $list=$users->where($where)->save($data);
        if ($list){
            Cac()->delete('userinfo_'.$user_id);
            return true;
        }else{
            return false;
        }
    }
     // 设置支付密码
    public function setpay($user_id,$zfb_pwd){
        $users=D('Users');
        $where['user_id']=$user_id;
        $data['zfb_pwd']=md5($zfb_pwd);
        $status=$users->where($where)->save($data);
        if ($status){
            Cac()->delete('userinfo_'.$user_id);
            return true;
        }else{
            return false;
        }
    }
    //解除支付密码
    public function set_paypwd($user_id){

        $users=D('Users');
        $whrer['user_id']=$user_id;
        $sava['zfb_pwd']="";
        $status=$users->where($whrer)->save($sava);
        if ($status){
            return true;
        }else{
            return false;
        }
    }

    //解除支付宝
    public function setzfb($user_id){
        //$users=M('Users');
        $where['user_id']=$user_id;
        $data['zfb_num']=null;
        $data['name']='';
        $list=$this->where($where)->save($data);
        if ($list){
            Cac()->delete('userinfo_'.$user_id);
            return true;
        }else{
            return false;
        }
    }

    /**用户提现
     * @param $user_id
     * @param $money
     * @param $zfb
     */
    public function txmoney($user_id,$money){

        $user_money=$this->getUserMoney($user_id);

        if (($money+($money*0.01))>$user_money){
            $data['msg']='提现金额大于剩余金额';
            $data['status']=0;
            return $data;
        }

        $user=D('Users');
        $where['user_id']=$user_id;
        $list=$user->where($where)->find();

        $tixian=D('Tixian');
        $data['user_id']=$user_id;
        $data['money']=$money*100;
        $data['zfb_num']=" ";
        $data['user_name']=" ";
        $data['time']=time();
        $data['status']=0;
        $data['rate']=($money*0.01*100);
        $tixian->add($data);

        $paid=D('Paid');
        $paid_data['money']=-($money*100+($money*0.01*100));
        $paid_data['user_id']=$user_id;
        $paid_data['creatime']=time();
        $paid_data['type']=3;
        $paid_data['remark']="用户提现";
        $paid->add($paid_data);


        $data1['msg']="提现成功";
        $data1['status']=1;
        return $data1;
    }

    /**我的推荐
     * @param $user_id
     */
    public function use_pid($user_id){


        $tb_users=M("Users");
        $where['pid']=$user_id;
        $list=$tb_users->where($where)->select();

        $data['x'][]=$list;
        foreach ($list as $k){
            $where['pid']=$list[$k]['user_id'];
            $list1=$tb_users->where($where)->field();
            $data['xx'][]=$list1;
        }
        return $data;
    }

    /**我的收益
     * @param $user_id
     */
    public function sum_money($user_id){
        $lastid=(int)$_POST['lastid'];
        $fanyong=M('Fanyong');
        $where['fenyong_id']=$user_id;
        //收益总金额
        $sum_money=$fanyong->where($where)->field('sum(fenyong_edu) as sum_money')->select();


        $begintime=date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d'),date('Y')));

        $catime = strtotime($begintime); //开始时间搓
        $endtime=date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1);
        $catime1 = strtotime($endtime); //结束时间搓

        //今日总收益
        //  $str=date("Y-m-d",time());//当前日期
        $where1['fyDate']=array('EGT',$catime);

        $where1['fenyong_id']=$user_id;
        $money=  $fanyong->where($where1)->field('sum(fenyong_edu) as money')->select();

        //收益明细


        if($lastid>0){
            $where['ID']=array('lt',$lastid);
        }
        $list=$fanyong->where($where)->order(array('fyDate'=>'desc'))->limit(15)->select();
        $users=D('Users');
        foreach ($list as &$v){
            $user=$users->getUserByUid($v['fabao_id']);
            $v['nickname']=$user['nickname'];
            $v['fyDate']=date('Y-m-d',$v['fyDate']);
            $lastID=$v['ID'];
        }

        $data['sum_money']=$sum_money[0]['sum_money'];  // 收益总金额
        $data['money']=$money[0]['money']; //今日收益总金额
        $data['list']=$list;  //收益明细
        $data['lastid']=$lastID;
        //$data['show']=$show; //   分页显示

        return $data;
    }
    //提现锁
    public function txLock($uid,$str){

        Cac()->rPush('txLock'.$uid,$str);
        $value=Cac()->lGet('txLock'.$uid,0);
        if($value==$str){
            return true;
        }else{
            return false;
        }
    }
    //提现解锁
    public function txopenLock($uid){
        Cac()->delete('txLock'.$uid);
    }

    //设置密码
    public function set_pwd($use_id,$pwd){

        $users=M('users');
        $where['user_id']=$use_id;
        $save['password']=md5($pwd);
        $status=$users->where($where)->save($save);
        if ($status){
            return true;
        }else{
            return false;
        }
    }


}