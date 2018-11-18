<?php

namespace addons\member\user\controller;

/**
 * Description of Record
 * 交易记录
 * @author shilinqing
 */
class Record extends \web\user\controller\AddonUserBase{
    
    public function index(){
        return $this->fetch();
    }
    
    public function loadList(){
        $keyword = $this->_get('keyword');
        $status = $this->_get('status');
        $filter = '';
        if ($keyword != null) {
            $filter = 'username like \'%' . $keyword . '%\'';
        }
        $m = new \addons\member\model\TradingRecord();
        $total = $m->getTotal($filter);
        $rows = $m->getDataList($this->getPageIndex(), $this->getPageSize(), $filter);
        return $this->toDataGrid($total, $rows);
    }
    
}
