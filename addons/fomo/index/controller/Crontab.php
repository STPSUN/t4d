<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace addons\fomo\index\controller;

/**
 * Description of Crontab
 * f3d投注分配限制所选战队, p3d为所有
 * 类型：0=p3d分红,1=f3d分红
 * @author shilinqing
 */
class Crontab extends \web\common\controller\Controller{
    
    public function _initialize(){
        
    }

    public function excute(){
        set_time_limit(0);
        $queueM = new \addons\fomo\model\BonusSequeue();
        $list = $queueM->getUnSendData();
        if(!empty($list)){
            try
            {
                foreach($list as $data){
                    try{
                        $queueM->startTrans();
                        $res = false;
                        if($data['type'] == 1){
                            $res = $this->sendT3d($data['user_id'],$data['coin_id'],$data['amount'], $data['game_id'], $data['scene']);
                        }
                        if(!$res)
                            return json($this->failData('发放失败'));

                        //更新发放状态
                        $data['status'] = 1;
                        $data['update_time'] = NOW_DATETIME;
                        $queueM->save($data);
                        $queueM->commit();

//                        return json($this->successData());
                    } catch (\Exception $ex) {
                        $queueM->rollback();
                        return json($this->failData($ex->getMessage()) );
                    }
                }

                echo '处理成功';
            }catch (\Exception $e)
            {
                echo '处理失败';
                return json($this->failData($e->getMessage()) );
            }
        }
    }

    /**
     * 发放T3d分红
     * @param type $coin_id
     * @param type $amount
     * @param type $game_id
     * @param type $scene 场景id 场景:0=p3d购买（全网分红），1=f3d投注分配，2=f3d开奖分配'
     * @return boolean
     */
    private function sendT3d($user_id,$coin_id, $amount, $game_id, $scene){
        $keyRecordM = new \addons\fomo\model\KeyRecord();
        $balanceM = new \addons\member\model\Balance();
        $rewardM = new \addons\fomo\model\RewardRecord();

        $amount = $this->limitAmount($user_id,$game_id,$amount);
        if(!$amount)
            return true;

        if($scene == 0)
        {
            $this->sendT3dBuy($user_id,$game_id,$amount,$coin_id);
            return true;
        }

        $remark = '';
        switch ($scene)
        {
            case 4:
                $remark = '最后投资分红'; break;
            case 5:
                $remark = '大赢家分红';  break;
            case 6:
                $remark = '奖金池触手分红';    break;
            case 7:
                $remark = '节点分红';   break;
            case 8:
                $remark = '中期奖金';   break;
        }

        //添加余额, 添加分红记录
        $balance = $balanceM->getBalanceByCoinID($user_id, $coin_id);
        $balance = $balanceM->updateBalance($user_id, $amount, $coin_id, true);
        if($balance != false){
            $before_amount = $balance['before_amount'];
            $after_amount = $balance['amount'];
//            $type = 0; //奖励类型 0=投注分红(全网分红)，1=胜利战队分红，2=胜利者分红，3=邀请分红
//            $remark  = 't3d全网分红';
            $rewardM->addRecord($user_id, $coin_id, $before_amount, $amount, $after_amount, $scene, $game_id,$remark);
            //分红值增加
            $keyRecordM->where(['game_id' => $game_id, 'user_id' => $user_id])->setInc('bonus_amount',$amount);
        }

//        $record_list = $keyRecordM->getRecord($user_id, $game_id);
//        if(!empty($record_list)){
//            $team_total_key = $keyRecordM->getTotalByGameAndTeamID($game_id);
//            foreach($record_list as $k => $record){
//                if($record['key_num'] <= 0){
//                    continue;
//                }
//                $user_id = $record['user_id'];
//                $rate = $this->getUserRate($team_total_key, $record['key_num']);
//                $_amount = $amount * $rate; //所得分红
//                //添加余额, 添加分红记录
//                $balance = $balanceM->getBalanceByCoinID($user_id, $coin_id);
//                $balance = $balanceM->updateBalance($user_id, $_amount, $coin_id, true);
//                if($balance != false){
//                    $before_amount = $balance['before_amount'];
//                    $after_amount = $balance['amount'];
//                    $type = 0; //奖励类型 0=投注分红(全网分红)，1=胜利战队分红，2=胜利者分红，3=邀请分红
//                    $remark  = 't3d全网分红';
//                    $rewardM->addRecord($user_id, $coin_id, $before_amount, $_amount, $after_amount, $type, $game_id,$remark);
//                }
//            }
//        }
        return true;
    }

    private function sendT3dBuy($user_id,$game_id,$amount,$coin_id)
    {
        $balanceM = new \addons\member\model\Balance();
        $keyRecordM = new \addons\fomo\model\KeyRecord();
        $rewardM = new \addons\fomo\model\RewardRecord();

        $record_list = $keyRecordM->getRecord($user_id, $game_id);
        if(!empty($record_list)){
            $team_total_key = $keyRecordM->getTotalByGameAndTeamID($game_id);
            foreach($record_list as $k => $record){
                if($record['key_num'] <= 0){
                    continue;
                }
                $user_id = $record['user_id'];
//                $rate = $this->getUserRate($team_total_key, $record['key_num']);
//                $_amount = $amount * $rate; //所得分红
                //添加余额, 添加分红记录
                $balance = $balanceM->getBalanceByCoinID($user_id, $coin_id);
                $balance = $balanceM->updateBalance($user_id, $amount, $coin_id, true);
                if($balance != false){
                    $before_amount = $balance['before_amount'];
                    $after_amount = $balance['amount'];
                    $type = 0; //奖励类型 0=投注分红(全网分红)，1=胜利战队分红，2=胜利者分红，3=邀请分红
                    $remark  = '全网分红';
                    $rewardM->addRecord($user_id, $coin_id, $before_amount, $amount, $after_amount, $type, $game_id,$remark);
                }
            }
        }
    }

