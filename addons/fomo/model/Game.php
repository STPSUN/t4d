<?php

namespace addons\fomo\model;

/**
 * @author shilinqing
 */
class Game extends \web\common\model\BaseModel{
    
    protected function _initialize() {
        $this->tableName = 'fomo_game';
    }
    
    public function getList($pageIndex = -1, $pageSize = -1, $filter = '', $order = 'id desc') {
        $m = new \addons\config\model\Coins();
        $sql = 'select a.*,b.coin_name from ' . $this->getTableName() . ' a,'.$m->getTableName().' b where a.coin_id=b.id';
        if (!empty($filter))
            $sql = 'select * from ('.$sql.') as t where '.$filter;
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }
    
    public function startGame($id,$hour){
        $where['id'] = $id;
        $where['status'] = 0;
        $data['status'] = 1;
        $data['start_time'] = time();
        $data['end_game_time'] = strtotime("+".$hour." minute");
        return $this->where($where)->update($data);
    }
    
    public function getRunGame($fields='id,status,start_time,coin_id,coin_name,winner_rate,team_rate,fund_rate,drop_total_amount,total_buy_seconds,total_amount,pool_total_amount,release_total_amount,end_game_time'){
        $m = new \addons\config\model\Coins();
        $sql = 'select a.* ,b.coin_name from '.$this->getTableName().' a ,'.$m->getTableName().' b where a.status=1 and a.coin_id=b.id';
        if($fields!=''){
            $sql = 'select '.$fields.' from ('.$sql.') as tab';
        }
        $sql.=' limit 0,1';
        return $this->query($sql);
    }
    
    public function getLastEndGame($fields='id,status,coin_id,coin_name,winner_rate,team_rate,fund_rate,total_buy_seconds,total_amount,pool_total_amount,release_total_amount,end_game_time'){
        $m = new \addons\config\model\Coins();
        $sql = 'select a.* ,b.coin_name from '.$this->getTableName().' a ,'.$m->getTableName().' b where a.status=2 and a.coin_id=b.id order by end_game_time desc';
        if($fields!=''){
            $sql = 'select '.$fields.' from ('.$sql.') as tab';
        }
        $sql.=' limit 0,1';
        return $this->query($sql);
    }
}
