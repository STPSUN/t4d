<?php

namespace web\addons\controller;
/**
 * 插件类
 *
 */
abstract class Addon {

    public $info = array();

    public function __construct() {
        
    }
    

    //必须实现安装插件方法
    abstract public function install();

    //必须实现卸载插件方法
    abstract public function uninstall();
}
