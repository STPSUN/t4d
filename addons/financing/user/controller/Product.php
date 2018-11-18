<?php

namespace addons\financing\user\controller;

/**
 * Description of Product
 *
 * @author shilinqing
 */
class Product extends \web\user\controller\AddonUserBase{

    public function index(){
        return $this->fetch();
    }
    
    public function loadList(){
        $keyword = $this->_get('keyword');
        $m = new \addons\financing\model\Product();
        $filter = '';
        if ($keyword != null) {
            $filter = ' a.title like \'%' . $keyword . '%\'';
        }
        $rows = $m->getList($this->getPageIndex(), $this->getPageSize(),$filter);
        if($filter == ''){
            $total = $m->getTotal();
        }else{
            $total = count($rows);
        }
        return $this->toDataGrid($total, $rows);
    }
    
    public function loadData(){
        $id = $this->_get('id');
        $m = new \addons\financing\model\Product();
        $data = $m->getDetail($id);
        return $data;
    }
    
    public function del(){
        $id = $this->_post('id');
        if (!empty($id)) {
            $m = new \addons\financing\model\Product();
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
    
    public function edit(){
        if(IS_POST){
            $data = $_POST;
            $id = $data['id'];
            $start_rate_date = $data['start_rate_date'];
            $data['end_rate_date'] = date('Y-m-d', strtotime($start_rate_date .'+'. $data['duration'].' days'));
            try{
                $m = new \addons\financing\model\Product();
                $data['update_time'] = NOW_DATETIME;
                
                if(empty($id)){
                    $data['stock'] = $data['total_stock'];
                    $m->add($data); 
                }else{
                   $m->save($data);
                }
                return $this->successData();
            } catch (\Exception $ex) {
                return $this->failData($ex->getMessage());
                
            }
        }else{
            $m = new \addons\config\model\Coins();
            $data = $m->getDataList(-1,-1,'','','id asc');
            $this->assign('id',$this->_get('id'));
            $this->setLoadDataAction('loadData');
            $this->assign('coin_list',$data);
            return $this->fetch();
        }
    }
    
}