    /**
     * 封顶限制
     */
    private function limitAmount($user_id,$game_id,$amount)
    {
        $keyRecordM = new \addons\fomo\model\KeyRecord();
        $data = $keyRecordM->where(['game_id' => $game_id, 'user_id' => $user_id])->field('limit_amount,bonus_amount')->find();
        $limit_amount = $data['limit_amount'];
        $user_amount = $data['bonus_amount'];

        if($limit_amount <= $user_amount)
        {
            $keyRecordM->save([
                'status' => 2,
            ],[
                'user_id'   => $user_id,
                'game_id'   => $game_id,
            ]);
            return false;
        }

        $total_amount = $user_amount + $amount;
        if($limit_amount <= $total_amount)
        {
            $keyRecordM->save([
                'status' => 2,
            ],[
                'user_id'   => $user_id,
                'game_id'   => $game_id,
            ]);
            $amount = $limit_amount - $user_amount;
        }

        return $amount;
    }
    
    /**
     * 发放F3d分红
     * @param type $user_id
     * @param type $coin_id
     * @param type $team_id
     * @param type $game_id
     * @param type $amount
     * @param type $scene 场景id 场景:0=p3d购买，1=f3d投注分配，2=f3d开奖分配'
     */
    private function sendF3d($user_id, $coin_id, $game_id, $team_id, $amount, $scene){
        $keyRecordM = new \addons\fomo\model\KeyRecord();
        $balanceM = new \addons\member\model\Balance();
        $rewardM = new \addons\fomo\model\RewardRecord();
        //查询指定游戏,指定战队成员拥有key的所有user
        $record_list = $keyRecordM->getRecordWithOutUserID($user_id, $game_id, $team_id);
        if(!empty($record_list)){
            $team_total_key = $keyRecordM->getTotalByGameAndTeamID($game_id, $team_id);
            foreach($record_list as $k => $record){
                $user_id = $record['user_id'];
                $key_num = $record['key_num'];
                $rate = $this->getUserRate($team_total_key, $key_num);
                $_amount = $amount * $rate;
                //添加余额, 添加分红记录
                $balance = $balanceM->getBalanceByCoinID($user_id, $coin_id);
                $balance = $balanceM->updateBalance($user_id, $_amount, $coin_id, true);
                if($balance != false){
                    $before_amount = $balance['before_amount'];
                    $after_amount = $balance['amount'];
                    $type = 0; //奖励类型 0=投注分红，1=胜利战队分红，2=胜利者分红，3=邀请分红
                    $remark = 'f3d投注分红';
                    $rewardM->addRecord($user_id, $coin_id, $before_amount, $_amount, $after_amount, $type, $game_id,$remark);
                }
            }
        }
        return true;
    }
    
    /**
     * 发放P3d分红 
     * @param type $coin_id
     * @param type $amount
     * @param type $game_id
     * @param type $scene 场景id 场景:0=p3d购买，1=f3d投注分配，2=f3d开奖分配'
     * @return boolean
     */
    private function sendP3d($user_id,$coin_id, $amount, $game_id, $scene){
        $tokenRecordM = new \addons\fomo\model\TokenRecord();
        $balanceM = new \addons\member\model\Balance();
        $rewardM = new \addons\fomo\model\RewardRecord();
        $total_amount = $tokenRecordM->getTotalToken(); //p3d总额
        $filter = '';
        if($scene == 0){
            //场景:0=p3d购买
            $filter = 'user_id !='.$user_id;
        }
        $user_list = $tokenRecordM->getDataList(-1,-1,$filter,'id,user_id,token','id asc');
        if(!empty($user_list)){
            foreach($user_list as $k => $user){
                if($user['token'] <= 0){
                    continue;
                }
                $user_id = $user['user_id'];
                $rate = $this->getUserRate($total_amount, $user['token']);
                $_amount = $amount * $rate; //所得分红
                //添加余额, 添加分红记录
                $balance = $balanceM->getBalanceByCoinID($user_id, $coin_id);
                $balance = $balanceM->updateBalance($user_id, $_amount, $coin_id, true);
                if($balance != false){
                    $before_amount = $balance['before_amount'];
                    $after_amount = $balance['amount'];
                    $type = 0; //奖励类型 0=投注分红，1=胜利战队分红，2=胜利者分红，3=邀请分红
                    $remark  = 'p3d投注分红';
                    $rewardM->addRecord($user_id, $coin_id, $before_amount, $_amount, $after_amount, $type, $game_id,$remark);
                }
            }
        }
        return true;
    }
    
    
    /**
     * 计算用户所拥有的key/token 数量占全部的百分比
     * @param type $total_amount
     * @param type $amount
     * @return type
     */
    private function getUserRate($total_amount, $amount){
        return $amount / $total_amount;
    }
    
}
