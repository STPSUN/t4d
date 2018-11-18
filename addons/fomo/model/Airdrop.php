<?php
/**
 * Created by PhpStorm.
 * User: SUN
 * Date: 2018/8/20
 * Time: 11:32
 */

namespace addons\fomo\model;


use phpDocumentor\Reflection\Types\Null_;
use think\Db;

class Airdrop extends \web\common\model\BaseModel
{
    protected function _initialize()
    {
        $this->tableName = 'fomo_airdrop';
    }

    public function insert($data)
    {
        $min = empty($data['min']) ? NULL : $data['min'];
        $max = $data['max'];
        $rate = $data['rate'];
        $update_time = NOW_DATETIME;

        $res = Db::execute('insert into tp_fomo_airdrop (min,max,rate,update_time) VALUE ("'.$min.'","'.$max.'","'.$rate.'","'.$update_time.'")');

        return $res;
    }
    
    public function getRuleOrderBy($orderby = 'min asc'){
        return $this->order($orderby)->find();
    }

}


















