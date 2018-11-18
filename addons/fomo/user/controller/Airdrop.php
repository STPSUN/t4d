<?php

/**
 * Created by PhpStorm.
 * User: SUN
 * Date: 2018/8/20
 * Time: 11:11
 */

namespace addons\fomo\user\controller;

use think\Request;

/**
 * 空投设置
 * Class Airdrop
 * @package addons\fomo\index\controller
 * @author sutignpeng
 */
class Airdrop extends \web\user\controller\AddonUserBase {

    protected $airdropModel;

    public function __construct(Request $request = null) {
        parent::__construct($request);
        $this->airdropModel = new \addons\fomo\model\Airdrop();
    }

    public function index() {
        return $this->fetch();
    }

    public function loadList() {
        $total = $this->airdropModel->getTotal();
        $rows = $this->airdropModel->getDataList($this->getPageIndex(), $this->getPageSize());

        return $this->toDataGrid($total, $rows);
    }

    public function add() {
        if (IS_POST) {
            $data = $_POST;
            if ($data['min'] >= $data['max']) {
                return $this->failData('最大数量必须大于最小数量');
            }
            $data['update_time'] = NOW_DATETIME;
            $res = $this->airdropModel->save($data);
            if ($res > 0)
                return $this->successData();
            else
                return $this->failData('失败');
        }else {
            $this->assign('id', 0);
            return $this->fetch('add');
        }
    }

    public function del() {
        $id = $this->_post('id');
        if (empty($id)) {
            return $this->failData('删除失败,参数有误');
        }

        try {
            $res = $this->airdropModel->deleteData($id);
            if ($res > 0)
                return $this->successData();
            else
                return $this->failData('删除失败');
        } catch (\Exception $e) {
            return $this->failData($e->getMessage());
        }
    }

    public function edit() {
        $id = $this->_get('id');
        if (IS_POST) {
            $data = $_POST;

            if ($data['min'] >= $data['max']) {
                return $this->failData('最大数量必须大于最小数量');
            }

            $data['update_time'] = NOW_DATETIME;
            $res = $this->airdropModel->save($data, ['id' => $id]);
            if ($res > 0) {
                return $this->successData();
            } else {
                return $this->failData('编辑失败');
            }
        } else {
            $this->assign('id', $id);
            $this->setLoadDataAction('loadData');
            return $this->fetch('edit');
        }
    }

    public function loadData() {
        $id = $this->_get('id');
        $data = $this->airdropModel->getDetail($id);

        return $data;
    }

}
