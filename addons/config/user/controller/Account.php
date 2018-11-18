<?php

namespace addons\config\user\controller;

/**
 * Description of Account
 *
 * @author shilinqing
 */
class Account extends \web\user\controller\AddonUserBase{
    
    public function index(){
        return $this->fetch();
        
    }
    
    public function loadList() {
        $keyword = $this->_get('keyword');
        $m = new \web\common\model\user\AccountModel();
        $filter ='';
        if ($keyword != null) {
            $filter = ' username like \'%' . $keyword . '%\'';
        }
        $total = $m->getTotal($filter);
        $rows = $m->getList($this->getPageIndex(), $this->getPageSize(), $filter);
        return $this->toDataGrid($total, $rows);
    }
    
    public function edit() {
        if (IS_POST) {
            $data = $_POST;
            $id = $data['id'];
            $data['update_time'] = NOW_DATETIME;
            if($data['password'] != $data['password1']){
                return $this->failData('两次输入的密码不一致');
            }
            $data['password'] = md5($data['password']);
            $m = new \web\common\model\user\AccountModel();
            try {
                if (empty($id)) {
                    $data['create_time'] = NOW_DATETIME;
                    $ret = $m->add($data);
                } else {
                    $ret = $m->save($data);
                }
                return $this->successData();
            } catch (\Exception $e) {
                return $this->failData($e->getMessage());
            }
        } else {
            $this->assign('id', $this->_get('id'));
            $m = new \addons\config\model\Role();
            $role = $m->getDataList();
            $this->assign('role',$role);
            $this->setLoadDataAction('loadData');
            return $this->fetch();
        }
    }
    
    public function loadData(){
        $id = $this->_get('id');
        $m = new \web\common\model\user\AccountModel();
        $data = $m->getDetail($id);
        return $data;
    }
    
    /**
     * 删除
     */
    public function del() {
        $id = intval($this->_get('id'));
        if (!empty($id)) {
            $m = new \web\common\model\user\AccountModel();
            try {
                $res = $m->deleteData($id);
                if ($res > 0) {
                    return $this->successData();
                } else {
                    return $this->failData('删除失败');
                }
            } catch (\Exception $e) {
                return $this->failData($e->getMessage());
            }
        } else {
            return $this->failData('删除失败，参数有误');
        }
    }
    
}
