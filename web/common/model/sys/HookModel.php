<?php

namespace web\common\model\sys;

/**
 * 钩子
 */
class HookModel extends \web\common\model\Model {

    protected function _initialize() {
        $this->tableName = 'sys_hooks';
    }

    /**
     * 获取勾子名称列表。
     * @return type
     */
    public function getHooksName() {
        return $this->column('name,addons');
    }

    /**
     * 获取列表          
     * @return type
     */
    public function getList() {
        return $this->field('name,addons')->select();
    }

}
