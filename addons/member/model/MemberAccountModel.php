<?php

namespace addons\member\model;

/**
 * 会员账户信息
 *
 * @author shilinqing
 */
class MemberAccountModel extends \web\common\model\BaseModel
{

    protected function _initialize()
    {
        $this->tableName = 'member_account';
    }
    
    public function getList($pageIndex = -1, $pageSize = -1, $filter = '', $order = 'id asc') {
        $sql = 'select tab.*,c.username as invite_user_name from '.$this->getTableName().' as tab left join '.$this->getTableName().' c on tab.pid=c.id';
        if (!empty($filter))
            $sql =  'select * from ('.$sql.') t where '.$filter;

        $balanceM = new \addons\member\model\Balance();
        $sql = "select a.*,ifnull(b.amount,0) balance from ({$sql}) a left join {$balanceM->getTableName()} b on a.id = b.user_id and coin_id =2";

//        $RewardRecordM = new \addons\fomo\model\RewardRecord();
//        $sql = "select a.*,ifnull(sum(b.amount),0) reward_total from ({$sql}) a left join {$RewardRecordM->getTableName()} b on a.id = b.user_id group by a.id";

//        $gameM = new \addons\fomo\model\Game();
//        $game = $gameM->getRunGame();
//        if(!$game){
//            $game = !empty($gameM->getLastEndGame()) ? $gameM->getLastEndGame() : [ 0=> ['id' => 0] ];
//        }
//        $game = $game[0];
//        $game_id = $game['id'];

//        $keyRecordM = new \addons\fomo\model\KeyRecord();
//        $sql = "select a.*,ifnull(sum(key_num),0) now_key_num from ({$sql}) a left join {$keyRecordM->getTableName()} b on a.id = b.user_id and b.game_id = {$game_id} group by a.id";
//
//        $sql = "select a.*,ifnull(sum(key_num),0) key_total from ({$sql}) a left join {$keyRecordM->getTableName()} b on a.id = b.user_id group by a.id";
//
//        $sql = "select a.*,ifnull(count(game_id),0) game_count from ({$sql}) a left join {$keyRecordM->getTableName()} b on a.id = b.user_id group by a.id";
//
//        $tokenRecordM = new \addons\fomo\model\TokenRecord();
//        $sql = "select a.*,ifnull(token,0) token_total from ({$sql}) a left join {$tokenRecordM->getTableName()} b on a.id = b.user_id";


        $sql = "select a.*,ifnull(count(b.id),0) recommend1,GROUP_CONCAT(b.id) ids  from ({$sql}) a left join {$this->getTableName()} b on a.id = b.pid group by a.id";

        $sql = "select a.*,ifnull(count(b.id),0) recommend2  from ({$sql}) a left join {$this->getTableName()} b on b.pid in(a.ids) group by a.id";
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }
    
    public function getPids($id, $pids=''){
        $pid = $this->getPID($id);
        if(!empty($pid)){
            if($pids != '')
                $pids.=',';
            $pids .= $pid;
            return $this->getPids($pid, $pids);
        }else{
            return $pids;
        }
        
    }

    /**
     * get user login info . (filed phone and wallet_name) one of them has to be filled out
     * @param type $password
     * @param type $phone
     * @param type $wallet_name
     * @param type $fields
     * @return boolean
     */
    public function getLoginData($password, $phone = '', $wallet_name = '', $fields = 'id,username,address,is_auth',$both=false)
    {
        $sql = 'select ' . $fields . ' from ' . $this->getTableName() . ' where logic_delete=0';
        if (!empty($phone)) {
            if($both){
                $sql .= ' and (phone=\'' . $phone . '\' or username=\''.$phone.'\')';
            }else{
                $sql .= ' and phone=\'' . $phone . '\'';
            }
        } else if (!empty($wallet_name)) {
            $sql .= ' and wallet_name=\'' . $wallet_name . '\'';
        } else {
            return false;
        }
        $sql .= ' and password=\'' . md5($password) . '\'';
        $result = $this->query($sql);
        if (!empty($result) && count($result) > 0)
            return $result[0];
        else
            return null;
    }

    /**
     * 短信验证登录时 只有电话号码用来查询用户信息
     * get user login info . filed phone  has to be filled out
     * @param type $phone
     * @param type $fields
     * @return boolean
     */
    public function getLoginDataBySms($phone = '', $fields = 'id,username,address,is_auth')
    {
        $sql = 'select ' . $fields . ' from ' . $this->getTableName() . ' where logic_delete=0';
        if (!empty($phone)) {
            $sql .= ' and phone=\'' . $phone . '\'';
        } else {
            return false;
        }
        $result = $this->query($sql);
        if (!empty($result) && count($result) > 0)
            return $result[0];
        else
            return null;
    }

