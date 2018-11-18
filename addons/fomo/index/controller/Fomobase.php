<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace addons\fomo\index\controller;

/**
 * Description of FomoBase
 *
 * @author shilinqing
 */
class Fomobase extends \web\index\controller\AddonIndexBase{
    
    public function getNotice(){
        $m = new \addons\config\model\Notice();
        $data = $m->find();
        switch (cookie('think_var')) {
        case 'en-us':
            $baidu = new BaiduApi();
            $data['content'] = $baidu->translate($data['content'],"zh","en");
            break;
        case 'zh-cn':
            # code...
            break;
        case 'zh-tw':
            $baidu = new BaiduApi();
            $data['content'] = $baidu->translate($data['content'],"zh","cht");
            break;  
        default:
            # code...
            break;
        }
        return $this->successData($data);
    }
    
    
    /**
     * 外网转入记录获取。
     * @return type
     */
    public function getEthOrders(){
        $coin_id = $this->_post('coin_id');
        $user_id = $this->user_id;
        $address = $this->address;
        if($user_id <= 0)
            return $this->failData(lang('Please login'));
        set_time_limit(200);
        $ethApi = new \EthApi();
        $coinM = new \addons\config\model\Coins();
        $coin = $coinM->getDetail($coin_id);
        if(!empty($coin)){
            $ethApi->set_byte($coin['byte']);
            if(!empty($coin['contract_address'])){
                $ethApi->set_contract($coin['contract_address']);
            }
            $transaction_list = $ethApi->erscan_order($address, $coin['is_token']);
            if(empty($transaction_list)){
                return $this->successData(2);
            }
            $res = $this->checkOrder($user_id, $address, $coin_id, $transaction_list);
        }
        return $this->successData();

    }
    
   /**
     * 外网数据写入
     * @param type $user_id 用户id
     * @param type $address 用户地址
     * @param type $list    抓取到的数据
     * @param type $coin_id 币种id
     * @return boolean
     */
    private function checkOrder($user_id, $address, $coin_id, $list){
        $m = new \addons\eth\model\EthTradingOrder();
        $balanceM = new \addons\member\model\Balance();
        $recordM = new \addons\member\model\TradingRecord();
        $ittm_coin_id = 2;
        foreach($list as $val){
            $txhash = $val['hash'];
            $block_number = $val['block_number'];
            $from_address = $val['from'];
            try{
                $res = $m->getDetailByTxHash($txhash);//订单匹配
                if($res){
                    continue;
                }
                $m->startTrans();
                $amount = $val['amount'];
                $eth_order_id = $m->transactionIn($user_id, $from_address, $address, $coin_id, $amount, $txhash, $block_number, 0, 1, 1, "外网转入");
                if($eth_order_id > 0){
                    //插入转入eth记录成功
                    $balance = $balanceM->updateBalance($user_id, $amount, $coin_id, true);
                    if(!$balance){
                        $m->rollback();
                        return false;
                    }
                    $type = 2;
                    $before_amount = $balance['before_amount'];
                    $after_amount = $balance['amount'];
                    $change_type = 1; //增加
                    $remark = '外网转入';
                    $_id = $recordM->addRecord($user_id, $coin_id, $amount, $before_amount, $after_amount, $type, $change_type, $user_id, $address, '', $remark);

                    //ETH换算ITTM
                    $maketM = new \web\api\model\MarketModel();
                    $rate = $maketM->getUsdtRateByCoinId($coin_id);
                    $ittm_amount = bcmul($amount,$rate,8);
                    $ittm_balance = $balanceM->updateBalance($user_id, $ittm_amount, $ittm_coin_id, true);
                    if(!$ittm_balance){
                        $m->rollback();
                        return false;
                    }
                    $type = 2;
                    $ittm_before_amount = $ittm_balance['before_amount'];
                    $ittm_after_amount = $ittm_balance['amount'];
                    $recordM->addRecord($user_id, $ittm_coin_id, $ittm_amount, $ittm_before_amount, $ittm_after_amount, $type, $change_type, $user_id, $address, '', $remark);
                    if(!$_id ){
                        $m->rollback();
                        return false;
                    }
                    $m->commit();
                }else{
                    $m->rollback();
                    return false;
                }
            } catch (\Exception $ex) {
                return false;
            }
        }
        return true;

    }
    
    protected function countRate($total_price, $rate){
        return $total_price * $rate / 100;
    }
    
