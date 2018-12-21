<?php

namespace web\user\controller;

/**
 * 用户登录控制器
 */
class Login extends \web\common\controller\BaseController {
    
    public function index(){
        if (IS_POST) {
            /* 检测验证码 */
            $code = $this->_post('code');
//            if (!captcha_check($code)) {
//                return $this->failData('验证码输入错误！');
//            }
            $username = $this->_post('username');
            $password = $this->_post('password');
            if (empty($username)) {
                return $this->failData('账号不能为空');
            }
            if (empty($password)) {
                return $this->failData('密码不能为空');
            }
            $m = new \web\common\model\user\AccountModel();
            $res = $m->getLoginData($username, $password);
            if ($res) {
                if ($res['logic_delete'] == 1)
                    return $this->failData('账号已删除，您的帐号无法登录');
                
                $loginData = array('uid' => $res['id']);
                if($res['is_admin'] == 0){
                    $roleM = new \addons\config\model\Role();
                    $addons_ids = $roleM->getDataByID($res['role_id'],'addon_ids');
                    $menuM = new \web\common\model\sys\UserNavMenuModel();
                    $loginData['promission'] = $menuM->getControllerByIDS($addons_ids);
                    
                }
                $loginData['username'] = $res['username'];
                $loginData['is_admin'] = $res['is_admin'];
                $loginData['role_id'] = $res['role_id'];
                $url = url('index/index');
                session('loginData', $loginData);
                return $this->successData($url);
            } else {
                return $this->failData('帐号或密码有误');
            }
        } else {
            return $this->fetch();
        }
    }
    
    /**
     * 退出
     */
    public function logout(){
        $loginData = session('loginData');
        if (empty($loginData))
            return $this->successData();
        session('loginData', null);
        return $this->successData();
    }
}