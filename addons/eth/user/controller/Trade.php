<?php

namespace addons\eth\user\controller;

use think\Loader;

class Trade extends \web\user\controller\AddonUserBase{


    public function index(){
        $status = $this->_get('status');
        if($status == ''){
            $status = 0; //未确认
        }
        $this->assign('status',$status);
        return $this->fetch();
    }

    public function loadList2(){
        $keyword = $this->_get('keyword');
        $status = $this->_get('status');
        $type = $this->_get('type');
        $filter = 'status='.$status;
        if($type != ''){
            $filter .= ' and type='.$type;
        }
        if ($keyword != null) {
            $filter .= ' and m.username like \'%' . $keyword . '%\'';
        }
        $m = new \addons\eth\model\EthTradingOrder();

        $total = $m->getTotal($filter);
        $rows = $m->getList($this->getPageIndex(), $this->getPageSize(), $filter);

        $sysM = new \web\common\model\sys\SysParameterModel();
        $eth_rate = $sysM->getValByName('eth_rate');
        foreach ($rows as &$v)
        {
            $v['eth_amount'] = bcdiv($v['amount'],$eth_rate,8);
        }

        $count_total = $m->getCountTotal($filter);
        return $this->toTotalDataGrid($total, $rows,$count_total);
    }

    public function loadList(){
        $keyword = $this->_get('keyword');
        $status = $this->_get('status');
        $type = $this->_get('type');
//        $status = 3;
        $filter = 'o.status='.$status;
        if($type != ''){
            $filter .= ' and o.type='.$type;
        }
        if ($keyword != null) {
//            $filter .= ' and b.username like \'%' . $keyword . '%\'';
            $filter .= ' and m.username like \'%' . $keyword . '%\'';
        }
        $m = new \addons\eth\model\EthTradingOrder();
//        $m = new \addons\member\model\MemberAccountModel();
        $total = $m->getTotal2($filter);
        $rows = $m->getList2($this->getPageIndex(), $this->getPageSize(), $filter);

        $marketM = new \addons\financing\model\Market();
        $cny = $marketM->getDetailByCoinName('ETH','cny');
        $eth_rate = bcdiv($cny,7,4);
//        $sysM = new \web\common\model\sys\SysParameterModel();
//        $eth_rate = $sysM->getValByName('eth_rate');
        foreach ($rows as &$v)
        {
            $v['eth_amount'] = bcdiv($v['amount'],$eth_rate,8);
        }

        $count_total = $m->getCountTotal2($filter);
        return $this->toTotalDataGrid($total, $rows,$count_total);
//        print_r($rows);exit();
//        $count_total = $m->getCountTotal($filter);
//        return $this->toTotalDataGrid($total, $rows,23);
    }

    /**
     * 审核
     */
    public function appr(){
        if(IS_POST){
            $id = $this->_post('id');
            try{
                $tradeM = new \addons\eth\model\EthTradingOrder();
                $data = $tradeM->getUncheckDataByID($id);
                if(!$data){
                    return $this->failData("订单数据异常");
                }
                //初始化参数 eth api
                $msg = '';
//                $ethApi = $this->_initArguments($msg);
//                if($ethApi == false){
//                    return $this->failData($msg);
//                }
                $id = $data['id'];
                $to = $data['to_address'];
                $contract_address = $data['contract_address'];
                $byte = $data['byte'];
//                if($data['coin_id'] !=1  && empty($contract_address))
//                    return $this->failData ('未设置合约地址');
                $frex_to = strtolower(substr($to,0,2));
                if(($frex_to !== "0x" || strlen($to) !== 42)){
//                    //异常订单处理 更新订单状态非未通过
                    $tradeM->updateStatus($id,5,NOW_DATETIME,'','转出地址格式错误');
                }
//                $ret = $ethApi->send($to, $data['amount'], $contract_address, $byte);
//                if($ret['success']){

                    $tradeM->startTrans();
                    //更新订单txhash
                    $has_update = $tradeM->updateStatus($id, 1, NOW_DATETIME, '', '审核成功，等待打款');
                    if(empty($has_update)){
                        return $this->failData('更新订单失败');
                    }
                    $tradeM->commit();
                    //确认订单完成时扣除冻结金额
                    return $this->successData('id:'.$id.' 转出成功。');
//                }else{
//                            //异常订单
//                    $tradeM->updateStatus($id, 5, NOW_DATETIME, '', $ret['message']);
//                    return $this->failData($ret['message']);
//                }
            } catch (\Exception $ex) {
                return $this->failData($ex->getMessage());
            }
        }
    }