    public function getBalance(){
        $coin_id = $this->_get('coin_id');
        $game_id = $this->_get('game_id');
        if($this->user_id <= 0){
            return $this->failData(lang('Not logged in'));
        }
        $rewardM = new \addons\fomo\model\RewardRecord();
        $data['invite_reward'] = $rewardM->getTotalByType($this->user_id, $coin_id);
        $data['other_reward'] = $rewardM->getTotalByType($this->user_id, $coin_id,'0,3,4,5,6');
        $data['all_reward'] = 0;
        if($game_id) $data['all_reward'] = $rewardM->getUserTotal($this->user_id, $coin_id, $game_id);

        $data['all_reward'] = sprintf("%01.8f", $data['all_reward']);
        $maketM = new \web\api\model\MarketModel();
        $rate = $maketM->getUsdtRateByCoinId(1);
        $data['all_reward_cny'] = bcmul($data['all_reward'], $rate, 8);
        $balanceM = new \addons\member\model\Balance();
        $balance = $balanceM->getBalanceByCoinID($this->user_id, $coin_id);
        $data['balance'] = $balance['amount'];
        return $this->successData($data);
    }
    
    /**
     * 提取
     */
    public function withdraw(){
        if(IS_POST){
            if($this->user_id <= 0){
                return $this->failData(lang('Not logged in'));
            }
            $amount = $this->_post('amount');
            $coin_id = $this->_post('coin_id');
            $address = $this->_post('address');
            if( empty($coin_id) || empty($address) || empty($amount)){
                return $this->failData(lang('missing parameter'));
            }
            if($amount <= 0){
                return $this->failData(lang('The amount must be greater than 0'));
            }

            try{
                $sysM = new \web\common\model\sys\SysParameterModel();
                $without_min = $sysM->getValByName("withdraw_min");
                if($amount < $without_min){
                    return $this->failData(lang('The minimum withdrawal amount is:').$without_min);
                }

                $balanceM = new \addons\member\model\Balance();
                $balance = $balanceM->getBalanceByCoinID($this->user_id, $coin_id);
                if(empty($balance)){
                    return $this->failData(lang('Lack of balance'));
                }

                $without_limit_rate = $sysM->getValByName("withdraw_limit_rate");

                $without_limit_amount = $this->countRate($balance['amount'],$without_limit_rate);
                if($amount > $without_limit_amount)
                    return $this->failData('提币数量不能超过总额的' . $without_limit_rate . '%');

                $ethM = new \addons\eth\model\EthTradingOrder();

                $filter = 'user_id = '. $this->user_id ." and coin_id = ".$coin_id;
                $userWithout = $ethM->getSum($filter, "amount");
                $without_self = $sysM->getValByName("register_send");
                if($amount > $balance['amount'] - $without_self - $without_self){
                    return $this->failData(lang('The system gives money and cannot withdraw it').$without_self);
                }

                $key_head = strtolower(substr($address,0,2));
                if(($key_head!=="0x" || strlen($address) !==42)){
                    return $this->failData(lang('The address does not exist or is illegal, please check'));
                }

                $coinM = new \addons\config\model\Coins();

                $without_rate = $sysM->getValByName("withdraw_rate");
                $tax = 0;
                if(!empty($without_rate)){
                    $tax = $amount * $without_rate / 100;
                }
                $total_amount = $amount + $tax; //用户资产扣除总额
                if($balance['amount'] < $total_amount){
                    return $this->failData(lang('Lack of balance'));
                }
                $balanceM->startTrans();
                $before_amount = $balance['amount'];
                $balance['before_amount'] = $before_amount;
                $balance['amount'] = $before_amount - $total_amount;
                $balance['withdraw_frozen_amount'] = $balance['withdraw_frozen_amount'] + $total_amount;
                $balance['update_time'] = NOW_DATETIME;
                $ret = $balanceM->save($balance);
                if($ret > 0){
                    //保存提取订单
                    $data['amount'] = $amount;
                    $data['tax'] = $tax;
                    $data['type'] = 0;//转出
                    $data['coin_id'] = $coin_id;
                    $data['to_address'] = $address;
                    $data['from_address'] = $this->address;
                    $data['user_id'] = $this->user_id;
                    $data['status'] = 0;
                    $data['update_time'] = NOW_DATETIME;
                    $id = $ethM->add($data);
                    if($id > 0){
                        $balanceM->commit();
                        return $this->successData($id);
                    }else{
                        $balanceM->rollback();
                        return $this->failData(lang('Failed to submit extract'));
                    }
                }else{
                    $balanceM->rollback();
                    return $this->failData(lang('Update balance failed'));
                }
            } catch (\Exception $ex) {
                $balanceM->rollback();
                return $this->failData($ex->getMessage());
            }
            
        }else{
            $this->assign('coin_id',$this->_get('coin_id'));
            $this->assign('id','0');
            $this->setLoadDataAction('');
            return $this->fetch('public/withdraw');
        }
    }
    

    
}
