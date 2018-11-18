<?php

namespace addons\config\user\controller;

/**
 * 轮播设置
 */
class Slider extends \web\user\controller\AddonUserBase {
    
    public function index(){
        
       return $this->fetch(); 
    }
    
    public function edit(){
        $m = new \addons\config\model\Slider();
        if(IS_POST){
            $data = $_POST;
            $id = $data['id'];
            try{
                if(empty($id))
                    $m->add($data);
                else
                    $m->save($data);
                return $this->successData();
            } catch (\Exception $ex) {
                return $this->failData($ex->getMessage());
            }   
        }else{
            $this->assign('id',$this->_get('id'));
            $this->assign('order_index', $m->getNewOrderIndex()); //排序
            $this->setLoadDataAction('loadData');
            return $this->fetch(); 
        }
        
    }
    
    public function loadList(){
        $m = new \addons\config\model\Slider();
        $keyword = $this->_get('keyword');
        $filter = '1=1';
        if ($keyword != null) {
            $filter .= ' and title like \'%' . $keyword . '%\'';
        }
        $total = $m->getTotal($filter);
        $rows = $m->getDataList($this->getPageIndex(), $this->getPageSize(), $filter, '', $this->getOrderBy('id asc'));
        return $this->toDataGrid($total, $rows);
    }
    
    public function loadData(){
        $id = $this->_get('id');
        $m = new \addons\config\model\Slider();
        $data = $m->getDetail($id);
        return $data;
    }
    
    
    public function del(){
        $id = $this->_post('id');
        $m = new \addons\config\model\Slider();
        $res = $m->deleteData($id);
        if($res >0){
            return $this->successData();
        }else{
            return $this->failData('删除失败');
        }
    }
    
    
}