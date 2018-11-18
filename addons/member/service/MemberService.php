<?php

namespace addons\member\service;
use \web\common\controller\Controller;
class MemberService extends \web\common\controller\Service {

    public function __construct()
    {
        parent::__construct();
        $this->validater = new \addons\member\validate\MemberValidate();
    }

    private $address = array(); //eth接口返回
    private $ethPass = '';
    /**
     * 用户登陆
     */
    public function login(){
        try{
            $msg = '';
            $param = $this->check_params(__FUNCTION__,$msg);
            if(!$param){
                return $this->failData($msg);
            }
            $username = $param['username'];
            $password = $param['password'];
            $m = new \addons\member\model\MemberAccountModel();
            $field = "id,username,password,salt,address,phone,is_auth,is_frozen";
            $res = $m->getLoginData("username",$username, $password, $field);
            if ($res) {
                if(intval($res['is_frozen']) == 1){
                    return $this->failData('账号已冻结，无法登陆');
                }
                if(!$res['address']){
                    $res['address'] = $this->getEthAddr($res['phone']);
                    $m->save($res);
                }
//                $this->getEthOrders($res['id']);
                $memberData['user_id'] = $res['id'];
                $memberData['user_name'] = $res['username'];
                $memberData['address'] = $res['address'];
                $memberData['phone'] = $res['phone'];
                $memberData['is_auth'] = $res['is_auth'];
                return $this->successData($memberData);
            } else {
                return $this->failData('帐号或密码有误');
            }
        } catch (\Exception $ex) {
            return $this->failData($ex->getMessage());
        }
    }

    /*
     * 获取用户数据
     */
    public function getUserInfo($user_id){
        try{
            $m = new \addons\member\model\MemberAccountModel();
            $field = "id as user_id,username,phone,home_address,city,country,email,register_time,pid,id_stand,is_auth,card_no,real_name";
            $data = $m->getDetail($user_id, $field);
            $data['pname'] = $m->getSingleField($data['pid'],'username');
            $authArr = ['-1'=>'不通过','0'=>"未认证",'1'=>'已认证','2'=>'待认证'];
            $data['auth_name'] = $authArr[$data['is_auth']];
//            -1:不通过，0=未认证，1=已认证，2=待认证
            return $this->successData($data);
        }catch (\Exception $ex){
            return $this->failData($ex->getMessage());
        }

    }


    /**
     * 获取用户资产
     */
    public function getUserAsset($user_id){
        try{
            $this->getEthOrders($user_id);
            $assetM = new \addons\member\model\Balance();
            $frezenM = new \addons\member\model\frozenAssetModel();

            $asset = $assetM->getBalanceByCoinID($user_id,1);
            $pos = $assetM->getBalanceByCoinID($user_id,2);
            $share = $frezenM->getUserAsset($user_id,1);
            $frezen = $frezenM->getUserAsset($user_id, 2);
            $special = $frezenM->getUserAsset($user_id, 3);

            $shareAmount = $share ? rtrim(rtrim($share['stock_amount'], '0'),'.') : 0;
            $frezenAmount = $frezen ? rtrim(rtrim($frezen['stock_amount'], '0'), '.') : 0;
            $mine = [
                ['mine_name'=>'冻结钱包','amount' => $shareAmount + $frezenAmount, 'is_special'=>false],
            ];
            if($special && $special['stock_amount'] >0 ) $mine[] =  ['mine_name'=>'特殊钱包','amount' => $special ? rtrim(rtrim($special['stock_amount'], '0'), '.') : 0, 'is_special'=>true];
            $data['mine'] = $mine;
            $data['asset'] = $asset ? rtrim(rtrim($asset['amount'], '0'), '.') : 0;
            $data['special'] = $special ? rtrim(rtrim($special['stock_amount'], '0'), '.') : 0;
            $data['pos'] = $pos ? rtrim(rtrim($pos['amount'], '0'), '.') : 0;
            return $this->successData($data);
        }catch (\Exception $ex){
            return $this->failData($ex->getMessage());
        }
    }


