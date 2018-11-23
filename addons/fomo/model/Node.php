<?php
/**
 * Created by PhpStorm.
 * User: stp
 * Date: 2018/11/20
 * Time: 11:09
 */

namespace addons\fomo\model;


class Node extends \web\common\model\BaseModel
{
    protected function _initialize()
    {
        $this->tableName = 'fomo_node';
    }

    public function getNodeUsers($game_id)
    {
        $data = $this
                ->alias('n')
                ->field('n.user_id')
                ->join('fomo_key_record r','n.user_id = r.user_id and n.game_id = r.game_id','left')
                ->where('n.game_id',$game_id)
                ->select();

        return $data;
    }
}