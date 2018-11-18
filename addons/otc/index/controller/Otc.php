<?php
namespace addons\otc\index\controller;
/**
 * pc首页controller
 */
class Otc extends  \web\index\controller\AddonIndexBase{
    
    public function index()
    {
        $otcM = new \addons\otc\model\OtcOrder();
        $last_price = $otcM->where("status",4)->order("id desc")->value('price');
        $last_order = $otcM->getList(0,10,'status = 4','*','id desc');
        $marketM = new \web\api\model\MarketModel();
        $price = $marketM->getCnyRateByCoinId(1);
        $this->assign("price", $price);
        $this->assign("last_price", $last_price ? $last_price :0);
        $this->assign("last_order", $last_order );
        $this->assign("detail_url",getURL("putup"));
        $this->assign('title','OTC');
        return $this->fetch();
    }


    public function loadList(){
        $user_id = $this->user_id ? $this->user_id : 0;
        $type = $this->_get('type',0); //0=卖出,1=买入,2=卖出订单,3=买入订单
        $status = $this->_get('status',0); //0 = all 1=close 2=unpaid 3=check pay 4=success
        $coin_id = $this->_get('coin_id',2);
        if(empty($user_id) || ($type < 0) || empty($coin_id) ){
            return $this->failJSON('missing arguments');
        }
        if($type > 4){
            return $this->failJSON('加载列表类型错误');
        }
        $m = new \addons\otc\model\OtcOrder();
        $filter = 'coin_id='.$coin_id;
        $orderby = 'price asc';
        $fields = 'id,username,buy_username,pay_type,type,price,amount,total_amount,pay_amount,status,add_time,FORMAT(price*amount, 3) total_cny,deal_time';
        try{
            switch ($type){
                case 0:
                case 1:
                    $filter .= ' and type='.$type.' and status=0 and user_id!='.$user_id;
                    break;
                case 2:
                    $filter .= ' and user_id='.$user_id;
                    $orderby = 'status asc';
                    break;
                case 3:
                    $filter .= ' and buy_user_id='.$user_id;
                    $orderby = 'status desc';
                    break;
                case 4:
                    $filter .= ' and ( user_id='.$user_id.' or buy_user_id='.$user_id .')';
                    $orderby = 'status desc';
                    break;
            }
            if($status){
                $filter .= ' and status in ('.$status.')';
            }

            $total = $m->getTotal($filter);
            $rows = $m->getList($this->getPageIndex(),$this->getPageSize(),$filter,$fields,$orderby);
            foreach ($rows as &$v)
            {
                $v['deal_time'] = empty($v['deal_time']) ? '' : $v['deal_time'];
            }
            return $this->toDataGrid($total, $rows);
        } catch (\Exception $ex) {
            return $this->failJSON($ex->getMessage());
        }
    }


    /**
     * 获取订单详情
     */
    public function getOrderDetail(){
        $user_id = $this->user_id ? $this->user_id : 0;
        $order_id = $this->_post('order_id');
        if(empty($user_id) || empty($order_id))
            return $this->failJSON('missing arguments');
        $m = new \addons\otc\model\OtcOrder();
        $payM = new \addons\otc\model\PayConfig();
        try{
            $data = $m->getOrderDetail($order_id);
            $data['pay_data'] = $payM->getUserPayDetail($data['user_id'],$data['pay_type']);
            return $this->successJSON($data);
        } catch (\Exception $ex) {
            return $this->failJSON($ex->getMessage());
        }
    }

    /**
     * 提交收款方式
     * @return type
     */
    public function setPayConfig(){
        if(!IS_POST){
            return $this->failJSON('illegal request');
        }
        $user_id = $this->user_id;
        if($user_id <= 0){
            return $this->failJSON('请先登录');
        }
        $type = $this->_post('type');
        $account = $this->_post('account');
        $id = $this->_post('id');
        $name = $this->_post('name');
        $bank_address = $this->_post('bank_address');
        if($user_id <= 0 || empty($type) || empty($account)){
            return $this->failJSON('missing arguments');
        }
        if($type == 3){
            if(empty($name) || empty($bank_address)){
                return $this->failJSON('姓名与开户行地址不能为空');
            }
            $data['bank_address'] = $bank_address;
            $data['name'] = $name;
        }
        try{
            $m = new \addons\otc\model\PayConfig();
            if(!empty($id)){
                $where['user_id'] = $user_id;
                $where['id'] = $id;
                $data['type'] = $type;
                $data['account'] = $account;
                $data['update_time'] = NOW_DATETIME;
                $ret = $m->where($where)->update($data);
            }else{
                $data['user_id'] = $user_id;
                $data['account'] = $account;
                $data['type'] = $type;
                $data['update_time'] = NOW_DATETIME;
                $ret = $m->add($data);
            }
            if($ret > 0){
                return $this->successJSON();
            }else{
                return $this->failJSON('添加或更新数据失败');
            }
        } catch (\Exception $ex) {
            return $this->failJSON($ex->getMessage());
        }
    }


    public function putup(){
        $order_id = $this->_get("id/d");
        $m = new \addons\otc\model\OtcOrder();
        try{
            $payM = new \addons\otc\model\PayConfig();
            $data = $m->getOrderDetail($order_id);

            $data['total_cny'] = bcmul($data['price'], $data['amount'], 2);
            $data['amount'] = bcmul($data['amount'], 1, 5);
            $data['pay_data'] = $payM->getUserPayDetail($data['user_id'],$data['pay_type']);
        } catch (\Exception $ex) {
            return $this->failJSON($ex->getMessage());
        }
        $this->assign("detail",$data);

        $this->assign('title','OTC');
        return $this->fetch();
    }

    public function orderDetail(){
        $order_id = $this->_get("id/d");
        $m = new \addons\otc\model\OtcOrder();
        try{
            $payM = new \addons\otc\model\PayConfig();
            $data = $m->getOrderDetail($order_id);

            $data['total_cny'] = bcmul($data['price'], $data['amount'], 2);
            $data['amount'] = bcmul($data['amount'], 1, 5);
            $data['pay_data'] = $payM->getUserPay($data['user_id']);
        } catch (\Exception $ex) {
            return $this->failJSON($ex->getMessage());
        }
        $this->assign("detail",$data);

        $this->assign('title','OTC');
        return $this->fetch();
    }


    public function myOrder(){
        $type = $this->_get("type",0);
        $this->assign('type',$type);
        $this->assign('list',[]);
        $this->assign('title','OTC');
        return $this->fetch();
    }




    public function setUniopay(){
        $m = new \addons\otc\model\PayConfig();
        $data = $m->getUserPayDetail($this->user_id,3);
        $this->assign('info',$data);
        $this->assign('id','');
        $this->setLoadDataAction('');
        $this->assign('title','OTC');
        return $this->fetch();
    }
    public function setAlipay(){

        $this->assign('id','');
        $this->setLoadDataAction('');
        $this->assign('title','OTC');
        return $this->fetch();
    }
    public function setWechatpay(){

        $this->assign('id','');
        $this->setLoadDataAction('');
        $this->assign('title','OTC');
        return $this->fetch();
    }
    public function sold(){

        $maketM = new \web\api\model\MarketModel();
        $rate = $maketM->getCnyRateByCoinId(1);
        $this->assign("rate",$rate);
        $this->assign('id','');
        $this->setLoadDataAction('');
        $this->assign('title','OTC');
        return $this->fetch();
    }
    public function withdraw(){

        $this->assign('id','');
        $this->setLoadDataAction('');
        $this->assign('title','OTC');
        return $this->fetch();
    }


}
