<?php

namespace addons\member\model;

/**
 * Description of TradingRecord
 *
 * @author shilinqing
 */
class TradingRecord extends \web\common\model\BaseModel{
    
    protected function _initialize() {
        $this->tableName = 'trading_record';
    }
    
    
    /**
     * 添加记录
     * @param type $user_id 用户id
     * @param type $coin_id 币种id
     * @param type $amount 数量
     * @param type $before_amount  更新前数量
     * @param type $after_amount   更新后数量
     * @param type $type        记录类型：0=转账，1=OTC交易，2=外网转入，3=提现转出，4=购买理财，5=购买矿机,6=后台拨币，7=私募可用，8=私募冻结释放
     * @param type $change_type 0 = 减少 ；1 = 增加
     * @param type $to_user_id      目标用户
     * @param type $to_address      转入地址
     * @param type $from_address    转出地址
     * @param type $remark      备注
     * @param type $game_id      游戏id
     * @param type $team_id      队伍id
     * @return type
     */
    public function addRecord($user_id, $coin_id, $amount, $before_amount, $after_amount, $type, $change_type=0, $to_user_id = 0, $to_address = '', $from_address = '' , $remark='', $game_id = 0, $team_id  = 0){
        $data['user_id'] = $user_id;
        $data['to_user_id'] = $to_user_id;
        $data['type'] = $type; 
        $data['change_type'] = $change_type;
        $data['coin_id'] = $coin_id;
        $data['before_amount'] = $before_amount;
        $data['after_amount'] = $after_amount;
        $data['amount'] = $amount;
        $data['to_address'] = $to_address;
        $data['from_address'] = $from_address;
        $data['game_id'] = $game_id;
        $data['team_id'] = $team_id;
        $data['remark'] = $remark;
        $data['update_time'] = NOW_DATETIME;
        return $this->add($data);
    }

    public function getList($pageIndex = -1, $pageSize = -1, $filter = '',$fileds='*', $order = 'id desc') {
        $coinM = new \addons\config\model\Coins();
        $userM = new \addons\member\model\MemberAccountModel();
        $recordConfM  = new \addons\member\model\RecordConf();
        $sql = 'select a.*,c.coin_name,d.trade_type from '.$this->getTableName() . ' a,'.$coinM->getTableName().' c,'.$recordConfM->getTableName().' d where a.coin_id=c.id and a.type = d.id';
        $sql = 'select s.*,u.username from ('.$sql.') as s left join '.$userM->getTableName().' u on s.user_id=u.id';
        if($filter != ''){
            $sql = 'select '.$fileds.' from ('.$sql.') as tab where '.$filter;
        }
        $sql = 'select t.*,p.username to_username from ('.$sql.') as t left join '.$userM->getTableName().' p on t.to_user_id=p.id';
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }
    
    public function getDataList($pageIndex = -1, $pageSize = -1, $filter = '',$fileds='*', $order = 'id asc') {
        $coinM = new \addons\config\model\Coins();
        $userM = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.*,c.coin_name from '.$this->getTableName() . ' a,'.$coinM->getTableName().' c where a.coin_id=c.id';
        $sql = 'select '.$fileds.' from ('.$sql.') as tab';
        $sql = 'select s.*,u.username,z.username as to_username from ('.$sql.') as s left join '.$userM->getTableName().' u on s.user_id=u.id left join '.$userM->getTableName().' z on s.to_user_id=z.id';
        if($filter != ''){
            $sql = 'select * from ('.$sql.') as y where '.$filter;
        }
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }
    
    /**
     * 获取记录总数
     * @param type $filter
     * @return int
     */
    public function getTotal($filter = '') {
        $userM = new \addons\member\model\MemberAccountModel();
        $sql = 'select count(*) c from ' . $this->getTableName() . ' a,'.$userM->getTableName().' y where 1=1 ';
        if (!empty($filter)){
            $sql .= ' and (' . $filter . ')';
        }
        $result = $this->query($sql);
        if (count($result) > 0)
            return intval($result[0]['c']);
        else
            return 0;
    }
    
}
