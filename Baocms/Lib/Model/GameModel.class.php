<?php
class GameModel extends CommonModel
{
    protected $pk = 'id';
    protected $tableName = 'game';

    //查看是否有进行中的游戏
    public function is_free($roomid){


        $res=$this->where('roomid='.$roomid.' AND creatime > '.(time()-60))->find();
        if(!empty($res)){
            return false;
        }else{
            return true;
        }

    }

    //抢庄原子执行
    public function Robqueue($roomid,$uid){
        $tempNum=time().rand_string(8,1);
        Cac()->rPush('game_q_'.$roomid,$tempNum);
        $new=Cac()->lGet('game_q_'.$roomid,0);
        if($new==$tempNum){
            return true;
        }else{
            return false;
        }
    }
    public function unLock($roomid){
        Cac()->delete('game_q_'.$roomid);
    }
    public function getNewInfo($roomid){
        $res=$this->where('roomid='.$roomid.' AND creatime > '.(time()-51))->find();
        return $res;
    }

    public function getAllbetmoney($Gameid){
        $sql="SELECT SUM(multmoney) AS sum FROM __PREFIX__betted WHERE game_id=$Gameid";
        $res=$this->Query($sql);
        if(empty($res)){
            $money=0;
        }else{
            $money=$res[0]['sum'];
        }
        return $money;
    }

    public function insertbet($betmoney,$gameid,$betType,$uid){
        if($betType>10){
            $multiple=3.5;
        }else{
            $multiple=2;
        }
        $multmoney=$betmoney*$multiple;
        $data['user_id']=$uid;
        $data['game_id']=$gameid;
        $data['bettype']=$betType;
        $data['money']=$betmoney;
        $data['multmoney']=$multmoney;
        $data['creatime']=time();
        $id=D('Betted')->add($data);
        if($id>0){
            return true;
        }else{
            return false;
        }
    }

    public function creategame($uid,$roomid){
        $data['roomid']=$roomid;
        $data['user_id']=$uid;
        $data['is_balance']=0;
        $data['creatime']=time();
        $resultArr=array();
        for($i=0;$i<3;$i++){
            $resultArr[$i]=rand(1,6);
        }
        $resultStr=implode(" ",$resultArr);
        $data['out_number']=$resultStr;
        $id=$this->add($data);
        $res=$this->where('id='.$id)->find();
        //unset($res['out_number']);
        Cac()->set('game_new_room_'.$roomid,serialize($res));
        return $res;
    }

    public function is_createresult($gameid){
        $res=Cac()->get('is_create_result_'.$gameid);
        if($res>0){
            return true;
        }else{
            return false;
        }
    }

    public function historylist($roomid){
        $res=$this->where('roomid='.$roomid.' AND creatime < '.(time()-20))->order('creatime desc')->limit(10)->select();
        //echo $this->getlastsql();
        return $res;
    }
    public function down($id){
        $data['goon']=1;
        $res=$this->where(array('id'=>$id))->save($data);
        return $res;
    }
}