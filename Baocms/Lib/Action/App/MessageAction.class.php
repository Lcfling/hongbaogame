<?php
class MessageAction extends CommonAction
{
    //获取id的信息条数
    public function messagenumber(){
        $user_id=$this->uid;
        $users=D("Message");
        $data= $users->messagenumber($user_id);
        $this->ajaxReturn($data,"信息未读条数");
    }
    //获取用户所有信息列表
    public function messagelist(){
        $user_id=$this->uid;
        $users=D("Message");
        $data= $users->messagelist($user_id);
        $this->ajaxReturn($data,"最新十条信息列表");
    }
//阅读单条记录
    public function readmessage(){
        $message_id=$_POST['messageid'];
        $user_id=$this->uid;
        $users=D("Message");
        $data= $users->readmessage($message_id);
        $this->ajaxReturn($data,"单条信息的记录");
    }
}