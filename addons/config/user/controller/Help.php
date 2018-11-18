<?php


namespace addons\config\user\controller;

class Help extends \web\user\controller\AddonUserBase{
    
    public function index(){
        return $this->fetch();
        
    }
    
    public function loadList() {
        $keyword = $this->_get('keyword');
        $filter = '1=1';
        $m = new \addons\config\model\Help();
        if ($keyword != null) {
            $filter .= ' and title like \'%' . $keyword . '%\'';
        }
        $total = $m->getTotal($filter);
        $rows = $m->getDataList($this->getPageIndex(), $this->getPageSize(), $filter);
        foreach($rows as &$val){
            $val['content'] = htmlspecialchars_decode(html_entity_decode($val['content']));;
        }
        return $this->toDataGrid($total, $rows);
    }
    
    public function edit() {
        if (IS_POST) {
            $data = $_POST;
            $id = $this->_post("id");
            $data['update_time'] = NOW_DATETIME;
            $m = new \addons\config\model\Help();
            try {
                if (empty($id)) {
                    unset($data['id']);
                    $ret = $m->add($data,false);
                } else {
                    $ret = $m->save($data);
                }
                return $this->successData();
            } catch (\Exception $e) {
                return $this->failData($e->getMessage());
            }
        } else {
            $id = $this->_get('id');
            $this->assign('id', $id);
            $this->setLoadDataAction('loadData');
            return $this->fetch();
        }
    }
    
    public function loadData(){
        $id = $this->_get('id');
        $m = new \addons\config\model\Help();
        $data = $m->getDetail($id);
        $data['content'] = htmlspecialchars_decode(html_entity_decode($data['content']));
        return $data;
    }
    
    /**
     * 删除
     */
    public function del() {
        $id = intval($this->_get('id'));
        if (!empty($id)) {
            $m = new \addons\config\model\Help();
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
