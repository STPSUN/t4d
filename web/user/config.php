<?php

const _MODULE = 'user';
return array(
    'dispatch_success_tmpl' => APP_PATH . _MODULE . '/view/default/dispatch_jump.tpl', // 默认成功跳转对应的模板文件
    'dispatch_error_tmpl' => APP_PATH . _MODULE . '/view/default/dispatch_jump.tpl', // 默认错误跳转对应的模板文件       


    /* 对视图输出的内容进行字符替换 */
    'view_replace_str' => array(
        '__MODULE__' => '/static/' . _MODULE,
        '__STATIC__' => '/static/global',
        '__ADDONS__' => '/static/addons/' . _MODULE,
        '__IMG__' => '/static/' . _MODULE . '/images',
        '__CSS__' => '/static/' . _MODULE . '/css',
        '__JS__' => '/static/' . _MODULE . '/js',
    ),
   
);
