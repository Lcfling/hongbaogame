<?php

/* *

 * 功能：支付宝页面跳转同步通知页面

 * 版本：2.0

 * 修改日期：2016-11-01

 * 说明：

 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。



 *************************页面功能说明*************************

 * 该页面可在本机电脑测试

 * 可放入HTML等美化页面的代码、商户业务逻辑程序代码

 */

require_once("config.php");

require_once 'wappay/service/AlipayTradeService.php';



?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<meta name="viewport" content="maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width,initial-scale=1.0" />

<title>支付成功,请返回App</title>



			<style type="text/css">

            		body{

						padding:0px;

						margin:0px;

						width:100%;

						height:100%;

						background:#F2F2F2;

						}

						

					.success{

						width:100%;

						height:100%;

						text-align:center;

						margin-top:100px;

						}

						

					.success img{

							width:50px;

							height:50px;								

								}

						

					.text{

						text-align:center;

						margin-top:40px;

						font-size:28px;

						color:#33CCCC;

						}

						

					.ok{

						text-align:center;

						margin-top:60px;

						font-size:30px;

						}

						

					.ok a{

						text-decoration:none;

						background-color:#33CCCC;

						border: 1px #26bbdb solid;

            			border-radius: 3px;

						}

						

					a:active {

 						color:#3399FF; /*鼠标经过的颜色变化*/

						}

						

					

            </style>

</head>



<body>

					<div class="success"><img src="http://hongbao.webziti.com/img/duihao.png"></img></div>

					<div class="text">支付成功 请返回App</div>



</body>

</html>



<script>

img.onclick=function(){

  return false;

 



</script>

