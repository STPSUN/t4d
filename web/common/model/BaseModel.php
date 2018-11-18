<?php

namespace web\common\model;

class BaseModel extends Model
{

    /**
     * 获取默认缓存KEY
     * @return type
     */
    protected function getCacheKey()
    {
        return $this->getTable() . '_';
    }

    /**
     * 获取条件
     * @param type $data
     * @return type
     */
    protected function getWhere($data = null)
    {
        if (!$data)
            $data = array();
        return $data;
    }

    /**
     * 获取记录总数
     * @param type $filter
     * @return int
     */
    public function getTotal($filter = '')
    {
        $sql = 'select count(*) c from ' . $this->getTableName() . ' where 1=1 ';
        if (!empty($filter))
            $sql .= ' and (' . $filter . ')';
        $result = $this->query($sql);
        if (count($result) > 0)
            return intval($result[0]['c']);
        else
            return 0;
    }

    /**
     * 获取某字段总值
     * @param type $filter
     * @param type $field
     * @return int
     */
    public function getSum($filter = '', $field = "id")
    {
        $sql = 'select sum(' . $field . ') c from ' . $this->getTableName() . ' where 1=1 ';
        if (!empty($filter))
            $sql .= ' and (' . $filter . ')';
        $result = $this->query($sql);
        if (count($result) > 0)
            return intval($result[0]['c']);
        else
            return 0;
    }

    /**
     * 删除数据。
     * @param type $id
     */
    public function deleteData($id)
    {
        $where = $this->getWhere(array('id' => $id));
        return $this->where($where)->delete();
    }

    /**
     * 逻辑删除。
     * @param type $id
     * @return type
     */
    public function deleteLogic($id)
    {
        $where = $this->getWhere(array('id' => $id));
        $data = array('logic_delete' => 1, 'delete_time' => getTime());
        return $this->save($data, $where);
    }

    /**
     * 删除数据。
     * @param type $filter
     */
    public function deleteFilter($filter)
    {
        return $this->where($filter)->delete();
    }

    /**
     * 获取数据详情。
     * @param type $id
     * @return type
     */
    public function getDetail($id, $fields = "*")
    {
        $where = $this->getWhere(array('id' => $id));
        return $this->where($where)->field($fields)->find();
    }

    public function getSingleField($id, $filed = 'username')
    {
        $where['id'] = $id;
        $data = $this->where($where)->field($filed)->find();
        if (!empty($data)) {
            return $data[$filed];
        } else {
            return '';
        }
    }

    /**
     * 获取列表数据
     * @param type $pageIndex 当前页
     * @param type $pageSize 每页数量
     * @param type $filter 过滤条件
     * @param type $fields 字段信息
     * @param type $order 排序
     * @return type
     */
    public function getDataList($pageIndex = -1, $pageSize = -1, $filter = '', $fields = '', $order = '')
    {
        if(!$order){
            $order = $this->getPk(). " desc";
        }
        $sql = 'select ';
        if (!empty($fields))
            $sql .= $fields;
        else
            $sql .= '*';
        $sql .= ' from ' . $this->getTableName();
        if (!empty($filter))
            $sql .= ' where ' . $filter;
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }

    /**
     * 获取数据记录列表。
     * @param type $filter
     * @param string $fields
     * @param type $order
     * @return type
     */
    public function getDatas($filter = null, $fields = '', $order = 'order_index,id asc')
    {
        if (empty($fields))
            $fields = '*';
        if (empty($filter)) {
            return $this->field($fields)->where($this->getWhere())->order($order)->select();
        }
        if (is_array($filter)) {
            $where = array_merge($this->getWhere(), $filter);
            return $this->field($fields)->where($where)->order($order)->select();
        } else {
            $sql = 'select ' . $fields . ' from ' . $this->getTableName();
            $sql .= ' and ' . $filter;
            return $this->query($sql);
        }
    }

    public function getID($key)
    {
        $where['field'] = $key;
        $data = $this->where($where)->field('id')->find();
        if (!empty($data))
            return $data['id'];
        else
            return -1;
    }

    /**
     * 指定值是否在二维数组中
     * @param type $search 搜索的字符
     * @param type $array 二维数组
     * @param type $filed 对应的字段
     * @return boolean
     */
    public function in_multi_array($search, $array, $filed)
    {
        $exist = false;
        foreach ($array as $value) {
            if ($search == $value[$filed]) {
                $exist = true;
                break;
            }
        }
        return $exist;
    }


    /**
     * 获取新的排序值。
     * @param type $code_field
     * @return int
     */
    public function getNewOrderIndex($code_field = 'order_index')
    {
        $result = $this->field('max(' . $code_field . ')+1 order_index')->find();
        if (empty($result))
            return 0;
        else
            return intval($result['order_index']);
    }

}
