<?php

namespace web\index\controller;

/**
 * index base控制器
 */
class Base extends \web\common\controller\BaseController {
    
    protected $addon = '';
    protected $module = '';
    protected $controller = '';
    protected $base_view_path = '';
    protected $inviter_code = '';
    protected $user_id = -1;
    protected $username = '';
    protected $address = '';
    protected $invite_code = '';


    protected function _initialize() {
        $memberData = session('memberData');
        $this->inviter_code = session('inviter_code');
        if (!empty($memberData)) {
            $m = new \addons\member\model\MemberAccountModel();
            $info = $m->getDetail($memberData['user_id']);
            if($info['is_frozen'] == 1 ){
                session('memberData', null);
                return $this->redirect(getURL("keyGame/index",'fomo'));
            }
            $this->user_id = $memberData['user_id'];
            $this->username = $memberData['username'];
            $this->address = $memberData['address'];
            $this->invite_code = $memberData['invite_code'];
        }
        parent::_initialize();
        if (!IS_AJAX || IS_PJAX) {
            $this->base_view_path = $this->view_path;
            $addon = '';
            if (defined('ADDON_NAME'))
                $addon = ADDON_NAME;
            $__c = explode('.', CONTROLLER_NAME);
            if (count($__c) > 1)
                $controller = $__c[1];
            else
                $controller = $__c[0];
            $__controller = \think\Loader::parseName($controller);
            $this->addon = $addon;
            $this->module = MODULE_NAME;
            $this->controller = $__controller;
            $this->assign('_CONTROLLER_NAME', $__controller);
            $this->assign('_ADDON_NAME', $addon);
            $templateConfig = config('template');
            $suffix = ltrim($templateConfig['view_suffix'], '.');
            $this->assign('PUBLIC_HEADER', APP_PATH . MODULE_NAME . DS . 'view' . DS . 'default' . DS . 'public' . DS . 'header' . '.' . $suffix);
//            $this->assign('PUBLIC_FOOTER', APP_PATH . MODULE_NAME . DS . 'view' . DS . 'default' . DS . 'public' . DS . 'footer' . '.' . $suffix);
            $this->assign('username', $this->username);
            $this->assign('address', $this->address);
            $this->assign('user_id', $this->user_id);
            $this->assign('invite_code',$this->invite_code);

        }
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
            $pageIndex = 1;
        return $pageIndex;
    }

    /**
     * 获取每页显示数量
     * @return type
     */
    protected function getPageSize() {
        $pageSize = $this->_get('rows');
        if (empty($pageSize))
            $pageSize = 10;
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
        return json($data);
    }
    
        /**
     * 输出错误JSON信息。
     * @param type $message     
     */
    protected function failJSON($message) {
        $jsonData = array('success' => false, 'message' => $message);        
        $json = json_encode($jsonData, true);
        echo $json;
        exit;
    }

    /**
     * 输出成功JSON信息
     * @param type $data
     */
    protected function successJSON($data = NULL) {
        $json = json_encode(array('success' => true, 'data' => $data), true);
        echo $json;
        exit;
    }
    
}
