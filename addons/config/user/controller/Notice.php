<?php

namespace addons\config\user\controller;

class Notice extends \web\user\controller\AddonUserBase{
    
    public function index(){
        return $this->fetch();
    }
    
    public function loadList(){
        $filter = '';
        $m = new \addons\config\model\Notice();
        $total = $m->getTotal($filter);
        $rows = $m->getDataList($this->getPageIndex(), $this->getPageSize(), $filter, '', $this->getOrderBy('id desc'));
        return $this->toDataGrid($total, $rows);
    }
    
    public function loadData(){
        $id = $this->_get('id');
        $m = new \addons\config\model\Notice();
        $data = $m->getDetail($id);
        return $data;
    }
    
    public function edit(){
        if (IS_POST) {
            $id = $this->_post('id');
            $data['title'] = $this->_post('title');
            $data['content'] = $this->_post('content');
            $data['id'] = $id;
            $data['update_time'] = NOW_DATETIME;
            $m = new \addons\config\model\Notice();
            try {
                if (empty($id))
                    $ret = $m->add($data);
                else 
                    $ret = $m->save($data);
                return $this->successData();
                
            } catch (\Exception $ex) {
                return $this->failData($ex->getMessage());
            }
        } else {
            $id = $this->_get('id');
            $this->assign('id', $id);
            $this->setLoadDataAction('loadData');
            return $this->fetch();
        }
    }
    
    public function del(){
        $id = intval($this->_get('id'));
        if (!empty($id)) {
            $m = new \addons\config\model\Notice();
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
