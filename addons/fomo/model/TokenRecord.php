<?php

namespace addons\fomo\model;

/**
 * @author shilinqing
 */
class TokenRecord extends \web\common\model\BaseModel{
    
    protected function _initialize() {
        $this->tableName = 'fomo_token_record';
    }
    
        
    public function getList($pageIndex = -1, $pageSize = -1, $filter = '', $order = 'update_time desc') {
        $u = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.*,u.username from '.$this->getTableName().' a ,'.$u->getTableName().' u where a.user_id=u.id';
        if($filter!=''){
            $sql = 'select * from ('.$sql.') as tab where '.$filter;
        }
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }
    
    public function getTotal($filter = '') {
        $u = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.*,u.username from '.$this->getTableName().' a ,'.$u->getTableName().' u where a.user_id=u.id';
        if($filter!=''){
            $sql = 'select count(*) as c from ('.$sql.') as tab where '.$filter;
        }
        $count = $this->query($sql);
        return $count[0]['c'];
    }
    
    public function getCountTotal($filter = '') {
        $u = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.*,u.username from '.$this->getTableName().' a ,'.$u->getTableName().' u where a.user_id=u.id';
        if($filter!=''){
            $sql = 'select sum(token) as count_total from ('.$sql.') as tab where '.$filter;
        }
        $count = $this->query($sql);
        return $count[0]['count_total'];
    }
    
    public function getTotalToken($user_id = -1){
        if($user_id > 0){
            $this->where('user_id='.$user_id);
        }
        $data = $this->sum('token');
        if(empty($data)){
            return 0;
        }
        return $data;
    }
    
    public function getDataByUserID($user_id){
       $where['user_id'] = $user_id;
       return $this->where($where)->find();
    }
    
    /**
     * 
     * @param type $user_id
     * @param type $amount
     * @param type $is_sum 是否为增加 , 默认减少
     */
    public function updateTokenBalance($user_id, $amount ,$is_sum = false ){
        $where['user_id'] = $user_id;
        $data = $this->where($where)->find();
        if(empty($data)){
            $data['user_id'] = $user_id;
            $data['token'] = 0;
            $data['before_token'] = 0;
            
        }
        $data['update_time'] = NOW_DATETIME;
        $data['before_token'] = $data['token'];
        if($is_sum){
            //增加
            $data['token'] = $data['token'] + $amount;
        }else{
            $data['token'] = $data['token'] - $amount;
        }
        $res = $this->save($data);
        if (!$res) {
            return false;
        }
        return $data;
    }
    
        
}
