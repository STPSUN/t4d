<?php

namespace web\common\controller;

class BaseController extends Controller {

    protected $sysConfigData = null;
    
    protected function _initialize() {
        $this->sysConfigData = $this->getSysConfig();
        if (!IS_AJAX) {
            $this->assign('SYS_TITLE', $this->sysConfigData['sys_title']);
        }
    }

    /**
     * 获取系统配置信息。
     * @return type
     */
    public function getSysConfig() {
        $m = new \web\common\model\sys\SysOemModel();
        return $m->getSysConfig(1);
    }

    /**
     * 获取平台名称。
     * @return type
     */
    public function getSysTitle() {
        if (empty($this->sysConfigData))
            $this->sysConfigData = $this->getSysConfig();
        return $this->sysConfigData['sys_title'];
    }
    /**
     * 获取User模块APIURL。
     * @return type
     */
    public function getUserApiURL() {
        if (empty($this->sysConfigData))
            $this->sysConfigData = $this->getSysConfig();
        $url = $this->sysConfigData['user_url'];
        if (empty($url)) {
            $this->error('请先配置user_url地址');
            exit;
        }
        $length = strlen($url);
        if (substr($url, $length - 1) == '/')
            $url = substr($url, 0, $length - 1);
        return $url;
    }


    protected function unicodeDecode($str) {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', create_function(
                        '$matches', 'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'
                ), $str);
    }

    /**
     * 获取构造的URL。
     * @param type $action
     * @param type $param
     * @param type $addon
     */
    protected function getURL($action, $param = null, $addon = null) {
        $url = $action;
        if (false === strpos($url, '/')) {
            $url = CONTROLLER_NAME . '/' . $url;
        }
        $_addon = ADDON_NAME;
        if ($addon != null) {
            $_addon = $addon;
        }
        $url = getUrl($url, $param, $_addon);
        return $url;
    }
    
    /**
     * 检查是否拥有对应的插件。
     * @param type $addon_name 插件名称
     * @return type
     */
    protected function hasAddon($addon_name) {
        $m = new \web\common\model\user\AccountModel();
        return $m->hasAddon($addon_name);
    }


}
