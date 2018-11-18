<?php

namespace web\common\model\sys;

/**
 * 系统参数。
 */
class SysParameterModel extends \web\common\model\Model {

    protected function _initialize() {
        $this->tableName = 'sys_parameter';
    }

    public function getID($key){
        $where['field_name'] = $key;
        $data = $this->where($where)->field('id')->find();
        if(!empty($data))
            return $data['id'];
        else
            return -1;
    }
    /**
     * 
     * @param type $type 类型(0=平台系统参数，1=品牌系统参数,2=门店系统参数)
     * @return type
     */
    public function getParameterGroup() {
        $list = $this->field('id,pid,title,remark,field_name,control_type,parameter_val')->order('order_index,id asc')->select();
        $node = null;
//        $childer = null;
        foreach ($list as $li) {
            if ($li['pid'] == 0)
                $node[] = $li;
            else
                $childer[] = $li;
        }
//        foreach ($node as $key => $data) {
//            foreach ($childer as $k => $c) {
//                if ($c['pid'] == $data['id']) {
//                    $node[$key]['childer'][] = $c;
//                    unset($childer[$k]);
//                }
//            }
//        }
        return $node;
    }

    /**
     * 获取参数列表。
     * @param type $type
     * @param type $pid
     * @return type
     */
    public function getParameters($type, $pid = 0) {
        $where = array('type' => $type, 'pid' => $pid);
        $list = $this->field('id,pid,title,remark,field_name,control_type,parameter_val')->order('order_index,id asc')->where($where)->select();
        return $list;
    }

    /**
     * 获取数据
     * @param type $type 类型
     * @return type
     */
    public function getParameterData($type) {
        $where = array('type' => $type);
        $list = $this->field('id,field_name,parameter_val')->where($where)->select();
        return $list;
    }

    /**
     * 获取数据根据filed_name 为key
     * @param type $type 类型
     * @return type
     */
    public function getParameterDataByKey($type) {
        $where = array('type' => $type);
        $list = $this->field('id,field_name,parameter_val')->where($where)->select();
        $arr = array();
        foreach($list as $val){
            $arr[$val['field_name']] = $val['parameter_val'];
        }
        return $arr;
    }
    
    public function getValByName($name){
        $where['field_name'] = $name;
        $data = $this->where($where)->field('parameter_val')->find();
        if(!empty($data)){
            return $data['parameter_val'];
        }else{
            return '';
        }
    }

}
