<?php

namespace addons\config\user\controller;

/**
 * Description of RegisterConf
 * 注册字段配置 
 * @author shilinqing
 */
class RegisterConf extends \web\user\controller\AddonUserBase{
    
    public function index(){
        if (IS_POST) {
            $json = $_POST['json'];
            $data = json_decode($json, true);
            $m = new \addons\config\model\RegisterConf();
            foreach ($data as $key => $val) {
                $id = $m->getID($key);
                if ($id > 0) {
                    $model['id'] = $id;
                    $model['status'] = $val;
                }
                $ret = $m->save($model);
            }
            return $this->successData();
        } else {
            $this->assign('id', 1);
            $m = new \addons\config\model\RegisterConf();
            $list = $m->getDataList(-1,-1,'','','id asc');
            $this->assign('list', $list);
            $this->setLoadDataAction('loadData');
            return $this->fetch();
        }
    }
    
    public function loadData() {
        $m = new \addons\config\model\RegisterConf();
        $data = $m->getDataList(-1,-1,'','','id asc');
        return $data;
    }
    
}
