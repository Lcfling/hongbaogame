<?php 


class CommonAction extends BaseAction {
   

    protected $token = '';
    protected $uid='';
    protected $member = array();
    public $redis=null;

    //初始化 验证登陆信息 开启跨域
    protected function _initialize()
    {
        //$this->initCache();
        header("Access-Control-Allow-Origin: *"); // 允许任意域名发起的跨域请求
        header('Access-Control-Allow-Methods:GET, POST');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept ,userid,token');
        $this->checkLogin();
    }
    //lcf $error success faild
    /**
     * Ajax方式返回数据到客户端.
     *
     * @param mixed  $data   要返回的数据
     * @param string $info   提示信息
     * @param bool   $status 返回状态
     * @param string $status ajax返回类型 JSON XML
     */
    public function ajaxReturn($data, $info = '', $status = 1, $type = 'JSON')
    {
        // 保证AJAX返回后也能保存知识
        if (C('LOG_RECORD')) {
            Log::save();
        }
        $result = array();
        $result['status'] = $status;
        $result['info'] = $info;
        $result['data'] = $data;
        if (empty($type)) {
            $type = C('DEFAULT_AJAX_RETURN');
        }
        if (strtoupper($type) == 'JSON') {
            // 返回JSON数据格式到客户端 包含状态信息
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode($result));
        } elseif (strtoupper($type) == 'XML') {
            // 返回xml格式数据
            header('Content-Type:text/xml; charset=utf-8');
            exit(xml_encode($result));
        } elseif (strtoupper($type) == 'EVAL') {
            // 返回可执行的js脚本
            header('Content-Type:text/html; charset=utf-8');
            exit($data);
        } else {
            // TODO 增加其它格式
        }
    }
    //验证用户信息
    public function checkLogin(){
        if(MODULE_NAME=='Hongbao'&&ACTION_NAME=='aotuopenkick'){
            return;
        }
        if(MODULE_NAME=='Hongbao'&&ACTION_NAME=='aotudosend'){
            return;
        }
        if(MODULE_NAME=='Hongbao'&&ACTION_NAME=='maotudosend'){
            return;
        }
        if(MODULE_NAME=='Verify'){
            return;
        }
        if(MODULE_NAME=='Version'){
            return;
        }
        if(MODULE_NAME=='Ucenter'&&ACTION_NAME=='notice'){
            return;
        }
        if(MODULE_NAME=='Ucenter'&&ACTION_NAME=='haibao'){
            return;
        }
        
        if(MODULE_NAME=='Game'&&(ACTION_NAME=='balance'||ACTION_NAME=='createresult'||ACTION_NAME=='isgoon')){
            return;
        }
        if(MODULE_NAME=='Login' || MODULE_NAME=='Index'|| MODULE_NAME=='Test'){
            return;

        }else{
            $user_id=$_SERVER['HTTP_USERID'];
            $token=$_SERVER['HTTP_TOKEN'];
            $userModel=D('Users');
            $userInfo=$userModel->getUserByUid($user_id);
            if($userInfo['token']==''){
                $this->ajaxReturn('','登陆错误失败',2);
            }
            if($userInfo['token']===$token){
                $this->uid=$user_id;
                $this->member=$userInfo;
                return;
            }else{
                $this->ajaxReturn('','登陆错误失败',2);
            }
        }
    }
    //初始化redis链接
    private function initCache(){
        $this->redis=Cac();

    }
	//增加模板结束

    public function verify() {
        import('ORG.Util.Image');
        Image::buildImageVerify(4,2,'png',60,30);
    }


    public function writeLog($text) {

        file_put_contents ( "./syslog.txt", date ( "Y-m-d H:i:s" ) . "  " . $text . "\r\n", FILE_APPEND );

    }
}