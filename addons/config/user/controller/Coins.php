<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace addons\config\user\controller;

/**
 * Description of Coins
 *
 * @author shilinqing
 */
class Coins extends \web\user\controller\AddonUserBase{
    
    public function index(){
        return $this->fetch();
    }
    
    public function loadList(){
        $keyword = $this->_get('keyword');
        $filter = '1=1';
        $m = new \addons\config\model\Coins();
        if ($keyword != null) {
            $filter .= ' and coin_name like \'%' . $keyword . '%\'';
        }
        $total = $m->getTotal($filter);
        $rows = $m->getDataList($this->getPageIndex(), $this->getPageSize(), $filter, '', $this->getOrderBy('id asc'));
        return $this->toDataGrid($total, $rows);
    }
    
    public function loadData(){
        $id = $this->_get('id');
        $m = new \addons\config\model\Coins();
        $data = $m->getDetail($id);
        return $data;
    }
    
    public function edit(){
        if(IS_POST){
            $m = new \addons\config\model\Coins();
            $data = $_POST;
            $id = $data['id'];
            try{
                $data['update_time'] = NOW_DATETIME;
                if(empty($id)){
                   $m->add($data); 
                }else{
                   $m->save($data);
                }
                return $this->successData();
            } catch (\Exception $ex) {
                return $this->failData($ex->getMessage());
                
            }
        }else{
            $this->assign('id', $this->_get('id'));
            $this->setLoadDataAction('loadData');
            return $this->fetch();
        }
    }
    
    public function del(){
        $id = $this->_post('id');
        $m = new \addons\config\model\Coins();
        $where['id'] = $id;
        $res = $m->where($where)->delete();
        if($res > 0){
            return $this->successData();
        }else{
            return $this->failData('删除失败!');
        }
    }
}
