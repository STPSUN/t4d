<?php

namespace web\common\model\sys;

/**
 * 系统OEM信息配置
 */
class SysOemModel extends \web\common\model\Model {

    protected function _initialize() {
        $this->tableName = 'system_oem';
    }

    /**
     * 获取系统配置信息
     * @param type $id
     * @return type
     */
    public function getSysConfig($id) {
        $key = 'tp_'.$this->getTableName() . '_' . $id;
        $data = $this->getGlobalCache($key);
        if (!$data) {
            $data = $this->where(array('id' => $id))->find();
            $this->setGlobalCache($data, $key);
        }        
        return $data;
    }

    /**
     * 清除系统配置信息缓存
     * @param type $id
     */
    public function clearSysConfigCache($id) {
        $key = $this->getTableName() . '_' . $id;
        $this->rmGlobalCache($key);
    }
    

}
