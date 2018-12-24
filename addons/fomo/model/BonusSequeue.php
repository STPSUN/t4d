<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace addons\fomo\model;

/**
 * Description of BonusSequeue
 * fomo游戏发奖mysql队列
 * @author shilinqing
 */
class BonusSequeue extends \web\common\model\BaseModel{
    
    protected function _initialize() {
        $this->tableName = 'fomo_bonus_sequeue';
    }
    
    /**
     * 添加分红数据库队列
     * @param type $user_id
     * @param type $coin_id
     * @param type $amount
     * @param type $type   类型：0=p3d分红,1=f3d分红
     * @param type $scene  0=p3d购买，1=f3d投注分配，2=f3d开奖分配
     * @param type $game_id
     * @param type $team_id
     */
    public function addSequeue($user_id,$coin_id,$amount,$type,$scene,$game_id=0,$team_id=0){
        $data['user_id'] = $user_id;
        $data['coin_id'] = $coin_id;
        $data['team_id'] = $team_id;
        $data['game_id'] = $game_id;
        $data['type'] = $type;
        $data['scene'] = $scene;
        $data['amount'] = $amount;
        $data['status'] = 0;
        $data['update_time'] = NOW_DATETIME;
        return $this->add($data);
    }
    
    public function getUnSendData(){
        $where['status'] = 0;
        return $this->where($where)->field('id,user_id,coin_id,amount,game_id,scene,type,status,update_time')->limit(2000)->select();
    }
}
