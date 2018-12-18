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
            'notify_url'=>'http://wkwk.zgzyph.com/app/zhifu/callBacks',        //异步通知URL
            'return_type'=>'1',                           //返回类型
            'key'=>'a75190db6d2bc880e7adca9e7902e0fa' //秘钥
        );
    }

    public function index(){

        $money=(int)$_POST['money'];  //支付金额
        //$this->ajaxReturn(null,"支付维护5分钟",0);
        $user_id=$this->uid;  //用户id
        if ($money == "" || $user_id == ""){
            $this->ajaxReturn(null,"数据异常请检查!",0);
        }
        if ($money <50){
            $this->ajaxReturn(null,"数据异常请检查!",0);
        }

        //商户订单号
        $mchOrderNo = $user_id.time().rand(1000,9999);

        $appId = "18121517331745056";//商户ID
        $user = "qingpeng";    //用户名
        $orderNo = $mchOrderNo;//订单号
        $amount = $money; //订单金额
        $timestamp = time() * 1000; // 时间戳
        $md5Key = "ekQcG8puSbmXdtiemBBdlaCGWeYBEgdUWpYnWqEghLfejpVSWpZCLJvO50pbL9hu";//签名

        $noticeUrl = "http://notify.6q1s1.com/app/zhifu/noticeUrl_1216";//回调地址
        //拼接请求地址
        $url = "http://api.ngjmr.cn/order/create?appId=" . $appId;
        $url .= "&user=$user";
        $url .= "&orderNo=$orderNo";
        $url .= "&timestamp=$timestamp";
        $url .= "&money=$amount";
        $url .= "&noticeUrl=" . urlencode($noticeUrl);
        $url .= "&sign=" . md5("$appId^$user^$orderNo^$amount^$timestamp^$md5Key");

        header("Content-Type: text/html;charset=utf-8");
        //  echo "sign=md5($appId^$user^$orderNo^$amount^$timestamp^$md5Key)";
        // echo "<hr />";
        //echo htmlspecialchars($url);
        //  echo "<hr />";

        $data=json_decode(file_get_contents($url),true);

        if ($data['result'] == 'success'){

            $order=M('order');
            $data1['user_id']=$user_id;
            $data1['out_trade_no']=$orderNo;
            $data1['total_amount']=$amount*100;
            $data1['subject']='用户充值';
            $data1['notify_time']=time();
            $data1['status']='0';
            $data1['zhifubao']=5;

            $order->add($data1);

            $re['url']=$data['data']['qrCode'];
            $this->ajaxReturn($re,'充值链接');

        }

    }
    public function noticeUrl_1216(){
        $appId=$_GET['appId'];  // 商户ID
        $user=$_GET['user'];  //用户名
        $order_no=$_GET['order_no']; //支付订单号

        $out_order_no=$_GET['out_order_no']; //	商户自己的订单号
        $trade_money=$_GET['trade_money'];   // 交易金额（支付金额）
        $trade_time=$_GET['trade_time'];   //  支付时间

        $rebate=$_GET['rebate'];    //税点
        $rebate_money=$_GET['rebate_money'];   //税费
        $sign=$_GET['sign'];  //加密签名

        $md5Key = "ekQcG8puSbmXdtiemBBdlaCGWeYBEgdUWpYnWqEghLfejpVSWpZCLJvO50pbL9hu";//签名
        $signs=md5("$appId^$user^$order_no^$out_order_no^$trade_time^$md5Key");


        if ($sign == $signs){
            // 保存

//            $myfile = fopen("newfile1218.txt", "w") or die("Unable to open file!");
//            fwrite($myfile, $order_no);
//            fclose($myfile);


            $order=M('order');
            $where['out_trade_no']=$out_order_no;
            $save['status']=1;
            $order->where($where)->save($save);
            $user_info=$order->where($where)->find();

            $paid=M('Paid');
            $where1['order_id']=$out_order_no;
            $list=$paid->where($where1)->find();

            if (!$list){
                $info['order_id']=$out_order_no;
                $info['money']=$trade_money*100;
                $info['user_id']=$user_info['user_id'];
                $info['creatime']=time();
                $info['type']=1;
                $info['remark']='支付宝充值';
                $info['is_afect']=1;
                $paid->add($info);
            }



        }

        echo "success";


    }
























    public function zhifu(){


        $money=(int)$_POST['money'];  //支付金额

        $user_id=$this->uid;  //用户id

        if ($money == "" || $user_id == ""){
            $this->ajaxReturn(null,"数据异常请检查!",0);
        }
        if ($money <50){
            $this->ajaxReturn(null,"数据异常请检查!",0);
        }
        $money=$money+rand(1,5);

        //商户订单号
        $mchOrderNo = $user_id.time().rand(1000,9999);


        $data["mchId"] = '20000001';//商户ID
        $data["appId"] = "33ae61f1ec604c808701347e02291a61";//appid
        $data["productId"]=8017;//支付方式
        $data["mchOrderNo"] = $mchOrderNo;//订单号
        $data["currency"] = "cny";//币种

        $data["amount"] = $money*100;//分开始 额度
        $data["notifyUrl"] = "http://hongbao.webziti.com/app/zhifu/notifyUrl";//回调
        $data["subject"] = "用户充值";//产品主题
        $data["body"] = "用户充值";//产品描述
        $data["extra"] = '{"timeout_express":"10m"}';//该笔订单允许的最晚付款时间
        $data["sign"] =$this->getSign($data);//签名

        $url = "http://47.244.129.122:3020/api/pay/create_order";
        $data = json_encode($data);
        $params['params']=$data;
        $result = $this->https_post_kf($url,$params);
        $final = json_decode($result);

        if ($final->retCode == "SUCCESS"){

            $order=M('order');
            $data1['user_id']=$user_id;
            $data1['out_trade_no']=$mchOrderNo;
            $data1['total_amount']=$money*100;
            $data1['subject']='用户充值';
            $data1['notify_time']=time();
            $data1['status']='0';
            $data1['zhifubao']=3;

            $order->add($data1);
            $data_url= parse_url($final->payParams->payUrl);
            $url=$this->convertUrlQuery($data_url['query']);
            $re['url']=$url['params'];
            print_r($re);
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

    public function notifyUrls(){

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

                //判断该笔订单是否在商户网站中已经做过处理
                $select_sql="select * from bao_order where out_trade_no='$mchOrderNo'";

                $list=mysql_query($select_sql);


                $data = mysql_fetch_array($list);
                $time=time();
                $user_id=$data['user_id'];
                // $total_amount=$amount*100;

                $order_sql="update bao_order  set status=1  where out_trade_no='$mchOrderNo'";

                mysql_query($order_sql);


                $sele_sql="select * from bao_paid where order_id='$mchOrderNo'";

                $list1=mysql_query($sele_sql);
                $row = mysql_fetch_array($list1);


                if (!$row){
                    $sql="insert into bao_paid (order_id,money,user_id,creatime,type,remark,is_afect)VALUES('$mchOrderNo',$amount,$user_id,$time,1,'支付宝充值',1)";

                    mysql_query($sql);
                }

            }
        }
        echo "success";

    }


    public function xinzhifu(){


        $money=(int)$_POST['money'];  //支付金额
        // $money=0.01;
        $user_id=$this->uid;  //用户id
        //$user_id=6667022;
        if ($money == "" || $user_id == ""){
            $this->ajaxReturn(null,"数据异常请检查!",0);
        }
        if ($money <50){
            $this->ajaxReturn(null,"数据异常请检查!",0);
        }
        $money=$money+rand(1,5);

        //商户订单号
        $mchOrderNo = $user_id.time().rand(1000,9999);




        //从网页传入price:支付价格， istype:支付渠道：1-支付宝；2-微信支付
        $price = $money;
        $istype =1;

        $orderuid = $user_id;       //此处传入您网站用户的用户名，方便在平台后台查看是谁付的款，强烈建议加上。可忽略。

        //校验传入的表单，确保价格为正常价格（整数，1位小数，2位小数都可以），支付渠道只能是1或者2，orderuid长度不要超过33个中英文字。

        //此处就在您服务器生成新订单，并把创建的订单号传入到下面的orderid中。
        $goodsname = "test";
        $orderid = $mchOrderNo;    //每次有任何参数变化，订单号就变一个吧。
        $uid = "71";//"此处填写平台的uid";
        $token = "17261f17e666507c7adde8882c698887";//"此处填写平台的Token";
        $return_url = 'http://ad.yiaigo.com/zfb3f/1214/payreturn.php';
        $notify_url = 'http://hongbao.webziti.com/app/zhifu/paynotify';

        $key = md5($goodsname. $istype . $notify_url . $orderid . $orderuid . $price . $return_url . $token . $uid);
        //经常遇到有研发问为啥key值返回错误，大多数原因：1.参数的排列顺序不对；2.上面的参数少传了，但是这里的key值又带进去计算了，导致服务端key算出来和你的不一样。

        $returndata['goodsname'] = $goodsname;
        $returndata['istype'] = $istype;
        $returndata['key'] = $key;
        $returndata['notify_url'] = $notify_url;
        $returndata['orderid'] = $orderid;
        $returndata['orderuid'] =$orderuid;
        $returndata['price'] = $price;
        $returndata['return_url'] = $return_url;
        $returndata['uid'] = $uid;


        $url = "https://www.500epay.com/pay?format=json";

        $result = $this->https_post_kf($url,$returndata);
        $final = json_decode($result);

        if ($final->code == 1){
            $order=M('order');
            $data1['user_id']=$user_id;
            $data1['out_trade_no']=$mchOrderNo;
            $data1['total_amount']=$money*100;
            $data1['subject']='用户充值';
            $data1['notify_time']=time();
            $data1['status']='0';
            $data1['zhifubao']=4;

            $order->add($data1);

            $re['url']=$final->data->qrcode;

            $this->ajaxReturn($re,'充值链接');
        }
    }


    public function  paynotify(){
        $platform_trade_no = $_POST["platform_trade_no"];
        $orderid = $_POST["orderid"];
        $price = $_POST["price"];
        $realprice = $_POST["realprice"];
        $orderuid = $_POST["orderuid"];
        $key = $_POST["key"];

        //校验传入的参数是否格式正确，略

        $token = "17261f17e666507c7adde8882c698887";

        $temps = md5($orderid . $orderuid . $platform_trade_no . $price . $realprice . $token);

        if ($temps != $key){
            return $this->jsonError("key值不匹配");
        }else{
            //校验key成功，是自己人。执行自己的业务逻辑：加余额，订单付款成功，装备购买成功等等。
            // 保存

            $order=M('order');
            $where['out_trade_no']=$orderid;
            $save['status']=1;
            $order->where($where)->save($save);
            $user_info=$order->where($where)->find();

            $paid=M('Paid');
            $where['order_id']=$orderid;
            $list=$paid->where($where)->find();

            if (!$list){
                $info['order_id']=$orderid;
                $info['money']=$price*100;
                $info['user_id']=$user_info['user_id'];
                $info['creatime']=time();
                $info['type']=1;
                $info['remark']='支付宝充值';
                $info['is_afect']=1;
                $paid->add($info);
            }

            echo "OK";
        }

    }




    //返回错误
    function jsonError($message = '',$url=null)
    {
        $return['msg'] = $message;
        $return['data'] = '';
        $return['code'] = -1;
        $return['url'] = $url;
        return json_encode($return);
    }

    //返回正确
    function jsonSuccess($message = '',$data = '',$url=null)
    {
        $return['msg']  = $message;
        $return['data'] = $data;
        $return['code'] = 1;
        $return['url'] = $url;
        return json_encode($return);
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



    public function indexs(){

        // 用户id
        // $user_id=$this->uid;
        //$user_id=$_GET['uid'];
        $user_id=666;
        //订单金额
        //  $money=(int)$_POST['money'];
        $money=0.01;
//        if ($money<50){
//            $this->ajaxReturn(null,'数据异常请检查');
//        }

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

            //  $re['url']="http://game1.zllmqw.com/zfbtest/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
            $re['url']="http://hongbao.webziti.com/zfbtest/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
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

            //   $re['url']="http://game1.zllmqw.com/zfbtest2/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
            $re['url']="http://hongbao.webziti.com/zfbtest2/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
            $this->ajaxReturn($re,'充值链接');
        }


    }

    public function index_bk(){

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

        $order=M('order');
        $data['user_id']=$user_id;
        $data['out_trade_no']=$customer_order_no;
        $data['total_amount']=$money*100;
        $data['subject']='用户充值';
        $data['notify_time']=time();
        $data['status']='0';
        $order->add($data);

        $re['url']="https://www.dhwangluo.top/zfbtest/zfbpay/wappay/pay.php?order_id=".$customer_order_no."&money=".$money;
        $this->ajaxReturn($re,'充值链接');

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

//    function get_Sign($arr, $miKey) {
//        $str = "";
//        if ($arr) {
//            ksort($arr);
//            foreach ($arr as $key => $value) {
//                $str = $str . $key . '=' . $value . '&amp';
//            }
//            $str = $str . 'key=' . $miKey;
//            //die($str);
//            $sign = md5($str);
//            return $sign;
//        } else {
//            return "error:数组为空!";
//        }
//    }

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