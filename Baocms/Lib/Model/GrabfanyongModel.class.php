<?PHP

class GrabfanyongModel extends CommonModel{



//获取分销信息数据
//获取分销级
//匹配字符串，获取分销数额
    private $numLevel;
    private $globalEdu;//免死额度;
    private $priceAry=array();
    private $priceString;
    private $current_userid;
    private $len;
    private $pidAry=array();
    private $gametype;
    public function fanyong($user_id,$mianis_edu,$type){

        global $current_userid,$globalEdu,$gametype;
        $current_userid=$user_id;
        $globalEdu=$mianis_edu;
        $gametype=$type;
        $distribution=D("distribution");
        $where['ID']='2';
        $line=$distribution->where($where)->find();

        global $numLevel;
        $numLevel=$line['numRen'];
        global $len;
        $len=$numLevel;

        $priceString=$line['price'];
        global $priceAry;
        $priceAry = explode(",",$priceString);//分销额度存入数组
        $this->allPid($current_userid);

    }


//获取uid的所有上级
 private function allPid($curId){

        global $len;

        if($len==0){
            $this->addFenYong();//数据保存

            return;
        }else{
            $len--;
        }

        $users=D('Users');
        $where['user_id']=$curId;
        $line_pid=$users->where($where)->find();


        $next_userid=$line_pid['pid'];//当前上级

        if($next_userid!=0){//断层，没有上级
        global $pidAry;
            array_push($this->pidAry,$next_userid);

        }
        $this->allPid($next_userid);

    }



//对应返佣额度
    private function addFenYong(){

        global $len,$pidAry,$numLevel,$priceAry,$globalEdu,$current_userid,$gametype;
        //获取真实pid的数组长度
        $pidAryLen=count($this->pidAry);

        if ($pidAryLen==$len){
            return;

        }

        $newPrice=$priceAry[$len];
        $p1id=$this->pidAry[$len];
        $fanyong=D('fanyong');
        $data['fabao_id']=$current_userid;
        $data['miansi_edu']=$globalEdu;
        $data['fenyong_id']=$p1id;
        $data['fenyong_edu']=$newPrice*$globalEdu/100;
        $data['type']=$gametype;
        $data['Lv']=$len+1;
        $data['fyDate']=time();
        $fanyong->add($data);
        //佣金插入现金表
        $paid=D('Paid');
        $map['money']=$newPrice*$globalEdu/100;
        $map['user_id']=$p1id;
        $map['creatime']=time();
        $map['type']=13;
        $map['remark']='抢包佣金';
        $map['is_afect']=1;
        $paid->add($map);
        $len++;
        $this->addFenYong();
    }


}

?>