<?php

namespace addons\resources\model;

/**
 * 资源信息
 */
class Resources extends \web\common\model\BaseModel {

    protected function _initialize() {
        $this->tableName = 'resources';
    }

    /**
     * 获取列表数据
     * @param type $offset 开始位置
     * @param type $length 每页数量
     * @param type $filter 过滤条件
     * @param type $fields 字段信息
     * @param type $order 排序     
     * @return type
     */
    public function getList($offset, $length, $filter = '', $fields = '', $order = 'id desc') {
        $where = '';
        if (!empty($filter))
            $where = $filter;
        $m = $this;
        if (!empty($fields))
            $m = $m->field($fields);
        if (!empty($order))
            $m = $m->order($order);
        if ($length < 0)
            return $m->where($where)->select();
        else
            return $m->where($where)->limit($offset, $length)->select();
    }

}
