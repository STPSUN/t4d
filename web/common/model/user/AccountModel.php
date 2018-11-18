<?php

namespace web\common\model\user;

/**
 *登录账户信息
 */
class AccountModel extends \web\common\model\Model {

    protected function _initialize() {
        $this->tableName = 'user_account';
    }

    /**
     *  获取数据详情。
     * @param type $company_id
     * @param type $id
     * @return type
     */
    public function getAccountDetail( $id) {
        $where = array('id' => $id);
        return $this->where($where)->find();
    }
    
    public function getList($pageIndex = -1, $pageSize = -1, $filter = '', $order = 'id asc') {
        $m = new \addons\config\model\Role();
        $sql = 'select a.id, a.username,a.lxr,a.mobile,a.create_time,a.update_time,a.is_admin,b.name as role_name from ' . $this->getTableName() .' a left join '.$m->getTableName().' b on a.role_id=b.id';
        if (!empty($filter))
            $sql = 'select * from ('.$sql.') as tab where ' . $filter;
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }

    /**
     * 逻辑删除。
     * @param type $id
     * @return type
     */
    public function deleteLogicAccount( $id) {
        $where = array('id' => $id);
        $data = array('logic_delete' => 1, 'delete_time' => getTime());
        return $this->save($data, $where);
    }

    /**
     * 根据ID获取数据。 
     * @param type $id
     * @return type
     */
    public function getDataByID($id) {
        return $this->where(array('id' => $id))->find();
    }

    /**
     * 获取密码
     * @param type $id
     * @return type
     */
    public function getPassword($id) {
        $result = $this->field('password')->where(array('id' => $id))->find();
        return $result['password'];
    }

    /**
     * 更新密码
     * @param type $id
     * @param type $password
     * @return type
     */
    public function updatePassword($id, $password) {
        $data = array('password' => md5($password));
        return $this->save($data, array('id' => $id));
    }
    
    /**
     * 根据账号和密码获取用户数据
     * @param type $company_id
     * @param type $username
     * @param type $password
     * @return type
     */
    public function getLoginData( $username, $password) {
        $sql = 'select id,role_id,username,is_admin,logic_delete from ' . $this->getTableName() . ' a';
        $sql .= ' where username=\'' . $username . '\' and password=\'' . md5($password) . '\'';
        $result = $this->query($sql);
        if (!empty($result) && count($result) > 0)
            return $result[0];
        else
            return null;
    }
    
    /**
     * 是否拥有指定的功能插件
     * @param type $name 插件名称
     * @return boolean
     */
    public function hasAddon($user_id, $name) {
        $key = 'brand_addons_'.$user_id;
        $data = $this->getCache($key);
        if (empty($data)) {
            $list = $this->query('call sp_brand_addons(' . $brand_id . ')');
            if (count($list) > 0) {
                $data = $list[0][0];
                $this->setCache($data, $key);
            }
        }
        if (!empty($data[$name]))
            return true;
        else
            return false;
    }


}