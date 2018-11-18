<?php

namespace addons\fomo\model;

/**
 * Description of TeamTotal
 *
 * @author shilinqing
 */
class TeamTotal extends \web\common\model\BaseModel{
    
    protected function _initialize() {
        $this->tableName = 'fomo_team_total';
    }
    
    public function getTotalByGameId($game_id){
        $where['game_id'] = $game_id;
        return $this->where($where)->select();
    }
    
    public function getTotalByTeamAndGameID($game_id,$team_id){
        $where['game_id'] = $game_id;
        $where['team_id'] = $team_id;
        $data = $this->where($where)->find();
        if (empty($data)) {
            return 0;
        }
        return $data['total_amount'];
    }
    
    /**
     * 根据条件获取数据
     * @param type $team_id
     * @param type $game_id
     * @param type $coin_id
     * @return type
     */
    public function getDataByWhere($team_id,$game_id,$coin_id){
        $where['team_id'] = $team_id;
        $where['game_id'] = $game_id;
//        $where['coin_id'] = $coin_id;
        $data =  $this->where($where)->find();
        if(!$data){
            $data = [
                'team_id' => $team_id,
                'game_id' => $game_id,
                'total_amount' => 0,
                'before_total_amount' => 0,
                'update_time' => NOW_DATETIME,
            ];
            $this->save($data);
        }
        return $data;
    }
    
}
