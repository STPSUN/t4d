<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace addons\fomo\user\controller;

/**
 * Description of Parameter
 * fomo参数配置
 * @author shilinqing
 */
class Parameter extends \web\user\controller\AddonUserBase{
    
        public function index() {
        if (IS_POST) {
            $json = $_POST['json'];
            $data = json_decode($json, true);
            $m = new \addons\fomo\model\Conf();
            foreach ($data as $key => $val) {
                if($key == 'invite_rate'){
                    if (!preg_match('/^[\d,]*$/i', $val)) {
                        return $this->failData('请输入层级费率，用逗号隔开');
                    }
                }
                $id = $m->getID($key);
                if ($id > 0) {
                    $model['id'] = $id;
                    $model['parameter_val'] = $val;
                }
                $ret = $m->save($model);
            }
            return $this->successData();
        } else {
            $this->assign('id', '1');
            $m = new \addons\fomo\model\Conf();
            $list = $m->getDataList(-1,-1,'','','id asc');
            $this->assign('param_list', $list);
            $this->setLoadDataAction('loadData');
            return $this->fetch();
        }
        
    }
    
    public function loadData() {
        $m = new \addons\fomo\model\Conf();
        $data = $m->getDataList(-1,-1,'','','id asc');
        return $data;
    }
    
}
