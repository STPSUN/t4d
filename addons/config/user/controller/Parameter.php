<?php

namespace addons\config\user\controller;

class Parameter extends \web\user\controller\AddonUserBase{
    
    public function index() {
        if (IS_POST) {
            $json = $_POST['json'];
            $data = json_decode($json, true);
            $m = new \web\common\model\sys\SysParameterModel();
            foreach ($data as $key => $val) {
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
            $m = new \web\common\model\sys\SysParameterModel();
            $list = $m->getParameterGroup();
            $this->assign('param_list', $list);
            $this->setLoadDataAction('loadData');
            return $this->fetch();
        }
    }

    public function loadData() {
        $m = new \web\common\model\sys\SysParameterModel();
        $data = $m->getDataList();
        return $data;
    }

}
