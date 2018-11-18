<?php

namespace addons\fomo\user\controller;

/**
 * Description of Team
 * 战队设置
 * @author shilinqing
 */
class Team extends \web\user\controller\AddonUserBase{
    
    public function index() {
        return $this->fetch();
    }

    public function loadList() {
        $keyword = $this->_get('keyword');
        $filter = '';
        if ($keyword != null) {
            $filter .= ' name like \'%' . $keyword . '%\'';
        }
        $m = new \addons\fomo\model\Team();
        $total = $m->getTotal($filter);
        $rows = $m->getDataList($this->getPageIndex(), $this->getPageSize(), $filter);
        return $this->toDataGrid($total, $rows);
    }

    public function edit() {
        $m = new \addons\fomo\model\Team();
        if (IS_POST) {
            $data = $_POST;
            $id = $data['id'];
            $data['update_time'] = NOW_DATETIME;
            try {
                if (empty($id))
                    $m->add($data);
                else
                    $m->save($data);
                return $this->successData();
            } catch (\Exception $ex) {
                return $this->failData($ex->getMessage());
            }
        } else {
            $this->assign('id', $this->_get('id'));
            $this->setLoadDataAction('loadData');
            return $this->fetch();
        }
    }
    
    public function loadData() {
        $id = $this->_get('id');
        $m = new \addons\fomo\model\Team();
        $data = $m->getDetail($id);
        return $data;
    }

    /**
     * 逻辑删除
     * @return type
     */
    public function del() {
        $id = $this->_post('id');
        $m = new \addons\fomo\model\Team();
        try {
            $ret = $m->deleteData($id);
            if ($ret > 0) {
                return $this->successData();
            } else {
                $message = '删除失败';
                return $this->failData($message);
            }
        } catch (\Exception $ex) {
            return $this->failData($ex->getMessage());
        }
    }

    
    
}
