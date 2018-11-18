<?php

namespace addons\financing\user\controller;

/**
 * Description of Market
 * 火币行情配置
 * @author shilinqing
 */
class Market extends \web\user\controller\AddonUserBase {
    
    public function index(){
        return $this->fetch();
    }
    
    public function loadList() {
        $keyword = $this->_get('keyword');
        $filter = '';
        $m = new \addons\financing\model\Market();
        if ($keyword != null) {
            $filter = ' coin_name like \'%' . $keyword . '%\'';
        }
        $total = $m->getTotal($filter);
        $rows = $m->getDataList($this->getPageIndex(), $this->getPageSize(), $filter);
        return $this->toDataGrid($total, $rows);
    }
    
    public function edit(){
        if(IS_POST){
            $data = $_POST;
            $data['transfer_name'] = $data['coin_name'] .'/'. $data['pay_coin_name'];
            $data['update_time'] = NOW_DATETIME;
            $m = new \addons\financing\model\Market();
            $ret = $m->save($data);
            if($ret > 0){
                return $this->successData();
            }else{
                return $this->failData('编辑失败');
            }
        }else{
            $this->assign('id',$this->_get('id'));
            $this->setLoadDataAction('loadData');
            return $this->fetch();
        }
        
    }
    
    public function update(){
        $id = $this->_post('id');
        if (!empty($id)) {
            $m = new \addons\financing\model\Market();
            $exchangeM = new \addons\config\model\ExchangeRate();
            try {
                $hotApi = new \web\common\utils\HotApi();
                $data = $m->getDetail($id);
                $symbol = strtolower(str_replace("/","",$data['transfer_name']));
                $detail_merged = $hotApi->get_detail_merged($symbol);
                if($detail_merged['success']){
                    $tick = $detail_merged['data']['tick'];
                    $tick['id'] = $data['id'];
                    $tick['transfer_name'] = $data['transfer_name'];
                    $tick['coin_name'] = $data['coin_name'];
                    $tick['pay_coin_name'] = $data['pay_coin_name'];
                    $tick['rate'] = ($tick['close'] - $tick['open']) / $tick['open'];
                    if($data['pay_coin_name'] == 'USDT'){
                        $usd_rate = $exchangeM->getRate('美元');
                        $tick['cny'] = $usd_rate * $tick['close'];
                    }
                    if($data['pay_coin_name'] == 'ETH'){
                        $eth_cny = $m->getDetailByCoinName('ETH','cny');
                        $tick['cny'] = $eth_cny * $tick['high'];
                    }
                    if($tick['coin_name'] == 'ETH'){
                        $this->_ethRate = $tick['cny'];
                    }
                    $tick['update_time'] = NOW_DATETIME;
                    $res = $m->save($tick);
                    if ($res > 0) {
                        return $this->successData();
                    } else {
                        return $this->failData('更新失败');
                    }
                }else{
                    $msg = '行情不存在';
                    if(!empty($detail_merged['message'])){
                        $msg = $detail_merged['message'];
                    }
                    return $this->failData($msg);
                }
            } catch (\Exception $e) {
                return $this->failData($e->getMessage());
            }
        } else {
            return $this->failData('更新失败，参数有误');
        }
    }
    
    public function loadData(){
        $id = $this->_get('id');
        $m = new \addons\financing\model\Market();
        $data = $m->getDetail($id);
        return $data;
    }
    
    public function del(){
        $id = $this->_post('id');
        if (!empty($id)) {
            $m = new \addons\financing\model\Market();
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
