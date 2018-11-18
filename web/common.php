<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用公共文件
/**
 * 写微信日志文件
 * @param $data
 */
function writelog($data)
{
    $path = APP_ROOT . '/log/wechat.txt';
    @file_put_contents($path, serialize($data));
    return true;
}

/**
 * 返回随机名称
 * @param type $length
 * @return type
 */
function getMD5Name($length,$org_name){
    $name = md5(uniqid(microtime(true),true).$org_name);
    $name = substr($name, 0,$length);
    
    return $name;
}

/**
 * 获取当前访问的域名
 * @return string
 */
function getDomain() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $protocol . $_SERVER['HTTP_HOST'];
    return $domain;
}

/**
 * 获取身份证存放目录
 */
function getIDcardPath() {
    $root = $_SERVER['DOCUMENT_ROOT'];
    $root = str_replace('/public', '/idcard/', $root);
    return $root;
}

/**
 * 获取当前时间(Y-m-d H:i:s)
 * @param type $time
 * @return type
 */
function getTime($time = null) {
    if ($time == null)
        $time = time();
    return date('Y-m-d H:i:s', $time);
}

/**
 * 获取距离指定日期**天后(前)的日期
 * @param type $distance 可填正负数
 * @param type $date
 * @return type
 */
function getAnyDay($distance, $date = null) {
    if ($date == null)
        $date = date('Y-m-d');
    return date('Y-m-d', strtotime("$date " . $distance . " day"));
}

function http($url, $param = null, $method = 'POST') {
    try {
        $opts = array(
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );
        /* 根据请求类型设置特定参数 */
        $opts[CURLOPT_URL] = $url;
        if (strtoupper($method) == 'POST' && !is_null($param)) {
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $param;
            if (is_string($param)) { //发送JSON数据
                $opts[CURLOPT_HTTPHEADER] = array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($param),
                );
            }
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {//发生错误            
            $data = '{"success":false, "message":"' . $error . '"}';
        } else {
            $data = '{"success":true, "data":' . $data . '}';
        }
    } catch (\Exception $ex) {
        $data = '{"success":false, "message":"' . $ex->getMessage() . '"}';
    }
    return json_decode($data, true);
}

/**
 * 处理插件钩子
 *
 * @param string $hook
 *        	钩子名称
 * @param mixed $params
 *        	传入参数
 * @return void
 */
function hook($hook, $params = array()) {
    \think\Hook::listen($hook, $params);
}

/**
 * 获取插件勾子类的类名
 * @param strng $name
 * 插件名
 */
function get_hook_class($name) {
    $Addon = ucwords($name);
    $class = "addons\\{$name}\\{$Addon}Hook";
    return $class;
}

/**
 * 获取插件类的类名
 * @param strng $name
 * 插件名
 */
function get_addon_class($name) {
    $Addon = ucwords($name);
    $class = "addons\\{$name}\\{$Addon}Addon";
    return $class;
}

/**
 * 获取微信插件类
 * @param strng $name
 * 插件名
 */
function getWeiXinAddonClass($name) {
    $class = "addons\\{$name}\\WeiXinAddon";
    return $class;
}

/**
 * 获取当前访问的地址
 * @return string
 */
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = $protocol . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
    return $url;
}

/**
 * 检测微信接口返回是否成功。
 * @param type $res
 * @return boolean
 */
function checkIsSuc($res) {
    $result = true;
    if (empty($res))
        return false;
    if (is_string($res)) {
        $res = json_decode($res, true);
    }
    if (is_array($res) && (isset($res['errcode']) && 0 !== (int) $res['errcode'])) {
        $result = false;
    }
    return $result;
}

/**
 * 检测微信接口是否过期
 * @param type $res
 * @return boolean
 */
function checkIsExpires($res) {
    if (!checkIsSuc($res)) {
        if ($res['errcode'] == 40001 || $res['errcode'] == 40014 || $res['errcode'] == 42001)
            return true;
        else
            return false;
    } else
        return false;
}

/**
 * 获取客户端真实IP地址。
 * @return type
 */
function getRealIpAddr() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * 生成md5签名
 * @param type $paramData
 * @param type $key
 * @return type
 */
