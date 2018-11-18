<?php

namespace addons\member\user\controller;

class Suggest extends \web\user\controller\AddonUserBase{

    //用户反馈
    public function index(){
        return $this->fetch();
    }

    //反馈详情
    public function edit(){
        if($this->request->isPost()){
            $id = $this->_post("id/d");
            $content = $this->_post("back_content");
            if($content && $id){
                try{
                    $m = new \addons\member\model\Suggest();

                    $data['id'] = $id;
                    $data['back_content'] = $content;
                    $data['update_time'] = NOW_DATETIME;
                    $m->save($data);
                    return $this->successData();
                }catch(\Exception $ex){
                    $this->failData($ex->getMessage());
                }
            }
            return $this->successData();
        }
        $id = $this->_get('id/d');
        $m = new \addons\member\model\Suggest();
        $data = $m->getDetail($id);
        $mdata = $m->getByPid($id);
        $back_content = $mdata ? $mdata['content'] : "";
        $this->assign('id', $id);
        $this->assign('pic', $data['pic']);
        $this->assign('back_content', $back_content);
        $this->setLoadDataAction('loadSuggest');
        return $this->fetch();
    }

    public function loadSuggest(){
        $id = $this->_get('id');
        $m = new \addons\member\model\Suggest();
        $data = $m->getDetail($id);
        return $data;
    }


    public function loadList(){
        $filter = ' pid=0';
        $m = new \addons\member\model\Suggest();
        $total = $m->getTotal($filter);
        $rows = $m->getList($this->getPageIndex(), $this->getPageSize(), $filter, '', $this->getOrderBy('id desc'));
        foreach($rows as &$val){
            $val['status'] = 0;
            if($val['back_content']){
                $val['status'] = 1;
            }
        }
        return $this->toDataGrid($total, $rows);
    }

}