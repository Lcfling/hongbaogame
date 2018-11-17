<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/29
 * Time: 15:47
 */

class VersionAction extends CommonAction{

    //todo lcfling
    public function index(){

        $version=array(
            'title'=>'版本更新',
            'DownloadUrl'=>"http://test-1251233192.coscd.myqcloud.com/1_1.apk",
            'Content'=>'修复什么事没什么'
        );
        die(json_encode($version));
    }

}