<?php

namespace addons\member\user\controller;

/**
 * 用户等级
 */
class LevelConfig extends \web\user\controller\AddonUserBase {

    public function index() {
        return $this->fetch();
    }

    public function edit() {
        if (IS_POST) {
            $m = new \addons\member\model\LevelConfig();
            $data = $_POST;
            $id = $data['id'];
            try {
                if (empty($id)) {
                    $m->add($data);
                } else {
                    $m->save($data);
                }
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
        $m = new \addons\member\model\LevelConfig();
        $data = $m->getDetail($id);
        return $data;
    }

    public function loadList() {
        $keyword = $this->_get('keyword');
        $filter = '1=1';
        $m = new \addons\member\model\LevelConfig();
        if ($keyword != null) {
            $filter .= ' and level_name like \'%' . $keyword . '%\'';
        }
        $total = $m->getTotal($filter);
        $rows = $m->getDataList($this->getPageIndex(), $this->getPageSize(), $filter);
        return $this->toDataGrid($total, $rows);
    }

    public function del() {
        $id = $this->_post('id');
        $m = new \addons\member\model\LevelConfig();
        $where['id'] = $id;
        $where['is_default'] = 0;
        $res = $m->where($where)->delete();
        if ($res > 0) {
            return $this->successData();
        } else {
            return $this->failData('删除失败!');
        }
    }

}
