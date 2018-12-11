<?php
class MessageModel extends CommonModel{
//通过id 判断是否有信息，返回条数
    public function messagenumber($user_id){
        $dbsql=D("Message");
        $map['user_id']=$user_id;
        $map['ifread']=0;//未读
        $count = $dbsql->where($map)->count(); // 查询满足要求的总记录数
        return $count;
    }
    public function messagelist($user_id)
    {
        $dbsql=D("Message");
        $map['user_id']=$user_id;
        //$map['ifread']=0;//未读
        $list = $dbsql->where($map)->order(array('id' => 'desc'))->limit(0 . ',' . 10)->select();
        // $count = $dbsql->where($map)->count(); // 查询满足要求的总记录数
        return $list;
    }
    public function readmessage($message_id)
    {
        $dbsql=D("Message");
        $map['id']=$message_id;
        //$map['ifread']=0;//未读
        $list = $dbsql->where($map)->select();
        if($list['ifread']==0){
            $data['ifread']=1;//标记已读
            $dbsql->where($map)->save($data);
        }
        // $count = $dbsql->where($map)->count(); // 查询满足要求的总记录数
        return $list;
    }
}