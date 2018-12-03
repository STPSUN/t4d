<?php

namespace web\index\controller;

/**
 * 前端首页控制器
 */
class Index extends Base {
    
    //定位到fomo游戏界面
    public function index(){
//        return header('Location: http://mobile.luckywinner.vip/app');
        $inviter_code = $this->_get('inv');
        session('inviter_code',$inviter_code);//设置邀请者地址缓存
        $this->assign("title", "DD.POR");
        return redirect(getUrl('key_game/index','','fomo',false));
    }
    
}
