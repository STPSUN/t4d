<?php

namespace addons\member\index\controller;

/**
 * 用户注册
 */
class Register extends \web\index\controller\AddonIndexBase {
    
    private $_address = array(); //eth接口返回
    private $ethPass = '';
    
    /**
     * 支持事务
     * @return type
     */
    public function index(){
//        return  $this->failJSON('请选择手机区号');
        if (IS_POST) {
            $data['phone'] = $this->_post('phone');
            $data['verify_code'] = $this->_post('verify_code');
            $inviter_code = $this->_post('inviter_code');
            $password = $this->_post('password');
            $password1 = $this->_post('password1');
            $pay_password = $this->_post('pay_password');
            $data['username'] = $this->_post('username');
            $data['area'] = $this->_post('area');
            if(!$data['area']){
                return $this->failJSON('请选择手机区号');
            }
            if ($password != $password1) {
                return $this->failJSON('两次输入的密码不一致');
            }
            if (!preg_match("/^(?![\d]+$)(?![a-zA-Z]+$)(?![^\da-zA-Z]+$).{6,16}$/", $password)) {
                return $this->failJSON('密码必须含有数字，字母，特殊符号中的两种。');
            }
            if (!preg_match("/^[0-9]{6}$/", $pay_password)) {
                return $this->failJSON('请输入6位数字交易密码');
            }
            if (strlen($password) < 8) {
                return $this->failJSON('密码长度不能小于8');
            }
            if(!$inviter_code)
            {
                return $this->failJSON('请输入推荐码');
            }
            $data['password'] = md5($password);
            $data['pay_password'] = md5($pay_password);
            $m = new \addons\member\model\MemberAccountModel();
            if (preg_match('/[\x7f-\xff]/', $data['username'])) {
                return $this->failJSON('用户名不支持中文');
            }
            $count = $m->hasRegsterUsername($data['username']);
            if ($count > 0) {
                return $this->failJSON('此用户名已被注册,请直接登录或尝试找回密码');
            }
            $counte_code = $m->hasRegsterPhone($data['phone']);

            if ($counte_code > 0) {
                return $this->failJSON('此手机号已被注册,请直接登录或尝试找回密码');
            }
            $m->startTrans();
            try {
                $verifyM = new \addons\member\model\VericodeModel();
                $_verify = $verifyM->VerifyCode($data['verify_code'], $data['phone']);
                if (!empty($_verify)) {
                    if (!empty($inviter_code)) {
                        //获取邀请者id
                        $invite_user_id = $m->getUserByInviteCode($inviter_code);
                        if (!empty($invite_user_id)) {
                            $data['pid'] = $invite_user_id; //邀请者id
                        } else {
                            return $this->failJSON('邀请人不存在');
                        }
                    }
                    $data['register_time'] = NOW_DATETIME;
                    $res = $this->getEthAddr($data['phone']);
                    if ($res) {
                        $data['address'] = $this->_address; //eth地址
                        $data['eth_pass'] = $this->ethPass;
                        $data['invite_code'] = $this->getInviteCode($data['phone'], $data['verify_code']);
                        $user_id = $m->add($data); //用户id
                        if($user_id > 0){
                            //注册赠送
                            $this->_registerSend($user_id);
                        }
                        $m->commit();
                        return $this->successJSON('注册成功');
                    }
                } else {
                    $m->rollback();
                    return $this->failJSON('验证码失效,请重新注册');
                }
            } catch (\Exception $ex) {
                return $this->failJSON($ex->getMessage());
            }
        } else{
            $this->assign('time', 60*3);//验证码过期时间 秒
            $this->assign('id','');
            $this->assign('title','');
            $invite_code = $this->_get("inv");
            $this->assign('invite_code',$this->_get("inv"));
            $this->setLoadDataAction('DD.POR');
            return $this->fetch('reg');
        }
    }
    
    /**
     * 注册赠送
     */
    private function _registerSend($user_id){
        try{
            $confM = new \web\common\model\sys\SysParameterModel();
            $is_register_send = $confM->getValByName('is_register_send');
            if($is_register_send != 1){
                return true;
            }
            $register_send = $confM->getValByName('register_send');
            if(empty($register_send) || $register_send < 0){
                return true;
            }
            $balanceM = new \addons\member\model\Balance();
            $coinM = new \addons\config\model\Coins();
            $eth = $coinM->getCoinByName();
            $coin_id = $eth['id'];
            $balance = $balanceM->updateBalance($user_id, $register_send, $coin_id, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }

    }
    
    private function getEthAddr($name){
        $eth_pass = 'token'.$name.rand(0000,9999);
        $this->ethPass = $eth_pass;
        $res = $this->jsonrpc('personal_newAccount', [$eth_pass]);
        return $res;
    }

    private function jsonrpc($method, $params) {
        $m = new \web\common\model\sys\SysParameterModel();
        $port = $m->getValByName('port');
        $url = "http://127.0.0.1:".$port;
        $request = array('method' => $method, 'params' => $params, 'id' => 1);
        $request = json_encode($request);
        $opts = array('http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/json',
                'content' => $request
        ));
        $context = stream_context_create($opts);
        if ($result = file_get_contents($url, false, $context)) {
            $data = json_decode($result, true);
            if(!empty($data) && $data['result'] ){
                $this->_address = $data['result'];
                return true;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }
  


    /**
     * 验证手机是否已经注册
     */
    public function hasReg($phone){
       $m = new \addons\member\model\MemberAccountModel();
       $count = $m->hasRegsterPhone($phone);
       return $this->successJSON($count);
    }
    
    /**
     * 短信验证
     */
    public function sms(){
        $phone = $this->_post('phone');
        $area = $this->_post('area');
        $time = time() + 300;
        $type = $this->_post('type');
        if(empty($type))
            $type = 1;//注册验证码
        $m = new \addons\member\model\VericodeModel();
        $unpass_code = $m->hasUnpassCode($phone,$type);
        if(!empty($unpass_code)){
            return $this->failData('验证码未过期,请输入之前收到的验证码');
        }
        if(!$area){
            return $this->failData('请选择国家区号');
        }
        try{
            $sendPhone = "{$area} $phone";
            //发送验证码
            $res = \addons\member\utils\Sms::send($phone);
//            $res['success'] = true;
//            $res['message'] = '短信发送成功';
//            $res['code'] = '1111';
            if(!empty($res['code'])){
                //保存验证码
                $pass_time = date('Y-m-d H:i:s',$time);
                $data['phone'] = $phone;
                $data['code'] = $res['code'];
                $data['type'] = $type;
                $data['pass_time'] = $pass_time; //过期时间
                $m->add($data);
                unset($res['code']);
            }
            return $res;
        } catch (\Exception $ex) {
            return $this->failData($ex->getMessage());
        }
    }
    
    /**
     * 生成邀请码
     * @param type $phone
     */
    private function getInviteCode($phone,$code){
        $str = md5($phone.microtime().$code);
        $str = substr($str, 0,5);
        return $str;
    }
    
}
    