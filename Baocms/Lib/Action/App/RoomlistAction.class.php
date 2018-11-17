<?php
require_once LIB_PATH.'/GatewayClient/Gateway.php';

class RoomlistAction extends CommonAction
{
    public function index()
    {
        $gametype=$_POST['gametype'];
        $roomlist=D('Room')->getroomlist($gametype);
        if(!empty($roomlist)){
            $this->ajaxReturn($roomlist);
        }else{
            $this->ajaxReturn('','未知错误！',1);
        }

    }
    public function room(){
        $roomid=$_POST['roomid'];
        $roomlist=D('Room')->getroom($roomid);
        if(!empty($roomlist)){
            $this->ajaxReturn($roomlist,'请求成功！',1);
        }else{
            $this->ajaxReturn('','未知错误！',0);
        }
    }
}