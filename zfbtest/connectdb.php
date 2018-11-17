<?PHP
error_reporting(E_ALL || ~E_NOTICE);
$con = mysql_connect('127.0.0.1', 'jinfu', 'root');
if (!$con) {
	$info = "连接数据库错误：" . mysql_error() . "错误原因" . mysql_error();
} else {
	$info = "成功连上数据库";
}


//选择数据库
mysql_select_db("jinfu", $con);
mysql_query("SET NAMES 'utf8'");
?>