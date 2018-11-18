<?php
/**
 * ETH 方法封装类
 * Created by PhpStorm.
 * User: mr_z
 * Date: 2018/8/13
 * Time: 上午11:09
 */
namespace  addons\eth\user\utils;
use think\Exception;

class EthApi{

    /**
     * ETH 开发人员API
     * @var type
     */
    private $jsonrpc_URL = 'https://api.etherscan.io/api';

    /**
     * ETH客户端端口号
     * @var type
     */
    private $eth_client_port = '';

    /**
     * APIkey
     * @var type
     */
    private $api_key = 'CJW6WNDAM7RET98ESSTCJARK5CJ7S5VT8P';

    /**
     * 矿工费
     * 单位Gwei 1 = 10^-9 个Eth
     * 补单Gwei 要更高
     * @var type
     */
    private $gasprice = 0;

    /**
     * Gas上限
     * @var type
     */
    private $gaslimit = 0;

    /**
     * 转账使用账号(主钱包)
     * 需要把主钱包的keystore导入到服务器的keystore文件夹中才能进行解锁
     * @var type
     */
    private $client_account = array(
        'address' => '',
        'password' => '',
    );

    /**
     * 从地址发送的事务数
     * @var type
     */
    private $_nonce = '';

    /**
     * ETH Transactions Sign
     * @var type
     * 例如: 0xf86480843b9aca00830186a094d09880bbfb3dea3155a543fb476b0033c37a279780801ca075e66bae393068dc4c451d24f3d52df9b4e461c6ef2ce7857115215c0ff9edd3a0457e6202dcdf2d52c7b645fb85118b7645542f2d2dbb8b824a3cc3da3bf4b69d
     */
    private $_raw = '';

    /**
     * 次方
     * @var type
     */
    private $_bcpowArr = array(
        'gasprice'  => 9,
        'eth_amount'=> 18,
    );

    /**
     * 方法的ASCII形式的Keccak散列的前4个字节
     * @var type
     */
    private $_transfer_method_id = '0xa9059cbb';

    public function __construct($from = '', $pass = '')
    {

    }


    /**
     * 转出eth与token
     * 外部调用函数
     * @param type $to  to address
     * @param type $amount transaction amount
     * @param type $contract_address token address dufault null:ETH transaction else token transaction
     * @param type $byte contract byte
     */
    public function send($to, $amount, $contract_address = null, $byte = 18){
        $data = array(
            'amount' => $amount,
            'byte' => $byte,
        );
        $ready = $this->_initTransaction();
        if(!$ready['success']){
            return $ready;
        }
        $ret = $this->_trading($data, $to, $contract_address);
        return $ret;
    }

    public function __set($name, $value){
        $allow = array('client_account','eth_client_port','gaslimit','gasprice');
        if(isset($this->$name)){
            $this->$name = $value;
        }
    }


    /**
     * sign data and send transaction
     * @param type $data
     * @param type $to
     * @param type $contract_address
     */
    private function _trading($data, $to, $contract_address){
        $amount_dechex = $this->bc_dechex(bcmul($data['amount'], bcpow(10, $data['byte'])));
        if(!empty($contract_address)){
            $amount_dechex = ltrim($amount_dechex,'0x');
            //32 bytes default string
            $default = str_pad(0,64,0,STR_PAD_LEFT);
            //32 bytes argument amount
            $amount = substr($default,0, strlen($default) - strlen($amount_dechex)).$amount_dechex;
            $_to = substr($to, 2);
            //32 bytes argument address
            $_to = str_pad($_to,64,0,STR_PAD_LEFT);
            $input_data = $this->_transfer_method_id . $_to . $amount;
            $has_sign = $this->_signTransaction($contract_address,$input_data);
        }else{
             $has_sign = $this->_signTransaction($to,'',$amount_dechex);
        }
        $this->_lockAccount($this->client_account['address']);
        if($has_sign['success']){
            $transfer = $this->_sendRawTransaction($this->_raw);
            if($transfer['success']){
                return $this->successData($transfer['data']);
            }else{
                return $this->failData($transfer['message']);
            }
        }
    }

    /**
     * send transaction raw
     * @param type $raw
     * @return type txid
     * 32 Bytes - the transaction hash, or the zero hash if the transaction is not yet available.
     */
    private function _sendRawTransaction($raw){
        $get = array(
            'module' => 'proxy',
            'action' => 'eth_sendRawTransaction',
            'hex' => $raw,
            'apikey' => $this->api_key,
        );
        $param = $this->makeData($get);
        $url = $this->jsonrpc_URL.'?'.$param;
        $result = http($url, null, 'GET');
        if(!empty($data['error'])){
            return $this->failData($data['error']['message']);
        }
        $data = $result['data'];
        if(empty($data['error'])){
            return $this->successData($data['result']);
        }else{
            return $this->failData($data['error']['message']);
        }
    }


