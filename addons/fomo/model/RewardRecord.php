<?php

namespace addons\fomo\model;

/**
 * Description of RewardRecord
 *
 * @author shilinqing
 */
class RewardRecord extends \web\common\model\BaseModel {

    protected function _initialize() {
        $this->tableName = 'fomo_reward_record';
    }

    /**
     * 添加记录
     * @param type $user_id
     * @param type $coin_id
     * @param type $before_amount
     * @param type $amount
     * @param type $after_amount
     * @param type $type
     * @param type $game_id
     */
    public function addRecord($user_id, $coin_id, $before_amount, $amount, $after_amount, $type, $game_id, $remark = '') {
        $data['user_id'] = $user_id;
        $data['coin_id'] = $coin_id;
        $data['amount'] = $amount;
        $data['before_amount'] = $before_amount;
        $data['after_amount'] = $after_amount;
        $data['type'] = $type;
        $data['game_id'] = $game_id;
        $data['remark'] = $remark;
        $data['update_time'] = NOW_DATETIME;

        return $this->add($data);
    }



    public function getUserTotal($user_id, $coin_id, $game_id = 0) {
        $where['user_id'] = $user_id;
        $where['coin_id'] = $coin_id;
        if ($game_id != 0) {
            $where['game_id'] = $game_id;
        }
        $data = $this->where($where)->sum('amount');
        if (empty($data)) {
            return 0;
        }
        return $data;
    }

    /**
     * 根据奖励类型获取总数
     * @param type $user_id
     * @param type $type
     * @return type
     */
    public function getTotalByType($user_id, $coin_id, $type = '3') {
        $where['type'] = array('in', $type);
        $where['user_id'] = $user_id;
        $where['coin_id'] = $coin_id;
        $data = $this->where($where)->sum('amount');
        if (empty($data)) {
            return 0;
        }
        return $data;
    }

    /**
     * 获取奖励总数
     */
    public function getTotalAmount($user_id, $coin_id,$game_id) {
        $where['user_id'] = $user_id;
        $where['coin_id'] = $coin_id;
        $where['game_id'] = $game_id;
        $data = $this->where($where)->sum('amount');
        if (empty($data)) {
            return 0;
        }
        return $data;
    }

    public function getTotalAmountAndLimit($user_id, $coin_id,$game_id,$key_record_id) {
        $sql = "SELECT SUM(r.amount)as amount,k.limit_amount "
            . " FROM tp_fomo_reward_record AS r, tp_fomo_key_record AS k"
            . " WHERE r.user_id = $user_id AND r.coin_id = $coin_id AND r.game_id = $game_id AND k.id = $key_record_id";


//        $where['user_id'] = $user_id;
//        $where['coin_id'] = $coin_id;
//        $where['game_id'] = $game_id;
//        $data = $this->where($where)->sum('amount');
//        if (empty($data)) {
//            return 0;
//        }
        return $this->query($sql);
    }
    
    public function getGameWinner($game_id){
        $m = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.amount, b.username from '.$this->getTableName().' a , '.$m->getTableName().' b where a.user_id=b.id and a.type=2 and a.game_id='.$game_id;
        return $this->query($sql);
    }

    /**
     * 获取列表数据
     * @param type $pageIndex 当前页
     * @param type $pageSize 每页数量
     * @param type $filter 过滤条件
     * @param type $fields 字段信息
     * @param type $order 排序
     * @return type
     */
    public function getDataList($pageIndex = -1, $pageSize = -1, $filter = '', $fields = '*', $order = 'id desc')
    {
        $MemberAccountModel = new \addons\member\model\MemberAccountModel();
        $sql = "select c.{$fields}, d.username from {$this->getTableName()} c left join {$MemberAccountModel->getTableName()} d on c.user_id = d.id";
        if (!empty($filter))
            $sql .= ' where ' . $filter;
        $m = new \addons\config\model\Coins;
        $sql = "select a.*,b.coin_name from ({$sql}) a left join {$m->getTableName()} b  on a.coin_id = b.id";
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }

    /**
     * 获取记录总数
     * @param type $filter
     * @return int
     */
    public function getTotal($filter = '')
    {
        $MemberAccountModel = new \addons\member\model\MemberAccountModel();

        $sql = "select count(*) c from {$this->getTableName()} c left join {$MemberAccountModel->getTableName()} d on c.user_id = d.id";
        if (!empty($filter))
            $sql .= ' where ' . $filter;
        $result = $this->query($sql);
        if (count($result) > 0)
            return intval($result[0]['c']);
        else
            return 0;
    }


    public function getCountTotal($filter = '') {
        $u = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.*,u.username from '.$this->getTableName().' a ,'.$u->getTableName().' u where a.user_id=u.id';
        if($filter!=''){
            $sql = 'select sum(amount) as count_total from ('.$sql.') as tab where '.$filter;
        }
        $count = $this->query($sql);
        return $count ? $count[0]['count_total'] : 0;
    }

}
