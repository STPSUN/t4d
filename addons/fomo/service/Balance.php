<?php

namespace addons\fomo\service;

/**
 * Created by PhpStorm.
 * User: stp
 * Date: 2018/12/20
 * Time: 10:35
 */
class Balance extends \web\index\controller\AddonIndexBase
{
    /**
     * 分红发放到账户，70%到可用，30%到冻结
     * @param $user_id
     * @param $amount
     * @param $coin_id
     * @return bool
     */
    public function updateBalanceByBonus($user_id,$amount,$coin_id)
    {
        $balanceM = new \addons\member\model\Balance();
        $balanceM->startTrans();
        try
        {
            $frost_amount = bcmul($amount,0.3,8);
            $res = $balanceM->updateBalance($user_id,$frost_amount,$coin_id,true);
            if(!$res)
            {
                $balanceM->rollback();
                return false;
            }

            $use_amount = bcmul($amount,0.7,8);
            $res = $balanceM->updateBalanceByBonus($user_id,$use_amount,$coin_id);
            if(!$res)
            {
                $balanceM->rollback();
                return false;
            }

        }catch (\Exception $e)
        {
            $balanceM->rollback();
            return false;
        }

        $balanceM->commit();
        return true;
    }

    /**
     * 余额是否充足
     * @param $user_id
     * @param $game_id
     * @param $key_num
     * @param $coin_id
     * @return bool
     */
    public function isEnoughBalance($user_id,$game_id,$key_num,$coin_id)
    {
        $priceM = new \addons\fomo\model\KeyPrice();
        $current_price_data = $priceM->getGameCurrentPrice($game_id);
        $current_price = $current_price_data['key_amount'];

        $confM = new \addons\fomo\model\Conf();
        $key_inc_amount = $confM->getValByName('key_inc_amount');   //key递增值
        //计算总金额：key价格递增
        $key_total_price = iterativeDec($current_price,$key_num,$key_inc_amount);
        $key_total_price = round($key_total_price,8);

        $balanceM = new \addons\member\model\Balance();
        $frost_balance = $balanceM->getBalanceByCoinID($user_id,$coin_id,1);
        $use_balance = $balanceM->getBalanceByCoinID($user_id,$coin_id,2);
        $total_balance = $frost_balance['amount'] + $use_balance['amount'];

        if($key_total_price > $total_balance)
            return false;

        return $key_total_price;
    }

    /**
     * 扣除余额
     * 先扣冻结，再扣可用
     * @param $user_id
     * @param $amount
     * @param $coin_id
     * @return bool
     */
    public function updateBalance($user_id,$amount,$coin_id)
    {
        $balanceM = new \addons\member\model\Balance();
        $frost_balance = $balanceM->getBalanceByCoinID($user_id,$coin_id,1);

        if($frost_balance['amount'] >= $amount)
        {
            $is_save = $balanceM->updateBalance($user_id,$amount,$coin_id,false,1);
            if(!$is_save)
                return false;
        }else
        {
            $is_save = $balanceM->updateBalance($user_id,$frost_balance['amount'],$coin_id,false,1);
            if(!$is_save)
                return false;

            $use_amount = $amount - $frost_balance['amount'];
            $is_save = $balanceM->updateBalance($user_id,$use_amount,$coin_id,false,2);
            if(!$is_save)
                return false;
        }

        return true;
    }

}









































