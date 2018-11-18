<?php
namespace addons\config\user\controller;

/**
 * Description of Sms
 * 短信账号配置
 * @author shilinqing
 */
class Sms extends \web\user\controller\AddonUserBase {
    
    public function index(){
        return $this->fetch();
    }
    
    public function edit(){
        $m = new \addons\config\model\Sms();
        if(IS_POST){
            $data = $_POST;
            $id = $data['id'];
            $is_allow = $m->getAllowConfig($id);
            if(!empty($is_allow)){
                return $this->failData('已有启用配置,请先禁用已启用短信配置');
            }
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
            $this->setLoadDataAction('loadData');
            return $this->fetch(); 
        }
        
    }
    
    public function loadList(){
        $m = new \addons\config\model\Sms();
        $keyword = $this->_get('keyword');
        $filter = '1=1';
        if ($keyword != null) {
            $filter .= ' and api_id like \'%' . $keyword . '%\'';
        }
        $total = $m->getTotal($filter);
        $rows = $m->getDataList($this->getPageIndex(), $this->getPageSize(), $filter, '', $this->getOrderBy('id asc'));
        return $this->toDataGrid($total, $rows);
    }
    
    public function loadData(){
        $id = $this->_get('id');
        $m = new \addons\config\model\Sms();
        $data = $m->getDetail($id);
        return $data;
    }
    
    
    public function del(){
        $id = $this->_post('id');
        $m = new \addons\config\model\Sms();
        $res = $m->deleteData($id);
        if($res >0){
            return $this->successData();
        }else{
            return $this->failData('删除失败');
        }
    }
    
    
}
