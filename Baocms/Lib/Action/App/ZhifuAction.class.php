<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-10-26
 * Time: 16:26
 */

class ZhifuAction extends CommonAction{


    public function config(){
        return $data=array(
            'version'=>'1',                                    //版本号
            'customer_id'=>'10956',                          //商户编号
            'pay_type'=>'alipay',                            //支付类型
            'return_url'=>'',         //同步跳转URL
            'notify_url'=>'http://wkwk.zh.com/app/zhifu/callBacks',        //异步通知URL
            'return_type'=>'1',                           //返回类型
            'key'=>'a75190db6d2bc880e7adca9e7902e0fa' //秘钥
        );
    }

    public function indexs(){
        // 用户id
        $user_id=$this->uid;
        //$this->ajaxReturn(null,'充值维护中！',0);

        //订单金额
        $money=(int)$_POST['money'];

        if ($money<50){
            $this->ajaxReturn(null,'数据异常请检查');
        }

        if ($user_id == "" || $money==""){
            $this->ajaxReturn(null,'数据异常请检查');
        }

        //商户订单号
        $customer_order_no = $user_id.time().rand(1000,9999);
        $rand=rand(1,2);
        if ($rand == 1){
            $order=D('Order');
            $data['user_id']=$user_id;
            $data['out_trade_no']=$customer_order_no;
            $data['total_amount']=$money*100;
            $data['subject']='用户充值玫瑰花';
            $data['notify_time']=time();
            $data['status']='0';
            $data['zhifubao']=1;
            $id=$order->add($data);
            if($id>0){
                $re['url']="http://game1gao.weiquer.com/zfbtest/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
                $this->ajaxReturn($re,'充值',1);
            }else{
                $re['url']="http://baidu.com";
                $this->ajaxReturn($re,'充值故障！',0);
            }
        }else{
            $order=D('Order');
            $data['user_id']=$user_id;
            $data['out_trade_no']=$customer_order_no;
            $data['total_amount']=$money*100;
            $data['subject']='用户充值法拉利';
            $data['notify_time']=time();
            $data['status']='0';
            $data['zhifubao']=2;
            $id=$order->add($data);
            if($id>0){
                $re['url']="http://game1gao.weiquer.com/zfbtest2/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
                $this->ajaxReturn($re,'充值',1);
            }else{
                $re['url']="http://baidu.com";
                $this->ajaxReturn($re,'充值故障！',0);
            }
        }

    }

    public function index(){
        // 用户id
        $user_id=$this->uid;

        //$this->ajaxReturn(null,'充值维护中！',0);
        //订单金额
        $money=(int)$_POST['money'];

        if ($money<50){
            $this->ajaxReturn(null,'数据异常请检查');
        }

        if ($user_id == "" || $money==""){
            $this->ajaxReturn(null,'数据异常请检查');
        }

        //商户订单号
        $customer_order_no = $user_id.time().rand(1000,9999);

        $rand=rand(1,2);
        if ($rand == 1){
            $order=D('Order');
            $data['user_id']=$user_id;
            $data['out_trade_no']=$customer_order_no;
            $data['total_amount']=$money*100;
            $data['subject']='用户充值';
            $data['notify_time']=time();
            $data['status']='0';
            $data['zhifubao']=1;
            $id=$order->add($data);
            if($id>0){
                $re['url']="http://game1gao.weiquer.com/zfbtest/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
                $this->ajaxReturn($re,'充值',1);
            }else{
                $re['url']="http://baidu.com";
                $this->ajaxReturn($re,'充值故障！',0);
            }
        }else{
            $order=D('Order');
            $data['user_id']=$user_id;
            $data['out_trade_no']=$customer_order_no;
            $data['total_amount']=$money*100;
            $data['subject']='用户充值';
            $data['notify_time']=time();
            $data['status']='0';
            $data['zhifubao']=2;
            $id=$order->add($data);
            if($id>0){
                $re['url']="http://game1gao.weiquer.com/zfbtest2/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
                $this->ajaxReturn($re,'充值',1);
            }else{
                $re['url']="http://baidu.com";
                $this->ajaxReturn($re,'充值故障！',0);
            }
        }

    }



    function callBacks(){


        //版本
        $version = $_POST['version'];
        //订单状态
        $status = $_POST['status'];
        //商户编号
        $customer_id = $_POST['customer_id'];
        //平台订单号
        $order_no = $_POST['order_no'];
        //商户订单号
        $customer_order_no = $_POST['customer_order_no'];
        //交易金额
        $money = $_POST['money'];
        //支付类型
        $pay_type = $_POST['pay_type'];
        //订单备注说明
        $remark = $_POST['remark'];
        //md5验证签名串
        $sign = $_POST['sign'];


        $data['version']=$version;
        $data['status']=$status;
        $data['customer_id']=$customer_id;
        $data['order_no']=$order_no;
        $data['customer_order_no']=$customer_order_no;
        $data['money']=$money;
        $data['pay_type']=$pay_type;
        $data['remark']=$remark;

        //判断status
        if($status=1){
            $oder = M('Order');
            $where['out_trade_no']=$customer_order_no;
            $data['status']=1;
            $oder->where($where)->save($data);

            $list=$oder->where($where)->find();
            $remark = "支付宝充值";
            D('Users')->addmoney($list['user_id'],$money*100,1,1,$remark,$customer_order_no);
        } else{
            $oder = M('Order');
            $where['out_trade_no']=$customer_order_no;
            $data['status']=2;
            $oder->where($where)->save($data);
        }
        echo "success";

    }





    function _request($data,$curl, $https = true,$method='POST')
    {
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $curl);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);//CURLOPT_HEADER 设置头部
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);//设置内容
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//是否进行服务器主机验证 不验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//是否验证证书 验证
            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置数据
            }

            $content = curl_exec($ch);//得到一个值
            curl_close($ch);//关闭资源 释放

            return $content;//返回得到的值
        }
    }

    function getSign($arr, $miKey) {
        $str = "";
        if ($arr) {
            ksort($arr);
            foreach ($arr as $key => $value) {
                $str = $str . $key . '=' . $value . '&amp';
            }
            $str = $str . 'key=' . $miKey;
            //die($str);
            $sign = md5($str);
            return $sign;
        } else {
            return "error:数组为空!";
        }
    }

    public function min_user(){
        $hongbao_id='822';
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
        print_r($min);
    }


    public function shuzu(){

        $data=array(


        );
        if (empty($data)){
            echo "111";
        }else{
            echo "222";
        }
    }

}
