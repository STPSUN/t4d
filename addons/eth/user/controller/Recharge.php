<?php
/**
 * Created by PhpStorm.
 * User: stp
 * Date: 2018/12/17
 * Time: 10:58
 */

namespace addons\eth\user\controller;


class Recharge extends \web\user\controller\AddonUserBase
{

    public function index(){
        $status = $this->_get('status');
        if($status == ''){
            $status = 0; //未确认
        }
        $this->assign('status',$status);
        return $this->fetch();
    }

    public function loadList(){
        $keyword = $this->_get('keyword');
        $status = $this->_get('status');
        $type = $this->_get('type');
//        $status = 3;
//        $filter = 'o.status='.$status;
        $filter = ' o.type=' . 1;
//        if($type != ''){
//            $filter .= ' and o.type='.$type;
//        }
        if ($keyword != null) {
//            $filter .= ' and b.username like \'%' . $keyword . '%\'';
            $filter .= ' and (m.username like \'%' . $keyword . '%\' or m.phone like \'%' . $keyword . '%\')';
        }
        $m = new \addons\eth\model\EthTradingOrder();
//        $m = new \addons\member\model\MemberAccountModel();
        $total = $m->getTotal2($filter);
        $rows = $m->getList2($this->getPageIndex(), $this->getPageSize(), $filter);

        $count_total = $m->getCountTotal2($filter);
//        return $this->toTotalDataGrid($total, $rows,$count_total);
//        print_r($rows);exit();
//        $count_total = $m->getCountTotal($filter);
        return $this->toTotalDataGrid($total, $rows,$count_total);
    }
}