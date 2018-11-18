<?php

namespace addons\member\model;

/**
 * sms code 
 *
 * @author shilinqing
 */
class VericodeModel extends \web\common\model\BaseModel {

    protected function _initialize() {
        $this->tableName = 'vericode';
    }

    /**
     * verify the sms code
     * @param type $code  
     * @param type $phone  
     * @param type $type 
     * @return type
     */
    public function VerifyCode($code, $phone, $type = 1) {
        $sql = 'select * from ' . $this->getTableName() . ' where type=' . $type . ' and code=\'' . $code . '\' and phone=\'' . $phone . '\' and pass_time > \'' . NOW_DATETIME . '\' and count <= 4';
        return $this->query($sql);
    }

    /**
     * Verify whether the sms is overdue by type
     * @param type $phone
     * @param type $type 1=register , 2=change login password , 3=change payment password
     */
    public function hasUnpassCode($phone, $type = 1) {
        $where['phone'] = $phone;
        $where['type'] = $type;
        $where['count'] = array('<', 5);
        $where['pass_time'] = array('>', getTime());
        return $this->where($where)->find();
    }

}
