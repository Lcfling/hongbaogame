<?PHP

/* *

 * 功能：支付宝服务器异步通知页面

 * 版本：2.0

 * 修改日期：2016-11-01

 * 说明：

 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。



 *************************页面功能说明*************************

 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。

 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。

 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知

 */
require_once("config.php");
require_once 'wappay/service/AlipayTradeService.php';
error_reporting(E_ALL ^ E_DEPRECATED);
$con = mysql_connect("47.104.240.34","hongbao","9g8iAev1OJO5AF");
if (!$con)
{
    die('Could not connect: ' . mysql_error());
}
mysql_select_db("hongbaodb", $con);



$arr=$_POST;

$alipaySevice = new AlipayTradeService($config);

$alipaySevice->writeLog(var_export($_POST,true));

$result = $alipaySevice->check($arr);


if($result) {
    //验证成功


//商户订单号
    $out_trade_no = $_POST['out_trade_no'];

//支付宝交易号
    $trade_no = $_POST['trade_no'];


//交易状态
    $trade_status = $_POST['trade_status'];
    $total_amount = $_POST['total_amount'];

    if ($_POST['trade_status'] == 'TRADE_FINISHED') {


    } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
        //判断该笔订单是否在商户网站中已经做过处理

        $select_sql="select * from bao_order where out_trade_no=".$out_trade_no;

        $list=mysql_query($select_sql);
        $data = mysql_fetch_array($list);
        $time=time();
        $user_id=$data['user_id'];
        $total_amount=$total_amount*100;

        $sele_sql="select * from bao_paid where order_id=".$out_trade_no;
        $list1=mysql_query($sele_sql);
        $row = mysql_fetch_array($list1);

        $order_sql="update bao_order  set trade_no=$trade_no,status=1  where out_trade_no=".$out_trade_no;

        mysql_query($order_sql);

        if (!$row){
            $sql="insert into bao_paid (order_id,money,user_id,creatime,type,remark,is_afect)VALUES('$out_trade_no',$total_amount,$user_id,$time,1,'支付宝充值',1)";
            mysql_query($sql);
        }




        echo "success";        //请不要修改或删除

    } else {

        $sql="update bao_order set status=2 where out_trade_no=".$out_trade_no;
        mysql_query($sql);
        //验证失败

        echo "fail";    //请不要修改或删除

    }


}



function https_post($url,$data)

	{

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url); 

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

    curl_setopt($curl, CURLOPT_POST, 1);

    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);



	

    $ref =curl_exec($curl);

  

    if (curl_errno($curl)) {

		$file=fopen("curlerror.txt","w+");

		if (flock($file,LOCK_EX))

		{

			fwrite($file,'Errno'.curl_error($curl));

			flock($file,LOCK_UN);

		}

		fclose();

       

    }

    curl_close($curl);

    return $ref;

	

}

?>