    public function getUserEthAddr($user_id){
        try{
            $memberM = new \addons\member\model\MemberAccountModel();
            $data = $memberM->getUserEthAddress($user_id);
            if(!$data){
                $res = $this->getEthAddr('WNCT');
                if(!$res){
                    return $this->failJSON("外网地址生成失败");
                }
                $info = [
                    'id' => $user_id,
                    'eth_address' => $this->address,
                    'eth_pass' =>$this->ethPass
                ];
                $memberM->save($info);
                $data = $this->address;
            }
            return $this->successJSON($data);
        }catch (\Exception $ex){
            return $this->failJSON($ex->getMessage());
        }
    }



    private function getEthAddr($name){
        $eth_pass = 'token'.$name.rand(0000,9999);
        $this->ethPass = $eth_pass;
        $res = $this->jsonrpc('personal_newAccount', [$eth_pass]);
        return $res;
    }

    private function jsonrpc($method, $params) {
        $sysM = new \web\common\model\sys\SysParameterModel();
        $port = $sysM->getValByName('port');
        $url = "http://127.0.0.1:".$port;
        $request = array('method' => $method, 'params' => $params, 'id' => 1);
        $request = json_encode($request);
        $opts = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/json',
            'content' => $request
        ));
        $context = stream_context_create($opts);
        if ($result = file_get_contents($url, false, $context)) {
            $data = json_decode($result, true);
            if(!empty($data) && $data['result'] ){
                $this->address = $data['result'];
                return true;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }


    /**
     * 用户注册
     */
    public function register(){
        try{
            $msg = '';
            $param = $this->check_params(__FUNCTION__,$msg);
            if(!$param){
                return $this->failData($msg);
            }
            $data['username'] = $param['username'];
            $data['phone'] = $param['phone'];
            $password = $param['password'];
            $pay_password = $param['pay_password'];
            $code = $param['code'];
            if($password == $data['username']){
                return $this->failData('用户名与密码相同');
            }
            $data['salt'] = rand(10000,99999);
            if(!empty($password))
                $data['password'] = md5(md5($password).$data['salt']);
            $data['pay_password'] = md5($pay_password);
            $m = new \addons\member\model\MemberAccountModel();
            if(isset($param['recommender'])){
                $puser = $m->getUserByUserName($param['recommender'], 'id,username');
                if(!$puser){
                    return $this->failData('推荐人不存在');
                }
                $data['pid'] = $puser['id'];
            }

            $verifyM = new \addons\member\model\VericodeModel();
            $_verify = $verifyM->VerifyCode($code,$data['phone'],1);
            if(!empty($_verify)) {
                $data['register_time'] = NOW_DATETIME;
                $res = $this->getEthAddr($data['username']);
                if(!$res){
                    return $this->failData('生成eth地址失败');
                }
                $data['address'] = $this->getAddress($data['phone']);
                $data['eth_address'] = $this->address; //eth地址
                $data['eth_pass'] = $this->ethPass;
                $user_id = $m->add($data); //用户id
                $_data['user_id'] = $user_id;
                $_data['address'] = $this->getAddress($data['phone']);
                $_data['username'] = $data['username'];
                return $this->successData();
            }else{
                return $this->failData('验证码无效,请重新输入或发送');
            }

        } catch (\Exception $ex) {
            return $this->failData($ex->getMessage());
        }
    }


    /**
     * 用户修改密码
     */
    public function changePass(){
        if(!IS_POST){
            return $this->failData('illegal request');
        }
        $phone = $this->_post('phone');
        $code = $this->_post('code');
        $password = $this->_post('password');
        $type = $this->_post('type');
        if(isset($phone) && isset($code) && isset($password) && isset($type)){
            if (!empty($password))
                $password = md5($password);
            $m = new \addons\member\model\MemberAccountModel();
            try {
                $verifyM = new \addons\member\model\VericodeModel();
                $_verify = $verifyM->VerifyCode($code, $phone, $type);
                if (!empty($_verify)) {
                    $id = $m->updatePassByPhone($phone, $password);
                    if ($id <= 0) {
                        return $this->failData('密码重置失败,请更换密码后重试 。reset the password is fail, please try again');
                    }
                    return $this->successData();
                } else {
                    return $this->failData('验证码失效,请重新注册');
                }
            } catch (\Exception $ex) {
                return $this->failData($ex->getMessage());
            }
        }else{
            return $this->failData('missing arguments');
        }
    }

    public function editUserAddr($user_id){
        $data['id'] = $this->_post('id');
        $data['user_id'] = $user_id;
        $data['address'] = $this->_post('address');
        $data['name'] = $this->_post('name');
        $data['remark'] = $this->_post('remark');
        $key_head = strtolower(substr($data['address'],0,2));
        if(empty($data['user_id'] || empty($data['address'])|| empty($data['name']))){
            return $this->failData('缺少参数');
        }
        if(($key_head!=="0x" || strlen($data['address']) !== 42)){
            return $this->failData('地址是由0X开头的42位16进制数组成');
        }
        $m = new \addons\member\model\MemberAccountModel();
        $user_addr = $m->getUserAddress($data['user_id']);
        if($data['address'] == $user_addr){
            return $this->failData('请勿输入自身钱包地址');
        }
        try{
            $data['update_time'] = NOW_DATETIME;
            $addrM = new \web\api\model\UserAddress();
            if(empty($data['id'])){
                //add
                $ret = $addrM->add($data);
            }else{
                //save
                $ret = $addrM->save($data);
            }
            if($ret > 0){
                return $this->successData();
            }else{
                return $this->failData('操作失败');
            }
        } catch (\Exception $ex) {
            return $this->failData($ex->getMessage());
        }
    }

    public function getUserAddrList($user_id){
        $m = new \web\api\model\UserAddress();
        try{
            $data = $m->getUserAddr($user_id);
            return $this->successData($data);
        } catch (\Exception $ex) {
            return $this->failData($ex->getMessage());
        }
    }

    public function getAddrByID($id){
        if(empty($id)){
            return $this->failData('缺少参数');
        }
        $m = new \web\api\model\UserAddress();
        try{
            $data = $m->getDetail($id);
            return $this->successData($data);
        } catch (\Exception $ex) {
            return $this->failData($ex->getMessage());
        }

    }

    public function delAddr($user_id, $id){
        $m = new \web\api\model\UserAddress();
        try{
            $where['id'] = $id;
            $where['user_id'] = $user_id;
            $ret = $m->where($where)->delete();
            if($ret > 0){
                return $this->successJSON('删除成功');
            }else{
                return $this->failJSON('删除失败');
            }
        } catch (\Exception $ex) {
            return $this->failJSON($ex->getMessage());
        }
    }


    /**
     * 用户身份验证
     */
    public function userAuth($user_id){
        $msg = '';
        $param = $this->check_params('userAuth',$msg);
        if(!$param){
            return $this->failData($msg);
        }
        $real_name = $this->_post('real_name');
        $card_no = $this->_post('card_no');
        $id_back_base64 = $this->_post('id_back');
        $id_face_base64 = $this->_post('id_face');
        $stand_pic_base64 = $this->_post('stand_pic');
        if(isset($user_id) && isset($real_name) && isset($card_no)){
            $is_auth = 2; //待认证状态
            try{
                $savePath = 'idcard/'.$user_id.'/';
                if(!empty($id_back_base64)){
                    $back_ret = $this->base_img_upload($id_back_base64, $user_id, $savePath);
                    if($back_ret['success']){
                        $data['id_back'] = $back_ret['path'];
                    }else{
                        return $this->failJSON('上传背面证件照失败');
                    }
                }
                if(!empty($id_face_base64)){
                    $face_ret = $this->base_img_upload($id_face_base64, $user_id, $savePath);
                    if($face_ret['success']){
                        $data['id_face'] = $face_ret['path'];
                    }else{
                        return $this->failJSON('上传正面证件照失败');
                    }
                }
                if(!empty($stand_pic_base64)){
                    $stand_ret = $this->base_img_upload($stand_pic_base64, $user_id, $savePath);
                    if($stand_ret['success']){
                        $data['stand_pic'] = $stand_ret['path'];
                    }else{
                        return $this->failJSON('上传手持身份证照失败');
                    }
                }
                $m = new \addons\member\model\MemberAccountModel();
                $data['id'] = $user_id;
                $data['real_name'] = $real_name;
                $data['card_no'] = $card_no;
                $data['is_auth'] = $is_auth;
                $m->save($data);
                return $this->successJSON();
            } catch (\Exception $ex) {
                return $this->failJSON($ex->getMessage());
            }
        } else {
            return $this->failJSON('missing arguments');
        }

    }


    /*
     * 设定用户资料
     */
    public function setUserInfo(){
        if(!IS_POST){
            return $this->failJSON('illegal request');
        }
        $user_id = $this->_post('user_id');
        if(!$user_id){
            return $this->failJSON("illegal request");
        }
        $data['id'] = $this->_post('user_id');
        $data['email'] = $this->_post('email');
        $data['home_address'] = $this->_post('home_address');
        $data['city'] = $this->_post('city');
        $data['country'] = $this->_post('country');
        try{
            $m = new \addons\member\model\MemberAccountModel();
            $m->save($data);
            return $this->successJSON();
        } catch (\Exception $ex) {
            return $this->failJSON($ex->getMessage());
        }
    }


    /**
     * 获取手机验证码
     */
    public function getPhoneVerify(){
        $phone = $this->_post('phone');
        $time = $this->_post('time',time());
        $type = $this->_post('type');
        if(empty($type))
            $type = 1;//注册验证码
        if($type == 2){
            //找回密码
            $memberM = new \addons\member\model\MemberAccountModel();
            $ret = $memberM->hasRegsterPhone($phone);
            if($ret <= 0){
                return $this->failJSON('手机号未注册 - Account is not exists');
            }
        }
        $m = new \addons\member\model\VericodeModel();
        $unpass_code = $m->hasUnpassCode($phone,$type);
        if(!empty($unpass_code)){
            return $this->failJSON('验证码未过期,请输入之前收到的验证码');
        }
        try{
            //发送验证码 todo
            $res = \web\common\utils\Sms::send($phone);
//            $res['success'] = true;
//            $res['message'] = '短信发送成功';
//            $res['code'] = '1111';
            if(!$res['success']){
                return $this->failJSON($res['message']);
            }
            $time = time();
            $time += 100;

            //保存验证码
            $pass_time = date('Y-m-d H:i:s',$time);
            $data['phone'] = $phone;
            $data['code'] = $res['code'];
            $data['type'] = $type;
            $data['pass_time'] = $pass_time; //过期时间
            $m->add($data);
            unset($res['code']);

            return $this->successJSON($res['message']);
        } catch (\Exception $ex) {
            return $this->failJSON($ex->getMessage());
        }
    }

    /**
     * 验证手机是否已经注册
     */
    public function hasReg($phone){
        if (empty($phone)){
            return $this->failJSON('手机号不能为空');
        }
        $m = new \addons\member\model\MemberAccountModel();
        $count = $m->hasRegsterPhone($phone);
        return $this->successJSON($count);
    }



    public function setLoginPass(){
        if(!IS_POST){
            return $this->failJSON('illegal request');
        }
        $user_id = $this->_post('user_id');
        $password = $this->_post('password');
        $now_password = $this->_post('pass2');
        $code = $this->_post('code');
        if(!$user_id || !$code  || !$now_password ){
            return $this->failJSON("illegal request");
        }

        try{
            $m = new \addons\member\model\MemberAccountModel();
            $user = $m->getDetail($user_id,"phone,password,salt");
            $verifyM = new \addons\member\model\VericodeModel();
            $_verify = $verifyM->VerifyCode($code,$user['phone'],5);
            if(!empty($_verify)){
//                $password = md5($password);
//                if($password !== $user['password']){
//                    return $this->failJSON("原密码输入有误");
//                }

                if(!preg_match("/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,20}$/",$now_password)){
                    return $this->failJSON('请输入5~20位字母数字密码');
                }
                $now_password = md5(md5($now_password).$user['salt']);


                $data['id'] = $user_id;
                $data['password'] = $now_password;
                $ret = $m->save($data); //用户id
                if(!$ret){
                    return $this->failJSON('修改失败');
                }
                //添加资产记录
                $m->commit();
                return $this->successJSON();
            }else{
                $m->rollback();
                return $this->failJSON('验证码失效,请重新输入');
            }
        } catch (\Exception $ex) {
            return $this->failJSON($ex->getMessage());
        }
    }

    public function setPayPass(){
        if(!IS_POST){
            return $this->failJSON('illegal request');
        }
        $user_id = $this->_post('user_id');
        $password = $this->_post('pass2');
        $now_password = $this->_post('pass2',0);

        $code = $this->_post('code');
        if(!$user_id || !$code ||  !$now_password ){
            return $this->failJSON("illegal request");
        }
        if(!preg_match("/^[0-9]{6}$/",$now_password)){
            return $this->failJSON('请输入6位数字交易密码');
        }

        try{
            $m = new \addons\member\model\MemberAccountModel();
            $user = $m->getDetail($user_id,"phone,pay_password");
            $verifyM = new \addons\member\model\VericodeModel();
            $_verify = $verifyM->VerifyCode($code,$user['phone'],7);
            if(!empty($_verify)){
                $now_password = md5($now_password);
                $data['id'] = $user_id;
                $data['pay_password'] = $now_password;
                $ret = $m->save($data); //用户id
                if(!$ret){
                    return $this->failJSON('修改失败');
                }
                //添加资产记录
                $m->commit();
                return $this->successJSON();
            }else{
                $m->rollback();
                return $this->failJSON('验证码失效,请重新输入或发送');
            }
        } catch (\Exception $ex) {
            return $this->failJSON($ex->getMessage());
        }
    }


    /*
     * 获取用户推荐链接
     */
    public function getInviteQRCode(){
        $user_id = $this->_get('user_id');
        if(empty($user_id)){
            return $this->failJSON('missing arguments');
        }
        try{
//            $m = new \addons\member\model\MemberAccountModel();
//            $info = $m->getDetail($user_id, 'username');
            $path = "http://www.wnct.io/login/register.html?code=".$user_id;
            $ret['count'] = 0;
            $ret['path'] = $path;
            return $this->successJSON($ret);

        } catch (\Exception $ex) {
            return $this->failJSON($ex->getMessage());
        }
    }



    /**
     * 外网转入记录获取。
     * @return type
     */
    protected function getEthOrders($user_id){
        set_time_limit(200);
        $ethApi = new \EthApi();
        $userM = new \addons\member\model\MemberAccountModel();
        $eth_address = $userM->getUserAddress($user_id);
        if(!$eth_address)
            return false;

        $coinM = new \addons\config\model\Coins();
        $coins = $coinM->getToken();
        foreach($coins as $coin){
            $ethApi->set_byte($coin['byte']);
            if(!empty($coin['contract_address'])){
                $ethApi->set_contract($coin['contract_address']);
            }
            $transaction_list = $ethApi->erscan_order($eth_address, $coin['is_eth']);
            if(empty($transaction_list)){
                continue;
            }
            $res = $this->checkOrder($user_id, $eth_address, $coin['id'], $transaction_list);
        }
        return true;
    }


    /**
     * 外网数据写入
     * @param type $user_id 用户id
     * @param type $address 用户地址
     * @param type $list    抓取到的数据
     * @param type $coin_id 币种id
     * @return boolean
     */
    private function checkOrder($user_id, $address, $coin_id, $list){
        $m = new \addons\eth\model\EthTradingOrder();
        $balanceM = new \addons\member\model\Balance();
        $recordM = new \addons\member\model\TradingRecord();
        foreach($list as $val){
            $txhash = $val['hash'];
            $block_number = $val['block_number'];
            $from_address = $val['from'];
            try{
                $res = $m->getDetailByTxHash($txhash);//订单匹配
                if($res){
                    return true;
                }
                $m->startTrans();
                $amount = $val['amount'];
                $eth_order_id = $m->transactionIn($user_id, $from_address, $address, $coin_id, $amount, $txhash, $block_number, 0, 1, 1, "外网转入");
                if($eth_order_id > 0){
                    //插入转入eth记录成功
                    $balance = $balanceM->updateAsset($user_id, $amount, $coin_id, true);

                    if(!$balance){
                        $m->rollback();
                        return false;
                    }

                    $type = 2;
                    $before_amount = $balance['before_amount'];
                    $after_amount = $balance['amount'];
                    $change_type = 1; //减少
                    $remark = '外网转入';
                    $r_id = $recordM->addRecord($user_id, $coin_id, $amount, $before_amount, $after_amount, $type, $change_type, $user_id, $address, '', $remark);
                    if(!$r_id ){
                        $m->rollback();
                        return false;
                    }
                    $m->commit();
                    return true;
                }else{
                    $m->rollback();
                    return false;
                }
            } catch (\Exception $ex) {
                return false;
            }
        }
        return true;
    }

}