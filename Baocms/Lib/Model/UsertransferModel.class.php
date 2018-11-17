<?php
class UsertransferModel extends CommonModel{
//获取用户的下级通讯录
    public function addressbook($uid){

        import('ORG.Util.Page'); // 导入分页类
        $info['pid']=$uid;
        $userModel=D('Users');
        $_GET['p']=(int)$_POST['p'];
        $count = $userModel->where($info)->count($info); // 查询满足要求的总记录数
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数

        $list = $userModel->where($info)->order(array('user_id'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
     // $list = $m->where($info)->order(array('user_id' => 'desc'))->select();
        return $list;

    }
 //接受前端传值，进行转账
    public function transfer($user_id,$to_id,$money){


        //减去$user_id的转账额度
        $info['money']=-$money;
        $info['user_id']=$user_id;//转账ID
        $info['creatime']=time();
        $info['type']=51;//减少
        $info['goon']=0;
        $info['remark']='转账扣款';
        $info['is_afect']=1;

        $m=D('Paid');
      $m->add($info);



        //增加$to_id的到账额度
        $info['money']=$money;
        $info['user_id']=$to_id;//到账ID
        $info['creatime']=time();
        $info['type']=52;//增加
        $info['goon']=0;
        $info['remark']='转账到账';
        $info['is_afect']=1;
        $m->add($info);
//增加转账明细
        $info['from_id']=$user_id;
        $info['to_id']=$to_id;//
        $info['money']=$money;//
        $info['creatime']=time();
        $transfer=D('Transfer');

        if($transfer->add($info)){
            return true;
        }else{
            return false;
        }


    }

    public function search($uid){
        //根据user_id获取用户信息
        $m=D('Users');
         $data = $m->find(array('where' => array('account' => $uid)));
         return $data;
    }

    public function transferinfo($uid){
        //根据user_id获取转账记录
        $m=D('Transfer');
        $data = $m->find(array('where' => array('from_id' => $uid)));
        return $data;
    }

}

