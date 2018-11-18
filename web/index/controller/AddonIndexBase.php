<?php

namespace web\index\controller;

/**
 * 插件父控制器
 */
class AddonIndexBase extends \web\index\controller\Base {

    // 初始化
    protected function _initialize() {
        parent::_initialize();
        $this->setTheme($this->theme);
    }
    
    /**
     * 设置主题
     * @param type $theme
     */
    protected function setTheme($theme) {
        $this->theme = $theme;
        $module = $this->request->module();
        // 定位模块目录
        $module = $module ? $module . DS : '';
        $theme = ($theme ? $theme . DS : '');
        \think\App::$modulePath = ADDONS_PATH . ADDON_NAME;
        $this->view_path = ADDONS_PATH . ADDON_NAME . DS . $module . 'view' . DS . $theme;
        if (!IS_AJAX) {
            $templateConfig = config('template');
            $suffix = ltrim($templateConfig['view_suffix'], '.');
            $this->assign('BASE_INDEX', APP_PATH . MODULE_NAME . DS . 'view' . DS . $theme . 'base' . DS . 'index' . '.' . $suffix);
            $this->assign('BASE_POPUP', APP_PATH . MODULE_NAME . DS . 'view' . DS . $theme . 'base' . DS . 'popup' . '.' . $suffix);
            $this->assign('BASE_POPUP_FORM', APP_PATH . MODULE_NAME . DS . 'view' . DS . $theme . 'base' . DS . 'popup_form' . '.' . $suffix);
        }
    }

}