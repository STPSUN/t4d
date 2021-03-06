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
//        $coin_id = $this->_post('coin_id');
        $coin_id = 1;
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
                return $this->successData('null');
            }
            $res = $this->checkOrder($user_id, $address, $coin_id, $transaction_list);
        }
        return $this->successData('ok');

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
                $marketM = new \addons\financing\model\Market();
                $cny = $marketM->getDetailByCoinName('ETH','cny');
                $eth_rate = bcdiv($cny,7,4);
                $amount = $val['amount'];
                $ittm_amount = bcmul($amount,$eth_rate,8);
                $eth_order_id = $m->transactionIn($user_id, $from_address, $address, $coin_id, $amount, $txhash, $block_number, 0, 1, 1, "外网转入",$ittm_amount);
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

                    //ETH换算EOPS
//                    $marketM = new \addons\financing\model\Market();
//                    $cny = $marketM->getDetailByCoinName('ETH','cny');
//                    $eth_rate = bcdiv($cny,7,4);
//                    $maketM = new \web\api\model\MarketModel();
//                    $rate = $maketM->getUsdtRateByCoinId($coin_id);
//                    $ittm_amount = bcmul($amount,$eth_rate,8);
//                    echo $ittm_amount;exit();
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
        if(empty($game_id))
            return $this->failData('系统繁忙，请稍后再试');

        $rewardM = new \addons\fomo\model\RewardRecord();
        $invite_reward = $rewardM->getTotalByType($this->user_id, $coin_id);
        $data['invite_reward'] = round($invite_reward,2);
        $other_reward = $rewardM->getTotalByType($this->user_id, $coin_id,'0,3,4,5,6,7,8');
        $data['other_reward'] = round($other_reward,2);
        $data['all_reward'] = 0;
        if($game_id)
        {
            $all_reward = $rewardM->getUserTotal($this->user_id, $coin_id, $game_id);
            $data['all_reward'] = round($all_reward,2);
        }

        $data['all_reward'] = round($data['all_reward'],2);
        $maketM = new \web\api\model\MarketModel();
        $rate = $maketM->getUsdtRateByCoinId(1);
        $data['all_reward_cny'] = bcmul($data['all_reward'], $rate, 2);
        $balanceM = new \addons\member\model\Balance();
        $balance = $balanceM->getBalanceByCoinID($this->user_id, $coin_id,1);
        $use_balance = $balanceM->getBalanceByCoinID($this->user_id,$coin_id,2);

