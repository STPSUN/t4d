<?php

namespace addons\fomo\model;

/**
 * @author shilinqing
 */
class Team extends \web\common\model\BaseModel
{

    protected function _initialize()
    {
        $this->tableName = 'fomo_team';
    }

    /**
     * 获取团队配置 默认获取投注配置
     * @param type $id
     * @param type $fields
     */
    public function getConfigByFields($id, $fields = 'pool_rate,f3d_rate,p3d_rate')
    {
        $where['id'] = $id;
        return $this->where($where)->field($fields)->find();
    }

    /**
     * 获取对于信息与投注情况
     * @param type $game_id
     */
    public function getTeamsByGame($game_id, $field = "a.id,name,detail,pic,select_pic,ifnull(total_amount,0) total_amount")
    {
        $sql = "select * from {$this->getTableName()} where status = 1";
        $m = new \addons\fomo\model\TeamTotal;
        $sql = "select {$field} from ({$sql}) a left join " . $m->getTableName() . " b on a.id = b.team_id ";
        $filter = " game_id = {$game_id}";
        if ($filter) {
            $sql .= "and " . $filter;
        }
        $sql .= " group by a.id";
        $result = $this->query($sql);
        return $result;
    }


    /**
     * 获取队伍列表数据
     */
    public function getTeamList($field = "id,name"){
        return $this->field($field)->select();
    }


}
