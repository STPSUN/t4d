<?php

namespace addons\config\user\controller;

/**
 * Description of Exchange
 *
 * @author shilinqing
 */
class Exchange extends \web\user\controller\AddonUserBase{
    
    public function index(){
        return $this->fetch();
    }
    
    public function loadList() {
        $keyword = $this->_get('keyword');
        $filter = '';
        $m = new \addons\config\model\ExchangeRate();
        if ($keyword != null) {
            $filter = ' name like \'%' . $keyword . '%\'';
        }
        $total = $m->getTotal($filter);
        $rows = $m->getDataList($this->getPageIndex(), $this->getPageSize(), $filter);
        return $this->toDataGrid($total, $rows);
    }
    
    /**
     * 更新汇率
     */
    public function update(){
        $settings = config('juhe_config');
        $api_key = $settings['api_key'];
        $api_url = $settings['api_url'];
        $url = $api_url . $api_key;
        $result = http($url, null, 'GET');
        if($result['success']){
            $data = $result['data'];
            if($data['error_code'] == 0){
                //更新数据
                $list = $data['result']['list']; //汇率数据
                $m = new \addons\config\model\ExchangeRate();
                foreach ($list as $rate){
                    $data['id'] = $m->getID($rate[0]);
                    $data['name'] = $rate[0];
                    $data['amount'] = $rate[1];
                    $data['buying_rate'] = $rate[2];
                    $data['cash_buying_rate'] = $rate[3];
                    $data['cash_sale_rate'] = $rate[4];
                    $data['converted_price'] = $rate[5];
                    $data['rate'] = $data['buying_rate'] / $data['amount'];
                    $data['update_time'] = NOW_DATETIME;
                    $m->save($data);
                }
                return $this->successData();
            }else{
                return $this->failData($data['reason']);
            }
        }else{
            return $this->failData('请求接口失败');
        }
        
    }
    
    
}
