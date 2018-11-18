<?php

namespace addons\financing\model;
/**
 * Description of Product
 * 理财产品
 * @author shilinqing
 */
class Product extends \web\common\model\BaseModel{
    
    protected function _initialize() {
        $this->tableName = 'product';
    }
    
    /**
     * 获取理财产品列表(带币种名称)
     * @param type $pageIndex
     * @param type $pageSize
     * @param type $filter
     * @param type $order
     * @return type
     */
    public function getList($pageIndex = -1, $pageSize = -1, $filter = '', $order = 'id asc') {
        $m = new \addons\config\model\Coins();
        $sql = 'select a.*,b.coin_name from ' . $this->getTableName() . ' a,'.$m->getTableName().' b where a.coin_id=b.id';
        if (!empty($filter))
            $sql .=  ' and '.$filter;
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }
    
    /**
     * 获取指定币种理财产品
     * @param type $coin_id
     * @return type
     */
    public function getListByCoinID($coin_id){
        $where['coin_id'] = $coin_id;
        return $this->where($where)->select();
    }
    
    /**
     * 获取理财产品详情
     * @param type $id
     * @return string
     */
    public function getDetailByID($id){
        $m = new \addons\config\model\Coins();
        $sql = 'select b.coin_name,a.id,a.duration,a.min,a.max,a.start_rate_date,a.end_rate_date,a.title,a.total_stock,a.stock';
        $sql .= ' from '.$this->getTableName().' a ,'.$m->getTableName().' b where a.id='.$id.' and a.coin_id=b.id';
        $data = $this->query($sql);
        if(!empty($data)){
            return $data[0];
        }else{
            return '';
        }
    }
}
