<?php

namespace addons\config\user\controller;

/**
 * Description of Role
 * 账号角色
 * @author shilinqing
 */
class Role extends \web\user\controller\AddonUserBase{
    
    public function index(){
        return $this->fetch('role_list');
    }
    
    public function loadList() {
        $m = new \addons\config\model\Role();
        $rows = $m->getDataList();
        return $rows;
    }
    
    public function role() {
        if (IS_POST) {
            $m = new \addons\config\model\Role();
            $data = $_POST;
            $id = $data['id'];
            $data['update_time'] = NOW_DATETIME;
            if (empty($id)) {
                $ret = $m->add($data);
            } else {
                $ret = $m->save($data);
            }
            if ($ret !== false)
                return $this->successData();
            else
                return $this->failData($m->getError());
        } else {
            $this->setLoadDataAction('loadData');
            $this->assign('id', $this->_get('id'));
            return $this->fetch();
        }
    }
    
    public function loadData() {
        $id = $this->_get('id');
        $m = new \addons\config\model\Role();
        $data = $m->getDetail($id);
        return $data;
    }
    
    /**
     * 获取操作权限树
     * @return type
     */
    public function getPopedomForTree() {
        $m = new \web\common\model\sys\UserNavMenuModel();
        return $m->getPopedomTree();
    }
    
}
