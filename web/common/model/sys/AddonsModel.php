<?php

namespace web\common\model\sys;

/**
 * 系统插件
 */
class AddonsModel extends \web\common\model\Model {

    protected function _initialize() {
        $this->tableName = 'sys_addons';
    }

    /**
     * 获取插件名称列表。
     * @param type $where
     * @return type
     */
    public function getAddonsName($where) {
        return $this->where($where)->column('id,name');
    }

    /**
     * 获取插件配置信息
     * @param type $name
     * @return type
     */
    public function getConfigData($name) {
        $where = array('name' => $name, 'status' => 1);
        return $this->where($where)->column('config');
    }

    /**
     * 获取微信插件列表     
     * @return type
     */
    public function getWeiXinAddonList() {
        $sql = 'select name from ' . $this->getTableName() . ' where type=1 and status=1';
        return $this->query($sql);
    }

    /**
     * 获取微信插件列表
     * @param type $addons
     * @return type
     */
    public function getWeiXinAddons($addons) {
        $sql = 'select name from ' . $this->getTableName() . ' where type=1 and status=1 and find_in_set(name,\'' . $addons . '\')';
        return $this->query($sql);
    }

    /**
     * 获取用户的插件
     * @param type $token
     * @param type $is_admin
     * @param string $addon_ids
     * @return type
     */
    public function getAddons($token, $is_admin, $addon_ids) {
        if (empty($addon_ids))
            $addon_ids = '0';
        $mp = new \web\common\model\weixin\mp($token);
        $sql = 'select b.name,title,description from (';
        $sql .= 'select enable_addons from ' . $mp->getTableName() . ' where token=\'' . $token . '\') a,' . $this->getTableName() . ' b';
        if ($is_admin != '1')
            $sql .= ',(select distinct addon from tp_addon_category where id in(' . $addon_ids . ') and addon<>\'\') c';
        $sql .= ' where status=1 and is_system=0 and is_module=1 and FIND_IN_SET(name,a.enable_addons)';
        if ($is_admin != '1')
            $sql .= ' and FIND_IN_SET(name,c.addon)';
        return $this->query($sql);
    }

    /**
     * 获取插件列表
     * @param type $ids
     * @param type $cate_id
     * @return type
     */
    public function getList($ids, $cate_id) {
        $where = array('cate_id' => $cate_id, '_string' => 'id in(' . $ids . ' )');
        return $this->where($where)->select();
    }

}