    /**
     * 反审核-不通过
     */
    public function cancel_appr(){
        if(IS_POST){
            $id = $this->_post('id');
            try{
                $tradeM = new \addons\eth\model\EthTradingOrder();
                $data = $tradeM->getDetail($id);
                if(!empty($data)){
                    $user_id = $data['user_id'];
                    $amount = $data['amount'];
                    $coin_id = $data['coin_id'];
                    $tax = $data['tax'];
                    $tradeM->startTrans();
                    $ret = $tradeM->updateStatus($id, 2, NOW_DATETIME, '', '转出审核不通过');
                    if($ret > 0){
                        if($data['tax'] > 0) $amount += $data['tax'];
                        //返还金额
                        $balanceM = new \addons\member\model\Balance();
                        $balance = $balanceM->updateBalance($user_id, $amount, $coin_id, true);
                        if(!$balance){
                            $tradeM->rollback();
                            return $this->failData('退单失败');
                        }
                        $type = 9;
                        $before_amount = $balance['before_amount'];
                        $after_amount = $balance['amount'];
                        $change_type = 1; //增加
                        $remark = '转出可用余额';

                        $recordM = new \addons\member\model\TradingRecord();
                        $r_id = $recordM->addRecord($user_id, $coin_id, $amount, $before_amount, $after_amount, $type, $change_type, $user_id, '', '', $remark);
                        if(!$r_id ){
                            $tradeM->rollback();
                            return $this->failJSON('提交申请失败');
                        }
                        $tradeM->commit();
                        return $this->successData('退还金额成功');
                    }else{
                        $tradeM->rollback();
                        return $this->failData('更新订单状态失败');
                    }
                }
            } catch (\Exception $ex) {
                return $this->failData($ex->getMessage());
            }
        }
    }

    /**
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function excel()
    {
        $keyword = $this->_get('keyword');
        $status = $this->_get('status');
        $type = $this->_get('type');
        $filter = 'status=1';
        if($type != ''){
            $filter .= ' and type='.$type;
        }
        if ($keyword != null) {
            $filter .= ' and b.username like \'%' . $keyword . '%\'';
        }
        $m = new \addons\eth\model\EthTradingOrder();
        $sysM = new \web\common\model\sys\SysParameterModel();
        $eth_rate = $sysM->getValByName('eth_rate');
        $rows = $m->getList($this->getPageIndex(), $this->getPageSize(), $filter);
        $data = [];
        foreach ($rows as $v)
        {
            $temp = [];
            $temp[] = $v['username'];
            $temp[] = $v['phone'];
            $temp[] = bcdiv($v['amount'],$eth_rate,8);
            $temp[] = $v['amount'];
            $temp[] = $v['coin_name'];
            $temp[] = ($v['type'] == 1) ? '转入' : '转出';
            $temp[] = $v['to_address'];
            $temp[] = $v['txhash'];
            $temp[] = $v['update_time'];
            $temp[] = $v['remark'];
            $temp[] = '已完成';

            $data[] = $temp;
        }

        $fileName = '提现明细.xlsx';
        $headArr = ['用户名称','手机号','ETH数量','EOPS数量','币种','类型','目标钱包地址','交易哈希值','更新时间','备注','订单状态'];

        Loader::import('PHPExcel.Classes.PHPExcel');
        Loader::import('PHPExcel.Classes.PHPExcel.IOFactory.PHPExcel_IOFactory');
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties();


        $key = ord("A");

        foreach ($headArr as $v)
        {
            $colum = chr($key);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1',$v);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1',$v);
            $key += 1;
        }

        $c = 2;
        $objActSheet = $objPHPExcel->getActiveSheet();
        $objActSheet->getColumnDimension('A')->setWidth('10');
        $objActSheet->getColumnDimension('B')->setWidth('12');
        $objActSheet->getColumnDimension('C')->setWidth('12');
        $objActSheet->getColumnDimension('D')->setWidth('10');
        $objActSheet->getColumnDimension('E')->setWidth('8');
        $objActSheet->getColumnDimension('F')->setWidth('8');
        $objActSheet->getColumnDimension('G')->setWidth('45');
        $objActSheet->getColumnDimension('H')->setWidth('12');
        $objActSheet->getColumnDimension('I')->setWidth('18');
        $objActSheet->getColumnDimension('J')->setWidth('22');
        $objActSheet->getColumnDimension('K')->setWidth('10');

        foreach ($data as $key => $r)
        {
            $span = ord("A");

            foreach ($r as $keyName => $value)
            {
                $objActSheet->setCellValue(chr($span) . $c,$value);
                $span++;
            }

            $c++;
        }


        $fileName = iconv("utf-8","gb2312",$fileName);
        $objPHPExcel->setActiveSheetIndex(0);

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename='$fileName'");
        header("Cache-Control:max-age=0");

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel,"Excel2007");
        $objWriter->save("php://output");

        exit();
    }
}


























