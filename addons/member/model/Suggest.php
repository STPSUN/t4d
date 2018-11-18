<?php


namespace addons\member\model;

/**
 * 用户反馈
 */
class Suggest extends \web\common\model\BaseModel {

    protected function _initialize() {
        $this->tableName = 'sys_suggest';
    }

    public function getList($pageIndex = -1, $pageSize = -1, $filter = '', $order = 'id asc') {
        $m = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.*,b.username,b.phone from '.$this->getTableName().' a inner join '.$m->getTableName().' b on a.user_id=b.id';
        $sql = 'select c.*  from ('.$sql.') as c  ';
        if (!empty($filter))
            $sql .=  ' where '.$filter." and user_id >0" ;
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }


    public function getMsgDetail($id,$user_id){
        $data=$this
            ->where("id",$id)
            ->where("user_id",$user_id)
            ->find();
        if(!$data){
            $this->error="留言未找到".$id.$user_id;
            return false;
        }
        return $data;
    }


    public function getByPid($pid){
        $data=$this
            ->where("pid",$pid)
            ->find();
        return $data;
    }

}