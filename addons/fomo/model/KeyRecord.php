<?php

namespace addons\fomo\model;

/**
 * @author shilinqing
 */
class KeyRecord extends \web\common\model\BaseModel{
    
    protected function _initialize() {
        $this->tableName = 'fomo_key_record';
    }

    public function getList($pageIndex = -1, $pageSize = -1, $filter = '', $order = 'update_time desc') {
        $g = new \addons\fomo\model\Game();
        $t = new \addons\fomo\model\Team();
        $u = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.*,g.name as game_name,g.status as status2,u.username from '.$this->getTableName().' a ,'.$g->getTableName().' g,'.$u->getTableName().' u where a.user_id=u.id and a.game_id=g.id';
        if($filter!=''){
            $sql = 'select * from ('.$sql.') as tab where '.$filter;
        }
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }
    
    public function getList2($pageIndex = -1, $pageSize = -1, $filter = '', $order = 'update_time desc') {
        $g = new \addons\fomo\model\Game();
        $t = new \addons\fomo\model\Team();
        $u = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.*,g.name as game_name,g.status,t.name as team_name,u.username from '.$this->getTableName().' a ,'.$g->getTableName().' g,'.$t->getTableName().' t,'.$u->getTableName().' u where a.user_id=u.id and a.game_id=g.id and a.team_id=t.id';
        if($filter!=''){
            $sql = 'select * from ('.$sql.') as tab where '.$filter;
        }
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }
    
    public function getTotal($filter = '') {
        $g = new \addons\fomo\model\Game();
        $t = new \addons\fomo\model\Team();
        $u = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.*,g.name as game_name,g.status as status2,t.name as team_name,u.username from '.$this->getTableName().' a ,'.$g->getTableName().' g,'.$t->getTableName().' t,'.$u->getTableName().' u where a.user_id=u.id and a.game_id=g.id and a.team_id=t.id';
        if($filter!=''){
            $sql = 'select count(*) as c from ('.$sql.') as tab where '.$filter;
        }
        $count = $this->query($sql);
        return $count[0]['c'];
    }
    
    public function getCountTotal($filter = '') {
        $u = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.*,u.username from '.$this->getTableName().' a ,'.$u->getTableName().' u where a.user_id=u.id';
        if($filter!=''){
            $sql = 'select sum(key_num) as count_total from ('.$sql.') as tab where '.$filter;
        }
        $count = $this->query($sql);
        return $count[0]['count_total'];
    }

    
    public function saveUserKey($user_id,$game_id, $key_num,$limit_amount){
        $where['user_id'] = $user_id;
//        $where['team_id'] = $team_id;
        $where['game_id'] = $game_id;
        $data = $this->where($where)->find();
        if(!empty($data)){
            $data['before_num'] = $data['key_num'];
            $data['key_num'] = $data['key_num'] + $key_num;
            $data['limit_amount'] = $data['limit_amount'] + $limit_amount;
            $data['update_time'] = NOW_DATETIME;
            $data['status'] = 1;
            return $this->save($data);
        }else{
//            $data['team_id'] = $team_id;
            $data['game_id'] = $game_id;
            $data['user_id'] = $user_id;
            $data['key_num'] = $key_num;
            $data['limit_amount'] = $limit_amount;
            $data['update_time'] = NOW_DATETIME;
            return $this->add($data);
        }
    }
    
    /**
     * 获取当场游戏用户key总量
     * @param type $user_id
     * @param type $game_id
     * @return type
     */
    public function getTotalByGameID($user_id,$game_id){
        $where['user_id'] = $user_id;
        $where['game_id'] = $game_id;
        $where['status'] = 1;
        $data = $this->where($where)->sum('key_num');
        if(empty($data)){
            return 0;
        }
        return $data;
    }

    /**
     * 获取user_id 以外的所有指定游戏,战队 - 拥有key的用户
     * @param type $user_id
     * @param type $game_id 
     * @param type $team_id
     */
    public function getRecordWithOutUserID($user_id, $game_id){
        $where['game_id'] = $game_id;
//        $where['team_id'] = $team_id;
        $where['user_id'] = array('<>', $user_id);
        return $this->where($where)->field('id,user_id,key_num')->select();
    }

    public function getRecord($user_id, $game_id){
        $where['game_id'] = $game_id;
        $where['user_id'] = $user_id;
        return $this->where($where)->field('id,user_id,key_num')->select();
    }
    
    /**
     * 获取指定游戏,战队的key的总数
     * @param type $game_id
     * @param type $team_id
     * @return int
     */
    public function getTotalByGameAndTeamID($game_id){
        $where['game_id'] = $game_id;
        $data = $this->where($where)->sum('key_num');
        if (empty($data)) {
            return 0;
        }
        return $data;
    }
    
    /**
     * 获取最后500个投注者
     */
    public function getLastWinner($game_id){
        $data = $this->where(['game_id' => $game_id, 'status' => 1])->field('id,team_id,user_id')->order('update_time desc')->limit(500)->select();
        return $data;
    }

    /**
     * 获取投资最多的100个投注者
     */
    public function getMaxWinner($game_id)
    {
        $data = $this->where(['game_id' => $game_id, 'status' => 1])->field('id,team_id,user_id')->order('key_num desc')->limit(100)->select();
        return $data;
    }

    /**
     * @param $game_id
     * @param $winner_num
     * @return mixed
     */
    public function getWinner($game_id,$winner_num)
    {
        $data = $this->where(['game_id' => $game_id, 'status' => 1])->field('user_id')->order(['winner' => 'asc', 'key_num' => 'desc'])->limit($winner_num)->select();

        return $data;
    }

    public function getWinnerRank($game_id,$winner_num)
    {
        $data = $this->alias('r')
            ->field('u.username')
            ->join('member_account u', 'u.id = r.user_id')
            ->where('r.game_id',$game_id)
            ->order(['r.winner' => 'asc', 'r.key_num' => 'desc'])
            ->limit($winner_num)
            ->select();

        return $data;
    }

    public function getKeyNumTotal($game_id)
    {
        $sql = "SELECT SUM(key_num) AS num FROM tp_fomo_key_record WHERE game_id = $game_id AND status = 1";

        $data = $this->query($sql);
        if(empty($data))
            return 0;

        return $data[0]['num'];
    }
  
}
