<?php
namespace Home\Controller;

class PlayerController extends HomeController{
    public function index(){
        $id   = D("Movie")->getmid(I('pid'));
        $info = D("Movie")->detail($id);
        if(!$info){
            $error = D("Movie")->getError();
            $this->error(empty($error) ? '未知错误！' : $error);
        }
        $info = D("Tag")->movieChange($info, 'movie', 2);
        $tpl  = D("Category")->getTpl($info['cid'], 'template_play');
        if(!$tpl){
            $error = D("Category")->getError();
            $this->error(empty($error) ? '未知错误！' : $error);
        }
        $this->assign('pos', 1);
        $this->assign($info);
        $payer_vip = D("Movie")->getPlayerVip(I('pid'));
        if($payer_vip){
            if(UID){
                if($this->user['vip_time'] < time()){
                    $this->error('VIP已过期，请续费', U('Home/Other/index/tpl/vip'), 5);
                }
            }else{
                $this->error('还未登录', U('User/Public/login'), 5);
            }
        }
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display("." . $this->tplpath . "/" . $tpl);
    }

    public function player(){
        $pid  = I('pid');
        $id   = D("Movie")->getmid($pid);
        $n    = I('n');
        $info = D("Movie")->detail($id);

        //$re= D('Movie')->getPlayer($id, $pid, $n);
        //p($re['player_code']);
        //进行过滤

        if(C('USER_ALLOW_PLAY') == 1 && C('USER_PLAY_COUNT') > 1 && UID){
            $map['user_id']     = UID;
            $map['type']        = 2;
            $map['action_id']   = M('Action')->getFieldByName('users_play', 'id');
            $map['create_time'] = array('gt', strtotime("-24 hours"));
            $count              = M('ActionLog')->where($map)->count();
            if($count <= C('USER_PLAY_COUNT')){
                $this->player_recom = 1;
            }
        }
        $record_on   = M('category')->where(array('id' => $info['category']))->getField('record');
        $info['url'] = url_change("movie/index", array("id" => $id, "name" => 'movie'));
        $this->assign("movie", $info);
        $this->assign("record_on", $record_on);
        $this->assign("player", D('Movie')->getPlayer($id, $pid, $n));

        $this->display("Public/Player/player.html");
    }

    public function getPlayers(){
        $moviess = file_get_contents("http://tv.sohu.com/20150912/n420974116.shtml");
        //preg_match('/(.*?)\.html/', $moviess, $result);
        preg_match('/<meta property="og:video" content="(.*)"\/>/', $moviess, $result);
        $moviess = htmlspecialchars($moviess);
        p($result);
        p($_REQUEST);
        if(isset($_GET['url']) && $_GET['url'] != ''){
            p($_GET['url']);
        }
    }

    //////////////////爬去优酷视频爬去优酷视频爬去优酷视频////
    ////////////爬去优酷视频/////////////
    public function fetch_youku_flv(){
        $url = "http://tv.sohu.com/20150912/n420974116.shtml";
        preg_match("id.*?(.*?).html",$url,$out);
        $id=$out[1];
        p($out);
        $content=$this ->get_curl_contents('http://v.youku.com/player/getPlayList/VideoIDS/'.$id);
        $data=json_decode($content);
        foreach($data->data[0]->streamfileids AS $k=>$v){
            $sid=$this -> getSid();
            $fileid=$this ->getfileid($v,$data->data[0]->seed);
            $one=($data->data[0]->segs->$k);
            if($k == 'flv' || $k == 'mp4') return "http://f.youku.com/player/getFlvPath/sid/{$sid}_00/st/{$k}/fileid/{$fileid}?K={$one[0]->k}";
            continue;
        }
    }

    public function get_curl_contents($url, $second = 5){
        if(!function_exists('curl_init')){
            die('php.ini未开启php_curl.dll');
        }
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        $UserAgent = $_SERVER['HTTP_USER_AGENT'];
        curl_setopt($c, CURLOPT_USERAGENT, $UserAgent);
        curl_setopt($c, CURLOPT_HEADER, 0);
        curl_setopt($c, CURLOPT_TIMEOUT, $second);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $cnt = curl_exec($c);
        $cnt = mb_check_encoding($cnt, 'utf-8') ? iconv('gbk', 'utf-8//IGNORE', $cnt) : $cnt; //字符编码转换
        curl_close($c);
        return $cnt;
    }

    public function getSid() {
        $sid = time().(rand(0,9000)+10000);
        return $sid;
    }
    public function getkey($key1,$key2){
        $a = hexdec($key1);
        $b = $a ^ 0xA55AA5A5;
        $b = dechex($b);
        return $key2.$b;
    }
    public function getfileid($fileId,$seed) {
        $mixed = $this -> getMixString($seed);
        $ids = explode("*",$fileId);
        unset($ids[count($ids)-1]);
        $realId = "";
        for ($i=0;$i < count($ids);++$i) {
            $idx = $ids[$i];
            $realId .= substr($mixed,$idx,1);
        }
        return $realId;
    }
    public function getMixString($seed) {
        $mixed = "";
        $source = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ/:._-1234567890";
        $len = strlen($source);
        for($i=0;$i< $len;++$i){
            $seed = ($seed * 211 + 30031) % 65536;
            $index = ($seed / 65536 * strlen($source));
            $c = substr($source,$index,1);
            $mixed .= $c;
            $source = str_replace($c, "",$source);
        }
        return $mixed;
    }
    /////////////爬去优酷视频爬去优酷视频///////////////
    /////////////////////////
    public function down(){
        $pid       = I('pid');
        $n         = I('n');
        $payer_vip = D("Movie")->getPlayerVip(I('pid'));
        if($payer_vip){
            if(UID){
                if($this->user['vip_time'] < time()){
                    $this->error('VIP已过期，请续费', U('Home/Other/index/tpl/vip'), 5);
                }
            }else{
                $this->error('还未登录', 'User/Public/login', 5);
            }
        }
        $downUrl = D('Movie')->getPlayerUrl($pid, $n);
        $this->assign("downurl", $downUrl);
        $this->display("Public/Player/down.html");
    }

    public function vip(){
        if(UID){
            if($this->user['vip_time'] >= time()){
                $info = array('code' => 1, 'msg' => '可以观看');
            }else{
                $info = array('code' => 3, 'msg' => 'VIP已过期，请续费', 'url' => U('Home/Other/index/tpl/vip'));
            }
        }else{
            $info = array('code' => 2, 'msg' => '还未登录', 'url' => U('User/Public/login'));
        }
        $this->ajaxReturn($info);
    }
}