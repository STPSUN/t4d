<?php

namespace addons\member\utils;

/**
 * 短信api
 */
class Sms{
//    短信接口：
    private static $data = [];  //返回消息
    private static $api_id = ''; //用户编码
    private static $api_key = ''; //用户秘钥
    private static $target_url = '';
    
    private static function _init() {
        $m = new \addons\config\model\Sms();
        $data = $m->getAllowConfig();
        if(empty($data))
            return false;
        self::$api_id = $data['api_id'];
        self::$api_key = $data['api_key'];
        self::$target_url = $data['api_url'];
    }
    
    /**
     * 发送验证码
     * @param type $phone
     */
    public static function send($phone){
        self::_init();
        if (empty($phone)) {
            self::$data['success'] = false;
            self::$data['message']='手机号码不能为空';
            return self::$data;
        }
//        if (!preg_match("/^1[34578]{1}\d{9}$/", $phone)) {
//            self::$data['success'] = false;
//            self::$data['message']='请输入正确的手机号';
//            return self::$data;
//        }
        $code = self::random(6,1);//验证码
        $post_data = array(
            'account' => self::$api_id,
            'password'  => self::$api_key,
            'mobile' => $phone,
            'content' => rawurlencode("您的短信验证码为".$code."，请勿向任何人提供此验证码。")
        );
        $post_data = self::makeData($post_data);
        $res = self::Post($post_data, self::$target_url);
        $res = self::xml_to_array($res);
        $res = $res['SubmitResult'];
        if($res['code'] != 2){
            self::$data['message'] = 'errormsg:'.$res['msg'];
            self::$data['success'] = false;
        }else{
            self::$data['code'] = $code;
            self::$data['message'] = "验证码发送成功，请注意查收";
            self::$data['success'] = true;   
        }
        return self::$data;
    }
    
    /**
     * 组成url 参数
     * @param type $post_data
     */
    private static function makeData($post_data){
        $str = '';
        foreach($post_data as $k => $v){
            if(!empty($str))
                $str .= '&';
            $str .= $k .'='.$v;
        }
        return $str;
    }


    /**
     * 转换errorcode
     * @param type $code
     * 0：提交成功
     * 8301：userID为空
     * 8302：uPhone手机号码为空
     * 8303：content发送内容为空
     * 8304：提交IP限制
     * 8201：通道无法匹配
     * 8202：通道异常或暂停
     * 8203：发送内容(content)字数超出限制
     * 8204：用户ID(userID)不存在
     * 8205：发送内容含有非法字符
     * 8206：账号已被锁定
     * 8207：cpKey验证失败
     * 8208：短信条数不足
     * 8209：提交号码个数超出限制
     * 8210：通道异常：网关无法连接
     * 8211：通道异常：欠费、未免白等原因
     * 8212：发送时间段限制
     * 9999：其它原因
     */
    private static function getError($code){
        $message = '';
        switch ($code){
            case '8301':
                $message = 'userID为空';
                break;
            case '8302':
                $message = 'uPhone手机号码为空';
                break;
            default :
                $message = '发送验证码失败';
        }
        return $message;
    }
    
    //随机数
    private static function random($length = 6, $numeric = 0) {
        PHP_VERSION < '4.2.0' && mt_srand((double) microtime() * 1000000);
        if ($numeric) {
            $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
            $max = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }

    /**
     * xml to array
     */
    private static function xml_to_array($xml) {
        $arr=array();
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i++) {
                $subxml = $matches[2][$i];
                $key = $matches[1][$i];
                if (preg_match($reg, $subxml)) {
                    $arr[$key] = self::xml_to_array($subxml);
                } else {
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }

    //curl POST表单
    private static function Post($curlPost, $url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $return_str = curl_exec($curl);
        curl_close($curl);
        return $return_str;
    }
    
    
}