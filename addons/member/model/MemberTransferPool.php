<?php
/**
 * Created by PhpStorm.
 * User: stp
 * Date: 2018/12/25
 * Time: 11:02
 */

namespace addons\member\model;


class MemberTransferPool extends \web\common\model\BaseModel
{
    public function _initialize()
    {
        $this->tableName = 'member_transfer_pool';
    }

}