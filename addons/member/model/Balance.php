<?php

namespace addons\member\model;

/**
 * 用户资产
 *
 * @author shilinqing
 */
class Balance extends \web\common\model\BaseModel
{

    protected function _initialize()
    {
        $this->tableName = 'member_balance';
    }

    /**
     * get user balance list
     * @param type $user_id
     * @param type $coin_id
     * @return type
     */
    public function getUserBalanceList($user_id, $coin_id = '')
    {
        $m = new \addons\config\model\Coins();
        $marketM = new \web\api\model\MarketModel();
        $sql = 'select a.id,a.amount,a.coin_id,b.coin_name,b.pic,(c.cny * a.amount) as cny from ' . $this->getTableName() . ' a,' . $m->getTableName() . ' b,' . $marketM->getTableName() . ' c where a.user_id=' . $user_id . ' and a.coin_id=b.id and b.coin_name=c.coin_name';
        if (!empty($coin_id)) {
            $sql .= ' and a.coin_id=' . $coin_id;
        }
        return $this->query($sql);
    }

    /**
     * get user balance by coin id , if null then add new data
     * @param type $user_id
     * @param type $coin_id
     * @return int
     */
    public function getBalanceByCoinID($user_id, $coin_id)
    {
        $where['user_id'] = $user_id;
        $where['coin_id'] = $coin_id;
        $data  = $this->where($where)->find();
        if(!$data){
            return $this->addBalance($user_id, $coin_id);
        }
        return $data;
    }

    public function addBalance($user_id,$coin_id){
        $data = [
            'user_id' => $user_id,
            'coin_id' => $coin_id,
            'amount' => 0,
            'before_amount' => 0,
            'total_amount' => 0,
            'otc_frozen_amount' => 0,
            'withdraw_frozen_amount' => 0,
            'update_time' => NOW_DATETIME,
        ];
        $ret = $this->save($data);
        $data = $this->where(['user_id' => $user_id, 'coin_id' => $coin_id])->find();
        return $data ;
    }

    /**
     * 更新用户资产
     * @param type $user_id
     * @param type $amount 变动金额
     * @param type $coin_id 变动币种
     * @param type $type 变动类型，false 减值，true增值
     * @return type
     */
    public function updateBalance($user_id, $amount, $coin_id, $type = false)
    {
        $map = array();
        $map['user_id'] = $user_id;
        $map['coin_id'] = $coin_id;
        $userAsset = $this->where($map)->find();
        if (!$userAsset) {
            $userAsset['user_id'] = $user_id;
            $userAsset['before_amount'] = 0;
            $userAsset['amount'] = 0;
            $userAsset['total_amount'] = 0;
            $userAsset['coin_id'] = $coin_id;
        }
        $userAsset['update_time'] = NOW_DATETIME;
        if ($type) {
            $userAsset['before_amount'] = $userAsset['amount'];
            $userAsset['amount'] = $userAsset['amount'] + $amount;
            $userAsset['total_amount'] = $userAsset['total_amount'] + $amount;
        } else {
            $userAsset['before_amount'] = $userAsset['amount'];
            $userAsset['amount'] = $userAsset['amount'] - $amount;
            $userAsset['total_amount'] = $userAsset['total_amount'] - $amount;
        }
        $res = $this->save($userAsset);
        if (!$res) {
            return false;
        }
        return $userAsset;
    }

    /**
     * 验证手续费所需糖果库存是否足够
     */
    public function verifyStock($user_id, $coin_id , $amount){
        $where['coin_id'] = $coin_id;
        $where['user_id'] = $user_id;
        $where['amount'] = array('>=',$amount);
        return $this->where($where)->find();
    }


    /**
     * otc确认交易余额操作
     * @param type $order_id
     * @return boolean
     */
    public function otcTradingConfirm($order_id){
        $m = new \addons\otc\model\OtcOrder();
        $recordM = new \addons\member\model\TradingRecord();
        $m->startTrans();
        $order = $m->getDetail($order_id);
        $order['status'] = 4; //完成
        unset($order['pay_detail_json']);
        $update_status = $m->save($order);
        if(empty($update_status)){
            $m->rollback();
            return false;
        }
        $buy_user_id = $order['buy_user_id'];
        $user_id = $order['user_id'];
        $coin_id = $order['coin_id'];
        $type = $order['type'];
        $tax_amount = $order['tax_amount'];
        $amount = $order['amount'];
        $total_amount = $order['total_amount'];
        if($type == 1){
            //买单 buy_user_id 扣除余额 , user_id 添加余额 ,手续费从total_amount 扣除
            $out_asset = $this->updateOtcAsset($buy_user_id, $amount, $coin_id);
            if(empty($out_asset)){
                $m->rollback();
                return false;
            }
            $out_before_amount = $out_asset['amount'] + $amount;
            $out_record_id = $recordM->addRecord($buy_user_id, $coin_id, $amount, $out_before_amount, $out_asset['amount'], 1, 0, $user_id,'','','用户交易扣除');
            if(empty($out_record_id)){
                $m->rollback();
                return false;
            }
            $_amount = $amount - $tax_amount;
            $in_asset = $this->updateBalance($user_id, $_amount, $coin_id, 1);
            if(empty($in_asset)){
                $m->rollback();
                return false;
            }
            $in_record_id = $recordM->addRecord($user_id, $coin_id, $_amount, $in_asset['before_amount'], $in_asset['amount'], 1, 1, $buy_user_id,'','','用户交易增加');
            if(empty($in_record_id)){
                $m->rollback();
                return false;
            }
            $m->commit();
            return true;
        }else{
            //卖单 user_id 扣除total_amount余额, buy_user_id 添加余额 amount
            $out_asset = $this->updateOtcAsset($user_id, $total_amount, $coin_id);
            if(empty($out_asset)){
                $m->rollback();
                return false;
            }
            $out_before_amount = $out_asset['amount'] + $total_amount;
            $out_record_id = $recordM->addRecord($user_id, $coin_id, $total_amount, $out_before_amount, $out_asset['amount'], 1, 0, $buy_user_id,'','','用户交易扣除');
            if(empty($out_record_id)){
                $m->rollback();
                return false;
            }
            $in_asset = $this->updateBalance($buy_user_id, $amount, $coin_id, 1);
            if(empty($in_asset)){
                $m->rollback();
                return false;
            }
            $in_record_id = $recordM->addRecord($buy_user_id, $coin_id, $amount, $in_asset['before_amount'], $in_asset['amount'], 1, 1, $user_id,'','','用户交易增加');
            if(empty($in_record_id)){
                $m->rollback();
                return false;
            }
            $m->commit();
            return true;
        }

    }


    /**
     * 更新otc余额
     * @param type $user_id     操作用户
     * @param type $coin_id    币种
     * @param type $amount     数量
     * @param type $type    0=减少 1=增加
     */
    public function updateOtcAsset($user_id, $amount, $coin_id ,$type = 0){
        $data = $this->getBalanceByCoinID($user_id, $coin_id);
        if($type == 0){
            $data['otc_frozen_amount'] = $data['otc_frozen_amount'] - $amount;
            $data['update_time'] = NOW_DATETIME;
            $ret = $this->save($data);

        }else{
            $before_amount = $data['amount'];
            $data['before_amount'] = $before_amount;
            $data['amount'] = $before_amount + $amount;
            $data['total_amount'] = $data['total_amount'] + $amount;
            $data['otc_frozen_amount'] = $data['otc_frozen_amount'] - $amount;
            $data['update_time'] = NOW_DATETIME;
            $ret = $this->save($data);

        }
        if($ret > 0)
            return $data;
        else
            return '';
    }




}
