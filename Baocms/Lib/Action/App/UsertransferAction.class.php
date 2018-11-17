<?php
require_once LIB_PATH.'/GatewayClient/Gateway.php';

use GatewayClient\Gateway;

class UsertransferAction extends CommonAction
{
    //获取通讯录
    public function index(){
        $uid=$this->uid;

        $users=D("Usertransfer");
        $data= $users->addressbook($uid);

        $this->ajaxReturn($data,'好友通讯录');
    }
    // 转账
    public function transfer(){
        //获取用户余额
        $user_id=$this->uid;
        $to_id = (int) $_POST['to_id'];
        $money = (int) $_POST['money'];
        $zfb_pwd=(int)$_POST['zfb_pwd'];
        $money=$money*100;
        if ($zfb_pwd == "" || $to_id == "" || $money == ""){
            $this->ajaxReturn(null,"数据异常!请检查！toid=".$to_id." and money=".$money." and zfb=".$zfb_pwd,0);
        }


        if ($money<5000){
            $this->ajaxReturn(null,"单笔金额不能低于50元！",0);
        }


        $users=D("Users");
        //判断支付密码是否正确
        $data=$users->getUserByUid($user_id);
        if ($data['zfb_pwd']!= md5($zfb_pwd)){
            $this->ajaxReturn(null,"支付密码错误!",0);
        }

        $sql_money= $users->getUserMoney($user_id);
        if($sql_money<$money){
            $this->ajaxReturn($user_id,'账号余额不足',0);
        }

        $users=D("Usertransfer");
        $data= $users->transfer($user_id,$to_id,$money);
        $this->ajaxReturn($data,"转账成功!");
    }

    // 搜索用户
    public function search()
    {
        $to_id = (int)$_POST['to_id'];
        if ($to_id == "") {
            $this->ajaxReturn(null, "数据异常!请检查！");
        }
        $users = D("Usertransfer");
        $data = $users->search($to_id);
        if ($data) {
            $this->ajaxReturn($data, '好友用户信息');

        } else {
            $this->ajaxReturn(null, '用户不存在', 0);

        }
    }
    //转账记录
    public function transferinfo(){
        import('ORG.Util.Page'); // 导入分页类
        $_GET['p']=(int)$_POST['p'];
        $map=array();
        $map['from_id']=$this->uid;
        $count=D('Transfer')->where($map)->count($map);
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        //$pager = $Page->show(); // 分页显示输出
        $list = D('Transfer')->where($map)->order(array('creatime'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if(!empty($list)){
            foreach ($list as &$value){
                $toUser=D('Users')->getUserByUid($value['to_id']);
                unset($toUser['money']);
                $value=array_merge($value,$toUser);
                $value['creatime']=date('Y-m-d H:i:s',$value['creatime']);
            }
        }else{
            $list=array();
        }
        $data['list']=$list;
        $this->ajaxReturn($data,'转账记录');
    }
}