    /**
     * 解锁账号并获取transaction sent number
     * @return type
     */
    private function _initTransaction(){
        $is_unlock = $this->_unlockAccount($this->client_account['address'], $this->client_account['password']);
        if($is_unlock['success']){
            $transactions = $this->_getTransactionCount($this->client_account['address']);
            if($transactions['success']){
                $this->_nonce = $transactions['data'];
                return $this->successData();
            }else{
                return $this->failData('get nonce value fail');
            }
        }else{
            return $this->failData('unlock account fail');
        }
    }

    /**
     * get Transaction sign
     * @param type $to 目标地址 token填写智能合约地址 , eth填写目标地址
     * @param $input_data 智能合约区块Input Data
     * @param $amount eth 数量
     * @return type
     */
    private function _signTransaction($to, $input_data='', $amount=''){
        $data['from'] = $this->client_account['address'];
        $data['to'] = $to;
        $data['gasPrice'] = $this->bc_dechex(bcmul($this->gasprice, bcpow(10,$this->_bcpowArr['gasprice'])));
        $data['gas'] = $this->bc_dechex($this->gaslimit);
        $data['nonce'] = $this->_nonce;
        if(!empty($input_data)){
            $data['data'] = $input_data;
            $data['value'] = '0x0'; //智能合约 value = 0
        }else{
            $data['data'] = '0x';
            $data['value'] = $amount;
        }
        $params = array($data);
        $ret = $this->jsonrpc('eth_signTransaction',$params);
        $this->_lockAccount($this->client_account['address']);
        unset($data);
        if($ret['success']){
            $data = $ret['data'];
            if(!empty($data['error'])){
                return $this->failData($data['error']['message']);
            }else{
                $this->_raw = $data['result']['raw'];
                return $this->successData();
            }
        }else{
            return $this->failData($ret['message']);
        }
    }

    /**
     * 10转16进制
     * @param type $decimal
     * @return type
     */
    private function bc_dechex($decimal) {
        $result = array();
        while ($decimal != 0) {
            $mod = bcmod($decimal, 16);
            $decimal = bcdiv($decimal, 16);
            array_push($result, dechex($mod));
        }
        return "0x" . join(array_reverse($result));
    }

    /**
     * Returns the number of transactions sent from an address.
     * 20 Bytes - address.
     * QUANTITY|TAG - integer block number, or the string "latest", "earliest" or "pending"
     * return string "{"success":true,"data":"0x3"}"
     */
    private function _getTransactionCount($address, $tag='pending'){
        $get = array(
            'module' => 'proxy',
            'action' => 'eth_getTransactionCount',
            'address' => $address,
            'tag' => $tag,
            'apikey' => $this->api_key,
        );
        $param = $this->makeData($get);
        $url = $this->jsonrpc_URL.'?'.$param;
        $data = http($url, null, 'GET');
        if(!empty($data['errmsg'])){
            return $this->failData($data['errmsg']);
        }
        if(empty($data['error'])){
            return $this->successData($data['data']['result']);
        }else{
            return $this->failData($data['error']['message']);
        }

    }

    /**
     * Decrypts the key with the given address from the key store.
     * @param type $address     地址
     * @param type $passphrase  密码短语
     * @param type $duration    持续时间    单位:分钟
     */
    private function _unlockAccount($address, $passphrase, $duration = 1){
        $arr = array($address,$passphrase,$duration);
        $data = $this->jsonrpc('personal_unlockAccount',$arr);
        if($data['success']){
            $data = $data['data'];
            if(empty($data['error'])){
                return $this->successData($data['result']);
            }else{
                return $this->failData($data['error']['message']);
            }
        }else{
            return $data;
        }

    }

    /*
     * lock Account
     */
    private function _lockAccount($address){
        $arr = array($address);
        $data = $this->jsonrpc('personal_lockAccount',$arr);
        if($data['success']){
            $data = $data['data'];
            if(empty($data['error'])){
                return $this->successData($data['result']);
            }else{
                return $this->failData($data['error']['message']);
            }
        }else{
            return $data;
        }
    }

    /**
     * 组成url 参数
     * @param type $post_data
     */
    private function makeData($post_data){
        $str = '';
        foreach($post_data as $k => $v){
            if(!empty($str))
                $str .= '&';
            $str .= $k .'='.$v;
        }
        return $str;
    }


//{"method": "personal_unlockAccount", "params": [string, string, number]}
    private function jsonrpc($method, $params) {
        $url = 'http://127.0.0.1:'.$this->eth_client_port;
        $request = array('method' => $method, 'params' => $params, 'id' => 1);
        $request = json_encode($request);
        $opts = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/json',
            'content' => $request
        ));
        $context = stream_context_create($opts);
        if ($result = file_get_contents($url, false, $context)) {
            $response = json_decode($result, true);
            return $this->successData($response);
        } else {
            return $this->failData('Connect Geth Error');
        }
    }


    /**
     * 返回错误信息。
     * @param type $message
     */
    protected function failData($message = null) {
        return array('success' => false, 'message' => $message);
    }

    /**
     * 返回成功信息
     * @param type $data
     */
    protected function successData($data = NULL) {
        return array('success' => true, 'data' => $data);
    }
}