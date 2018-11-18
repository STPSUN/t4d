<?php

namespace addons\member\index\controller;

/**
 * 个人首页
 */
class Member extends \web\index\controller\IndexMemberBase {
    
    public function index(){
        return $this->fetch();
    }
    
    
    
}
    