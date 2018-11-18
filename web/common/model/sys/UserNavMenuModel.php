<?php

namespace web\common\model\sys;

/**
 * user模块后台导航菜单
 */
class UserNavMenuModel extends \web\common\model\Model {

    protected function _initialize() {
        $this->tableName = 'user_nav_menu';
    }

    /**
     * 获取类目列表。
     * @return type
     */
    public function getList() {
        return $this->order('order_index asc,id asc')->select();
    }
    
    
    public function getMenuByIDS($ids){
        $where['id'] = array('in',$ids);
        return $this->field('id,title,addon,controller,action')->where($where)->select();
    }

    /**
     * 获取类目 id pid title 列表
     * @return type
     */
    public function getAddonList() {
        $table = $this->getTableName();
        $sql = 'select id,pid,title name from ' . $table . ' where allow=1 ';
        $data = $this->query($sql);
        return $data;
    }

    /**
     * 获取类目 id pid title 列表
     * @return type
     */
    public function getAddonCount() {
        return $this->where('allow=1')->count('id');
    }

    /**
     * 
     * @param type $addon
     * @return int
     */
    public function getCategoryPID($addon, $controller) {
        $table = $this->getTableName();
        $sql = 'select pid from ' . $table . ' a where a.addon=\'' . $addon . '\' and a.controller=\'' . $controller . '\' limit 1';
        $reult = $this->query($sql);
        if (!empty($reult) && count($reult) > 0)
            return intval($reult[0]['pid']);
        else
            return 0;
    }

    /**
     * 
     * @param type $addon
     * @return int
     */
    public function getCategoryID($addon, $controller) {
        $table = $this->getTableName();
        $sql = 'select id from ' . $table . ' a where a.addon=\'' . $addon . '\' and a.controller=\'' . $controller . '\' limit 1';
        $reult = $this->query($sql);
        if (!empty($reult) && count($reult) > 0)
            return intval($reult[0]['id']);
        else
            return 0;
    }

    /**
     * 获取栏目下第一个插件的第一个连接。
     * @param type $token     
     * @param type $pid
     * @param type $is_admin
     * @param type $popedom_ids
     * @return type
     */
    public function getFirstCategory($token, $pid) {
        $m = new \web\common\model\user\CompanyModel();
        $sql = 'select a.id,b.addons from ' . $this->getTableName() . ' a,' . $m->getTableName() . ' b where b.token=\'' . $token . '\' and a.pid=' . $pid . ' and a.allow=1 and find_in_set(a.addon,b.addons) order by a.order_index asc,a.id asc';
        $sql = 'select addon,controller,action from ' . $this->getTableName() . ' a,(' . $sql . ') b where a.pid=b.id and a.allow=1 and find_in_set(a.addon,b.addons)';
        $sql .= ' order by a.order_index asc,a.id asc limit 1';
        $result = $this->query($sql);
        if ($result != null && count($result) > 0)
            return $result[0];
        else
            return null;
    }

    /**
     * 获取插件菜单信息
     * @param type $addon
     * @param type $controller     
     */
    public function getCategory($addon, $controller) {
        $controller = explode('.', $controller)[0];
        $sql = 'select b.title category,a.title,a.addon,a.controller,a.action from ' . $this->getTableName() . ' a,' . $this->getTableName() . ' b
where a.addon=\'' . $addon . '\' and a.controller=\'' . $controller . '\' and a.pid=b.id';
        $result = $this->query($sql);
        if (!empty($result))
            return $result[0];
        else
            return array();
    }

    /**
     * 获取菜单
     * @param type $pid
     * @param type $popedom_ids
     * @return type
     */
    public function getCategoryParentMenu($pid) {
        $f = 'pid=' . $pid;
        $sql = 'select id,title,addon,controller,action,target,dialog_width,dialog_height from ' . $this->getTableName() . ' where allow=1 and ' . $f . ' order by order_index asc,id asc';
        return $this->query($sql);
    }

    /**
     * 获取分类菜单。
     * @param type $company_id
     * @param type $brand_id
     * @param type $pid
     * @param type $popedom_ids
     * @return type
     */
    public function getCategoryMenu($pid) {
        $f = ' pid=' . $pid;
        $sql = 'select a.id,a.title,a.addon,a.controller,a.action from ' . $this->getTableName() . ' a where ' . $f . ' and a.allow=1 order by a.order_index asc,a.id asc';
        return $this->query($sql);
    }

    /**
     * 获取菜单     
     * @param type $pid
     * @param type $popedom_ids
     * @return type
     */
    public function getMenu($pid) {
        $sql = 'select id,title from ' . $this->getTableName() . ' where pid=' . $pid . ' and allow=1 and ' . $filter . ' order by order_index asc,id asc';
        return $this->query($sql);
    }


    /**
     * 获取插件菜单信息
     * @param type $addon
     * @param type $controller     
     */
    public function getMenuData($addon, $controller) {
        $where = array('addon' => $addon, 'controller' => $controller);
        return $this->field('id,title')->where($where)->find();
    }

    /**
     * 获取ID 
     * @param type $addon
     * @param type $controller     
     * @return int
     */
    public function getNavID($addon, $controller) {
        $controller = \think\Loader::parseName($controller);
        $where = array('addon' => $addon, 'controller' => $controller);
        $result = $this->field('id')->where($where)->find();
        if (!empty($result))
            return $result['id'];
        else
            return 0;
    }
    
    /**
     * 获取权限树
     * @param type $user_type
     * @return string
     */
    public function getPopedomTree() {
        $sql = 'select id,pid pId,title name from ' . $this->getTableName() . ' order by order_index asc,id asc';
        return $this->query($sql);
    }
   
    public function getControllerByIDS($ids){
        $where['id'] = array('in',$ids);
        $where['pid'] = array('<>',0);
        $data = $this->where($where)->field('group_concat(concat_ws("/",addon,controller))as plugin')->select();
        if(!empty($data)){
            return $data[0]['plugin'];
        }else{
            return '';
        }
        
    }
   
}