function makeMD5Sign($paramData, $key = '') {
    ksort($paramData);
    $buff = '';
    foreach ($paramData as $k => $v) {
        if ($v != '' && !is_array($v)) {
            $buff .= $k . '=' . $v . '&';
        }
    }
    if (!empty($key)) {
        $string = $buff . 'key=' . $key;
    } else {
        $buff = trim($buff, '&');
    }
    $string = md5($string);
    $result = strtoupper($string);
    return $result;
}

/**
 * 获取总页数。
 * @param type $recordTotal 记录总数
 * @param type $pageSize 分页大小
 * @return type
 */
function getTotalPage($recordTotal, $pageSize) {
    return intval(floor(($recordTotal - 1.0) / $pageSize)) + 1; //总页数。           
}

/**
 * 获取金额抹零。
 * @param type $moling 抹零方式(1=四舍五入，2=全抹，3=全入)
 * @param type $jingdu 金额精度(1=角，2=元)
 * @param type $amount 金额
 */
function getAmountMoLing($moling, $jingdu, $amount) {
    if ($moling == '1') {//四舍五入
        if ($jingdu == '1') {//角
            $amount = round($amount, 1);
        } else {//元
            $amount = round($amount);
        }
    } else if ($moling == '2') {//全抹
        $arr = explode('.', $amount);
        if (count($arr) == 1)
            return floatval($amount);
        if ($jingdu == '1') {//角                
            if (floatval('0.' . $arr[1]) >= 0.5)
                $amount = intval($arr[0]) + 0.5;
            else
                $amount = intval($arr[0]);
        } else {//元
            $amount = intval($arr[0]);
        }
    } else if ($moling == '3') {//全入
        $arr = explode('.', $amount);
        if (count($arr) == 1)
            return floatval($amount);
        if ($jingdu == '1') {//角                
            if (floatval('0.' . $arr[1]) >= 0.5)
                $amount = intval($arr[0]) + 1;
            else if (floatval('0.' . $arr[1]) >= 0.01)
                $amount = intval($arr[0]) + 0.5;
            else
                $amount = intval($arr[0]);
        } else {//元
            if (floatval('0.' . $arr[1]) >= 0.01)
                $amount = intval($arr[0]) + 1;
            else
                $amount = intval($arr[0]);
        }
    }
    return $amount;
}

/**
 * 获取URL
 * @param type $url
 * @param type $vars
 * @param type $addon
 * @param type $suffix
 * @param type $domain
 * @return type
 */
function getUrl($url = '', $vars = '', $addon = null, $suffix = true, $domain = false) {
    if (false === strpos($url, '/'))
        $url = CONTROLLER_NAME . '/' . $url;
    if ($addon === null && defined('ADDON_NAME'))
        $addon = ADDON_NAME;
    if (false === strpos($url, '@') && defined('MODULE_NAME')) {
        $url = MODULE_NAME . '/' . $url;
        if (!empty($addon))
            $url = '@' . $url . '/addon/' . $addon;
    }else {
        if (!empty($addon))
            $url = $url . '/addon/' . $addon;
    }
    return \think\Url::build($url, $vars, $suffix, $domain);
}

/**
 * 检查是否拥用对应的插件
 * @param type $brand_id
 * @param type $addon_name 插件名称
 * @return type
 */
function hasAddon($brand_id, $addon_name) {
    $m = new \web\common\model\user\BrandAddonsModel();
    return $m->hasAddon($brand_id, $addon_name);
}

/**
 * 图片服务器前缀地址
 */
function getImgBaseUrl() {
    $qiniu_config = config('UPLOAD_FILE_QINIU');
    if (!empty($qiniu_config)) {
        return 'http://' . $qiniu_config['driverConfig']['domain'] . '/';
    }
    return '';
}

/**
 * @param $base     基数
 * @param $n        次数
 * @param $x        增长值
 * @return float|int
 */
function iterativeInc($base, $n, $x) {
    $s = $base * $n + ($n - 1) * $n / 2 * $x;
    return $s;
}

function iterativeDec($base, $n, $x) {
    $s = $base * $n - ($n - 1) * $n / 2 * $x;
    return $s;
}