//        print_r($balance);exit();
//        $sysM = new \web\common\model\sys\SysParameterModel();
//        $eth_rate = $sysM->getValByName('eth_rate');

        $marketM = new \addons\financing\model\Market();
        $cny = $marketM->getDetailByCoinName('ETH','cny');
        $eth_rate = bcdiv($cny,7,2);
        $eth_balance = bcdiv($balance['amount'],$eth_rate,2);
        $data['balance'] = round($balance['amount'],2);
        $data['eth_balance'] = round($eth_balance,4);
        $data['use_balance'] = round($use_balance['amount'],2);
        $use_eth_balance = empty($use_balance['amount']) ? 0 : bcdiv($use_balance['amount'],$eth_rate,4);
        $data['use_eth_balance'] = round($use_eth_balance,4);
        return $this->successData($data);
    }
    
    /**
     * 提取
     */
    public function withdraw(){
        if(IS_POST){
            $sysM = new \web\common\model\sys\SysParameterModel();
            $is_withdraw = $sysM->getValByName('is_withdraw');
            if($is_withdraw != 1)
                return $this->failData('提取暂未开放');

//            return $this->failData('提取暂未开放');
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

            $ethM = new \addons\eth\model\EthTradingOrder();
            $order_num = $ethM->where(['user_id' => $this->user_id, 'coin_id' => $coin_id, 'status' => 0])->find();
            if(!empty($order_num))
            {
                return $this->failData(lang('Please wait for approval before withdrawing cash again'));
            }

            $eops_limit = $sysM->getValByName('eops_limit');
            $where['user_id'] = $this->user_id;
            $where['coin_id'] = $coin_id;
            $where['status'] = array('in','0,1');
            $eops_num = $ethM->where($where)->whereTime('update_time','today')->sum('eops_amount');
            if($eops_num >= $eops_limit)
            {
                return $this->failData(lang('withdraw limit') . 0);
            }
            $eops_total = $eops_num + $amount;
            if($eops_total > $eops_limit)
            {
                $num = $eops_limit - $eops_num;
                return $this->failData(lang('withdraw limit') . $num);
            }

            $marketM = new \addons\financing\model\Market();
            $cny = $marketM->getDetailByCoinName('ETH','cny');
            $eth_rate = bcdiv($cny,7,4);

            try{
                $without_min = $sysM->getValByName("withdraw_min");
                if($amount < $without_min){
                    return $this->failData(lang('The minimum withdrawal amount is:').$without_min);
                }

                $balanceM = new \addons\member\model\Balance();
                $balance = $balanceM->getBalanceByCoinID($this->user_id, $coin_id,2);
                if(empty($balance)){
                    return $this->failData(lang('Lack of balance'));
                }

//                $without_limit_rate = $sysM->getValByName("withdraw_limit_rate");
//                $without_limit_amount = $this->countRate($balance['amount'],$without_limit_rate);
//                if($amount > $without_limit_amount)
//                    return $this->failData('提币数量不能超过总额的' . $without_limit_rate . '%');

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
                    $data['amount'] = bcdiv($amount,$eth_rate,8);
                    $data['tax'] = $tax;
                    $data['type'] = 0;//转出
                    $data['coin_id'] = $coin_id;
                    $data['to_address'] = $address;
                    $data['from_address'] = $this->address;
                    $data['user_id'] = $this->user_id;
                    $data['status'] = 0;
                    $data['eops_amount'] = $amount;
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
            $marketM = new \addons\financing\model\Market();
            $cny = $marketM->getDetailByCoinName('ETH','cny');
            $eth_rate = bcdiv($cny,7,4);
            $this->assign('eth_rate',$eth_rate);
            $this->assign('coin_id',$this->_get('coin_id'));
            $this->assign('id','0');
            $this->setLoadDataAction('');
            return $this->fetch('public/withdraw');
        }
    }

    /**
    * 转账
    */
    public function transfer()
    {
        if(IS_POST)
        {
            return $this->failData('转账暂未开放');
            $user_id = $this->user_id;
            if($user_id <= 0){
                return $this->failData(lang('Not logged in'));
            }

            $amount = $this->_post('amount');
            $coin_id = $this->_post('coin_id');
            $username = $this->_post('username');

            $userM = new \addons\member\model\MemberAccountModel();
            $to_user_id = $userM->getUserByUsername($username);

            $sysM = new \web\common\model\sys\SysParameterModel();
            $is_withdraw = $sysM->getValByName('is_transfer_tax');
            $tax = 0;
            $total_amount = $amount;
            if($is_withdraw == 1)
            {
                $transfer_rate = $sysM->getValByName('transfer_tax');
                if(!empty($transfer_rate)){
                    $tax = $amount * $transfer_rate / 100;
                }
                $total_amount += $tax; //用户资产扣除总额
            }

            $balanceM = new \addons\member\model\Balance();
            $balance = $balanceM->getBalanceByCoinID($user_id,$coin_id,2);
            if($balance['amount'] < $total_amount)
                return $this->failData(lang('Lack of balance'));

            $recordM = new \addons\member\model\TradingRecord();

            $balanceM->startTrans();
            try
            {
                $is_save = $balanceM->updateBalance($user_id,$total_amount,$coin_id,false,2);
                if(!$is_save)
                {
                    $balanceM->rollback();
                    return $this->failData('系统繁忙，请稍后再试，#1');
                }
                $recordM->addRecord($user_id,$coin_id,$total_amount,0,0,11,false,$to_user_id,'','','转账-转出');

                $is_save = $balanceM->updateBalance($to_user_id,$amount,$coin_id,true,2);
                if(!$is_save)
                {
                    $balanceM->rollback();
                    return $this->failData('系统繁忙，请稍后再试，#2');
                }
                $recordM->addRecord($user_id,$coin_id,$amount,0,0,11,true,$to_user_id,'','','转账-收入');
            }catch (\Exception $e)
            {
                $balanceM->rollback();
                return $this->failData($e->getMessage());
            }

            $balanceM->commit();
            return $this->successData(1);

        }else
        {
            $marketM = new \addons\financing\model\Market();
            $cny = $marketM->getDetailByCoinName('ETH','cny');
            $eth_rate = bcdiv($cny,7,4);
            $this->assign('eth_rate',$eth_rate);
            $this->assign('coin_id',$this->_get('coin_id'));
            $this->assign('id','0');
            $this->setLoadDataAction('');
            return $this->fetch('public/transfer');
        }
    }

    /**
     * 矿池
     */
    public function orepool()
    {
        if(IS_POST)
        {
            $user_id = $this->user_id;
            if($user_id <= 0){
                return $this->failData(lang('Not logged in'));
            }

            $amount = $this->_post('amount');
            $coin_id = $this->_post('coin_id');
            $pool_account = $this->_post('pool_account');

            $poolM = new \addons\member\model\MemberTransferPool();
            $order_num = $poolM->where(['user_id' => $this->user_id, 'coin_id' => $coin_id, 'status' => 1])->find();
            if(!empty($order_num))
            {
                return $this->failData(lang('Please wait for approval before withdrawing cash again'));
            }

            $sysM = new \web\common\model\sys\SysParameterModel();
            $is_pool = $sysM->getValByName('is_pool_tax');
            $tax = 0;
            $total_amount = $amount;
            if($is_pool == 1)
            {
                $transfer_rate = $sysM->getValByName('pool_tax');
                if(!empty($transfer_rate)){
                    $tax = $amount * $transfer_rate / 100;
                }
                $total_amount += $tax; //用户资产扣除总额
            }

            $balanceM = new \addons\member\model\Balance();
            $balance = $balanceM->getBalanceByCoinID($user_id,$coin_id,2);
            if($balance['amount'] < $total_amount)
                return $this->failData(lang('Lack of balance'));

            $recordM = new \addons\member\model\TradingRecord();

            $balanceM->startTrans();
            try
            {
                $is_save = $balanceM->updateBalance($user_id,$total_amount,$coin_id,false,2);
                if(!$is_save)
                {
                    $balanceM->rollback();
                    return $this->failData('系统繁忙，请稍后再试，#1');
                }

                $recordM->addRecord($user_id,$coin_id,$total_amount,0,0,11,false,$user_id,'','','矿池提币');
                $pool_data = [
                    'user_id'   => $user_id,
                    'pool_account' => $pool_account,
                    'amount'    => $amount,
                    'tax'       => $tax,
                    'coin_id'   => $coin_id,
                    'update_time' => NOW_DATETIME
                ];
                $poolM->add($pool_data);
            }catch (\Exception $e)
            {
                $balanceM->rollback();
                return $this->failData($e->getMessage());
            }

            $balanceM->commit();
            return $this->successData(1);
        }else
        {
            $marketM = new \addons\financing\model\Market();
            $cny = $marketM->getDetailByCoinName('ETH','cny');
            $eth_rate = bcdiv($cny,7,4);
            $this->assign('eth_rate',$eth_rate);
            $this->assign('coin_id',$this->_get('coin_id'));
            $this->assign('id','0');
            $this->setLoadDataAction('');
            return $this->fetch('public/orePool');
        }
    }
    
}
