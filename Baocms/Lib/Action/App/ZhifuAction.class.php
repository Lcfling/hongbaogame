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
            $order=M('order');
            $data['user_id']=$user_id;
            $data['out_trade_no']=$customer_order_no;
            $data['total_amount']=$money*100;
            $data['subject']='用户充值';
            $data['notify_time']=time();
            $data['status']='0';
            $data['zhifubao']=1;
            $order->add($data);

            $re['url']="http://game1.zllmqw.com/zfbtest/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
            $this->ajaxReturn($re,'充值链接');
        }else{
            $order=M('order');
            $data['user_id']=$user_id;
            $data['out_trade_no']=$customer_order_no;
            $data['total_amount']=$money*100;
            $data['subject']='用户充值';
            $data['notify_time']=time();
            $data['status']='0';
            $data['zhifubao']=2;
            $order->add($data);

            $re['url']="http://game1.zllmqw.com/zfbtest2/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
            $this->ajaxReturn($re,'充值链接');
        }

    }

    public function index(){

        // 用户id
        $user_id=$this->uid;


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
             $order=M('order');
             $data['user_id']=$user_id;
             $data['out_trade_no']=$customer_order_no;
             $data['total_amount']=$money*100;
             $data['subject']='用户充值';
             $data['notify_time']=time();
             $data['status']='0';
             $data['zhifubao']=1;
             $order->add($data);

             $re['url']="http://game1.zllmqw.com/zfbtest/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
             $this->ajaxReturn($re,'充值链接');
         }else{
             $order=M('order');
             $data['user_id']=$user_id;
             $data['out_trade_no']=$customer_order_no;
             $data['total_amount']=$money*100;
             $data['subject']='用户充值';
             $data['notify_time']=time();
             $data['status']='0';
             $data['zhifubao']=2;
             $order->add($data);

             $re['url']="http://game1.zllmqw.com/zfbtest2/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
             $this->ajaxReturn($re,'充值链接');
         }


    }

    public function notifyUrl(){

        $payOrderId=$_POST['payOrderId'];//支付中心订单号
        $mchId=$_POST['mchId'];//商户id
        $appId=$_POST['appId'];//appid

        $productId=$_POST['productId'];//支付方式ID
        $mchOrderNo=$_POST['mchOrderNo'];//支付订单号
        $amount=$_POST['amount'];//金额 以分单位
        $channelOrderNo=$_POST['channelOrderNo'];//三方支付渠道订单号
        $status=$_POST['status'];//支付状态,0-订单生成,1-支付中,2-支付成功,3-业务处理完成
        $paySuccTime=$_POST['paySuccTime'];// 支付成功时间 精确到毫秒
        $backType=$_POST['backType'];//通知类型，1-前台通知，2-后台通知
        $sign=$_POST['sign'];//签名值

        $data['payOrderId']=$payOrderId;  //1
        $data['mchId']=$mchId;
        $data['appId']=$appId;  //5
        $data['productId']=$productId; //3
        $data['mchOrderNo']=$mchOrderNo; //4
        $data['amount']=$amount;  //2
        $data['status']=$status;
        $data['paySuccTime']=$paySuccTime; //6
        $data['backType']=$backType;
        $data['channelOrderNo']=$channelOrderNo;

        $signs=$this->getSign($data);
        if ($sign == $signs ){
            if ($status  == 2){
                $order=M('order');
                $where['out_trade_no']=$mchOrderNo;
                $save['status']=1;
                $order->where($where)->save($save);
                $user_info=$order->where($where)->find();

                $paid=M('Paid');
                $where['order_id']=$mchOrderNo;
                $list=$paid->where($where)->find();

                if (!$list){
                    $info['order_id']=$mchOrderNo;
                    $info['money']=$amount;
                    $info['user_id']=$user_info['user_id'];
                    $info['creatime']=time();
                    $info['type']=1;
                    $info['remark']='支付宝充值';
                    $info['is_afect']=1;
                    $paid->add($info);
                    
                }

            }
        }
        echo "success";

    }




    function convertUrlQuery($query)
    {
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }



    function getSign($Obj){

        foreach ($Obj as $k => $v)
        {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String =$this->formatBizQueryParaMap($Parameters, false);
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String."&key=".'INIMNJGNPZ00ZKEIUNTQL3FBE411OWWFRRZPXXPD6C0JGMXRN3XG1EHAYWBHMX72VUMGUHV5WB3J3XJPC1ZXBTRVQV6MDORR8EN1WAPMDLYD1VFH2BSOYD04WTNMEBDN';
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }

    function formatBizQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }

        if (strlen($buff) > 0)
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }
    function https_post_kf($url,$data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            return 'Errno'.curl_error($curl);
        }
        curl_close($curl);
        return $result;
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

    function get_Sign($arr, $miKey) {
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
