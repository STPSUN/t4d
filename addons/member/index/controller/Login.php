<?php

namespace addons\member\index\controller;

class Login extends \web\index\controller\AddonIndexBase {
    
    //会员登录
    public function index(){
        if(IS_POST){
            try{
                $phone = $this->_post('phone');
                $password = $this->_post('password');
                if (empty($phone)) {
                    return $this->failData('手机号不能为空');
                }
                if (empty($password)) {
                    return $this->failData('密码不能为空');
                }
                
                $m = new \addons\member\model\MemberAccountModel();
                $res = $m->getLoginData($password,$phone,'','id,username,address,invite_code,is_frozen');
                if ($res) {
                    if($res['is_frozen'] == 1){
                        return $this->failData('账号异常');
                    }
                    $memberData['user_id'] = $res['id'];
                    $memberData['username'] = $res['username'];
                    $memberData['address'] = $res['address'];
                    $memberData['invite_code'] = $res['invite_code'];
                    session('memberData', $memberData);
                    return $this->successData();
                } else {
                    return $this->failData('帐号或密码有误');
                }
            } catch (\Exception $ex) {
                return $this->failData($ex->getMessage());
            }
        }else{
            $this->assign('id','');
            $this->setLoadDataAction('');
            return $this->fetch('index');
        }
    }
    
    /**
     * 登出
     */
    function out(){
        $memberData = session('memberData');
        if (empty($memberData))
            return $this->successData();
        session('memberData', null);
        return $this->successData();
    }
    
    /**
     * 忘记密码
     */
    function forgetPass(){
        if(IS_POST){
            $phone = $this->_post('phone');
            $code = $this->_post('code');
            $password = $this->_post('password');
            $type = $this->_post('type');
            if(!empty($password))
                $password = md5($password);
            $m = new \addons\member\model\MemberAccountModel();
            try{
                $verifyM = new \addons\member\model\VericodeModel();
                $_verify = $verifyM->VerifyCode($code,$phone,$type);
                if(!empty($_verify)){
                    $id = $m->updatePassByPhone($phone,$password); //用户id
                    if($id < 0){
                        return $this->failData('重置失败,请重试');
                    }
                    return $this->successData();
                }else{
                    return $this->failData('验证码失效,请重新注册');
                }
            } catch (\Exception $ex) {
                return $this->failData($ex->getMessage());
            }
            
        }else{
            $title = '忘记密码';
            $time = 60*3;
            $this->assign('title',$title);
            $this->assign('time' , $time);
            return $this->fetch();
        }
        
    }
    
    
}