    /**
     * @param string $field_name
     * @param string $field_value
     * @param $password
     * @param string $fields
     * @return bool
     */
    public function getNewLoginData($field_name = '', $field_value = '', $password, $fields = 'id,username,address,is_auth')
    {
        $where = [
            $field_name => $field_value,
            'logic_delete' => 0,
        ];
        $info = $this->where($where)->field($fields)->find();
        if (!$info) {
            $this->error = "账号或密码错误";
            return false;
        }
        $mdPass = md5(md5($password) . $info['salt']);
        if ($mdPass !== $info['password']) {
            $this->error = "账号或密码错误";
            return false;
        }
        return $info;
    }

    /**
     * verify the user's phone is registered or not
     * @param type $phone
     * @return type
     */
    public function hasRegsterPhone($phone)
    {
        $where['phone'] = $phone;
        return $this->where($where)->count();
    }

    /**
     * verify the user's username is registered or not
     * @param type $phone
     * @return type
     */
    public function hasRegsterUsername($username)
    {
        $where['username'] = $username;
        return $this->where($where)->count();
    }

    /**
     * verify the user's wallet name is registered or not
     * @param type $name
     * @return type
     */
    public function hasRegsterWallet($name)
    {
        $where['wallet_name'] = $name;
        return $this->where($where)->count();
    }

    /**
     * update the user password by phone number
     * @param type $phone
     * @param type $password
     * @param type $type 2=login password ,3 = payment password
     * @return int
     */
    public function updatePassByPhone($phone, $password, $type = 2)
    {
        if ($type == 2) {
            $data['password'] = $password;
        } else if ($type == 3) {
            $data['pay_password'] = $password;
        } else {
            return 0;
        }
        $where['phone'] = $phone;
        return $this->where($where)->update($data);
    }

    /**
     * get user by invite code
     * @param type $invite_code
     * @return int
     */
    public function getUserByInviteCode($invite_code)
    {
        $where['invite_code'] = $invite_code;
        $res = $this->where($where)->field('id')->find();
        if (!empty($res)) {
            return $res['id'];
        } else {
            return 0;
        }
    }

    /**
     * get user parent id
     * @param type $user_id
     * @return type
     */
    public function getPID($id)
    {
        $where['id'] = $id;
        $ret = $this->where($where)->field('pid')->find();
        return $ret['pid'];
    }

    /**
     * get user eth address
     * @param type $user_id
     */
    public function getUserAddress($id)
    {
        $where['id'] = $id;
        $data = $this->where($where)->field('address')->find();
        return $data['address'];
    }

    /**
     * get user by the eth address
     * @param type $address
     * @return int
     */
    public function getUserByAddress($address)
    {
        $where['address'] = $address;
        $data = $this->where($where)->find();
        if (!empty($data)) {
            return $data['id'];
        } else {
            return 0;
        }
    }

    /**
     * get user by the username
     * @param type $address
     * @return int
     */
    public function getUserByUsername($username)
    {
        $where['username'] = $username;
        $data = $this->where($where)->find();
        if (!empty($data)) {
            return $data['id'];
        } else {
            return 0;
        }
    }

    /**
     * get user authentication data
     * @param type $id
     * @param type $fields
     * @return type
     */
    public function getAuthData($id, $fields = 'real_name,card_no,id_face,id_back')
    {
        $where['id'] = $id;
        return $this->where($where)->field($fields)->find();
    }

    /**
     * return user authentication status
     * @param type $id
     */
    public function getAuthByUserID($id)
    {
        $where['id'] = $id;
        $auth = $this->where($where)->field('is_auth')->find();
        return $auth['is_auth'];
    }

    /**
     * change user account frozen status
     * @param type $id
     * @param type $status default 1
     * @return type
     */
    public function changeFrozenStatus($id, $status = 1)
    {
        $where['id'] = $id;
        $data['is_frozen'] = $status;
        return $this->where($where)->update($data);
    }


    /**
     * @param $pid
     * @param string $fields
     * @return array|mixed
     */
    public function getUserListByPid($pid, $fields = "id,username,address,register_time")
    {

        $sql = "select {$fields} from {$this->getTableName()} where pid in ({$pid})";
        $res = $this->query($sql);
        if (!$res) {
            return [];
        }
        $m = new \addons\fomo\model\KeyRecord();
        $tokenM = new \addons\fomo\model\TokenRecord();
        $sql = "select a.*,ifnull(sum(b.key_num),0) key_num from({$sql}) a left join {$m->getTableName()} b on a.id = b.user_id group by a.id";
        $sql = "select d.*,ifnull(c.token,0) token from({$sql}) d left join {$tokenM->getTableName()} c on d.id = c.user_id group by d.id";
        $result = $this->query($sql);
        return $result;
    }


    /**
     * @param $pid int 顶点用户
     * @param $tier int 层级
     * @param array $arr 子集
     * @return array $arr 子集返回
     */
    public function getTeamById($pid, $tier, $arr = [])
    {
        $where = ['pid' => ['in', $pid]];
        $ret = $this->where($where)->field("id,pid,username,{$tier} tier")->select();
        if (!$ret) {
            return $arr;
        }
        $arr = array_merge($arr, $ret);
        $ids = array_column($ret, 'id');
        $ids = join(',', $ids);
        return $this->getTeamById($ids, $tier += 1, $arr);
    }


}
