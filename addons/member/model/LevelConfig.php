<?php

namespace addons\member\model;

class LevelConfig extends \web\common\model\BaseModel {

    protected function _initialize() {
        $this->tableName = 'member_level_config';
    }

    /**
     * score    积分数量
     * @param type $score
     */
    public function getLevelID($score) {
        $where['score_total'] = array('<=', $score);
        $res = $this->where($where)->order('score_total desc')->find();
        return $res['id'];
    }

    /**
     * 根据user_id获取用户等级
     * @param type $user_id
     * @return type
     */
    public function getUserLevelName($user_id) {
        $m = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.level_name from ' . $this->getTableName() . ' a inner join ' . $m->getTableName() . ' b on a.id=b.level_id and b.id=' . $user_id;
        return $this->getFieldValue($sql, 'level_name');
    }

}
