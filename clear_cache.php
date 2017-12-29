<?php

/**
 * Created by PhpStorm.
 * User: will5451
 * 清除缓存
 */
class ClearCache{
    public function __construct(){
    }

    public function p($a){
        echo "<pre>";
    }

    public function del_cache(){
        echo "<pre>";
        $dir = dirname($_SERVER['ORIG_PATH_TRANSLATED']) . "/Application/Runtime/Cache/Home/Web";
        if(is_dir($dir)){
            if($dirs = @opendir($dir)){
                while($file = @readdir($dirs)){
                    $check = is_dir($file);
                    if(!$check){
                        @unlink($dir . $file);
                    }
                }
                @closedir($dir);
                return true;
            }
        }

        return true;
    }

    public function digui($dir){
        if($dirs = @opendir($dir)){
            while($file = @readdir($dirs)){
                $check = is_dir($file);
                if(!$check){
                    @unlink($dir . $file);
                }else{
                    $this->digui($dir . $file);
                }
            }
            @closedir($dir);
            return true;
        }
    }

    public function getdir($path){
        $dir = opendir($path);
        while(($d = readdir($dir)) == true){
            //不让.和..出现在读取出的列表里
            if($d == '.' || $d == '..'){
                continue;
            }
            //判断如果是目录，继续读取
            if(is_dir($path . '/' . $d)){
                $this->getdir($path . '/' . $d);
            }
            @unlink($path . '/' . $d);
            //return $path . '/' . $d;
            return true;
        }

    }
}

$m   = new ClearCache();
$res = $m->del_cache();
if($res === true){
    echo "删除缓存成功ss";
}else{
    echo "删除缓存失败";
}