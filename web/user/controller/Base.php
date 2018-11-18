<?php

namespace web\user\controller;

/**
 * User公共控制器
 */
class Base extends \web\common\controller\BaseController {
    
    
    /**
     * 是否管理员(1=是)
     * @var type 
     */
    protected $is_admin = 0;

    /**
     * 登录账号ID
     * @var type 
     */
    protected $login_user_id = 0;

    /**
     * 登录账号
     * @var type 
     */
    protected $login_user_name = '';

    /**
     * 可操作的权限IDS。
     * @var type 
     */
    protected $addon = '';
    protected $module = '';
    protected $controller = '';
    protected $base_view_path = '';

    /**
     * CDN图片域名;
     * @var type 
     */
    protected $img_base_url = '';

    protected function _initialize() {
        $loginData = session('loginData');
        $uid = '';
        $username = '';
        if (!empty($loginData)) {
            $uid = $loginData['uid'];
            $username = $loginData['username'];
        }
        if (empty($uid) || empty($username)) {
            //登录超时跳转登录
            $url = "/user/login";
            $this->redirect($url);
            exit;
        }
        parent::_initialize();
        $this->is_admin = $loginData['is_admin'];
        $this->login_user_id = $uid;
        $this->login_user_name = $username;
        if (!IS_AJAX || IS_PJAX) {
            $this->base_view_path = $this->view_path;
            $addon = '';
            if (defined('ADDON_NAME')) {
                $addon = ADDON_NAME;
            }
            $__c = explode('.', CONTROLLER_NAME);
            if (count($__c) > 1)
                $controller = $__c[1];
            else
                $controller = $__c[0];
            $__controller = \think\Loader::parseName($controller); //controller
            if($this->is_admin != 1){
                if ($addon != null & $addon != '') {
                    $plugin = $addon.'/'.$__controller;
                    if (strstr($loginData['promission'], $plugin) === false) {
                        $this->tips('您无此插件使用权限');
                    }
                }
            }
            $menu = new \web\common\model\sys\UserNavMenuModel();
            $this->assign('is_admin', $this->is_admin);
            $this->addon = $addon;
            $this->module = MODULE_NAME;
            $this->controller = $__controller;
            $this->assign('_CONTROLLER_NAME', $__controller);
            $this->assign('_ADDON_NAME', $addon);
            $templateConfig = config('template');
            $suffix = ltrim($templateConfig['view_suffix'], '.');
            //注册模板模块
            $this->assign('PUBLIC_HEADER', APP_PATH . MODULE_NAME . DS . 'view' . DS . 'default' . DS . 'public' . DS . 'header' . '.' . $suffix);
            
            $this->setLoadDataAction('');
            $menuData = $menu->getMenuData($addon, $__controller);
            $addon_id = 0;
            $menu_nav = '';
            if (!empty($menuData)) {
                $addon_id = $menuData['id'];
                $menu_nav = $menuData['title'];
            }
            $this->assign('addon_id', $addon_id);
            $page = 0;
            if ($this->_get('page') != '')
                $page = $this->_get('page');
            $this->assign('page', $page);
            $filter = '';
            if ($this->_get('filter') != '')
                $filter = $this->_get('filter');
            $this->assign('page_nav', $menu_nav);
            $this->assign('filter', $filter);
            $this->assign('is_admin', $this->is_admin);
            $this->assign('login_user_name', $this->login_user_name);
        }
    }
    
   

    /**
     * 提示信息。
     * @param string $msg
     * @param type $url
     * @param type $data
     * @param type $wait
     * @param array $header
     * @throws HttpResponseException
     */
    protected function tips($msg = '', $url = null, $data = '', $wait = 3, array $header = []) {
        $code = 0;
        if (is_numeric($msg)) {
            $code = $msg;
            $msg = '';
        }
        $result = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'url' => $url
        ];
        $type = $this->getResponseType();
        $path = $this->base_view_path . 'tips.html';
        $this->assign('result', $result);
        $content = $this->fetch($path, $result);
        $response = \think\Response::create($content, $type)->header($header);
        throw new \think\exception\HttpResponseException($response);
    }

    protected function fetch($template = '', $vars = [], $replace = [], $config = []) {
        $conent = parent::fetch($template, $vars, $replace, $config);
        if (IS_PJAX) {
            $html = '<addon>' . $this->addon . '</addon>';
            $html .= '<module>' . $this->module . '</module>';
            $html .= '<controller>' . $this->controller . '</controller>';
            $conent = $html . $conent;
        }
        return $conent;
    }

    /**
     * 加载表单的方法名称
     * @param type $value
     */
    protected function setLoadDataAction($value) {
        $this->assign('loadDataAction', $value);
    }

    /**
     * 获取当前页码
     * @return type
     */
    protected function getPageIndex() {
        $pageIndex = $this->_get('page');
        if (empty($pageIndex))
            $pageIndex = -1;
        return $pageIndex;
    }

    /**
     * 获取每页显示数量
     * @return type
     */
    protected function getPageSize() {
        $pageSize = $this->_get('rows');
        if (empty($pageSize))
            $pageSize = -1;
        if ($pageSize > 50)
            $pageSize = 50;
        return $pageSize;
    }

    /**
     * 获取排序信息
     * @param type $orderBy 默认排序信息
     * @return string
     */
    protected function getOrderBy($orderBy) {
        $sort = $this->_get('sort');
        $order = $this->_get('order');
        if (!empty($sort) && !empty($order)) {
            $sortArr = explode(',', $sort);
            $orderArr = explode(',', $order);
            $i = 0;
            $s = '';
            foreach ($sortArr as $field) {
                if ($i > 0)
                    $s .= ',';
                $s .= $field . ' ' . $orderArr[$i];
                $i++;
            }
            $orderBy = $s;
        }
        return $orderBy;
    }

  
    /**
     * 返回DataGrid数据
     * @param type $total
     * @param type $rows     
     */
    protected function toDataGrid($total, $rows) {
        if (empty($rows))
            $rows = array();
        $data = array('total' => $total, 'rows' => $rows);
        return $data;
    }
    
    /**
     * 返回DataGrid数据
     * @param type $total
     * @param type $rows     
     */
    protected function toTotalDataGrid($total, $rows,$count_total) {
        if (empty($rows))
            $rows = array();
        $data = array('total' => $total, 'rows' => $rows,'count_total' => $count_total);
        return $data;
    }

}
