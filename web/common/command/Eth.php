<?php

namespace web\common\command;

use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Exception;

/**
 * Description of Eth
 * 1.Eth 用户转出
 * 2.将转入的ETH提取到收款钱包
 * 
 * 自定义命令行
 * @author shilinqing
 * @@@@@@@@@@@@@@ --捐赠地址-- @@@@@@@@@@@@@@@@@@@
 * @                                            @
 * @ 0xd09880bBFB3DEA3155a543FB476B0033c37a2797 @
 * @                                            @
 * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
 */
class Eth extends Command{
    
    /**
     * ETH 开发人员API
     * @var type 
     */
    private $jsonrpc_URL = 'https://api.etherscan.io/api';
    
    /**
     * ETH客户端端口号
     * @var type 
     */
    private $eth_client_port = '8545';
    
    /**
     * APIkey
     * @var type 
     */
    private $api_key = 'CJW6WNDAM7RET98ESSTCJARK5CJ7S5VT8P';

    /**
     * 发送失败次数
     * @var type 
     */
    private $fail_count = 0;

    /**
     * 矿工费
     * 单位Gwei 1 = 10^-9 个Eth
     * 补单Gwei 要更高
     * @var type 
     */
    private $gasprice = 10;
    
    /**
     * Gas上限
     * @var type 
     */
    private $gaslimit = 100000;
    
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

    
    /**
     * 定义命令
     */
    protected function configure(){
        $this
            ->setName('eth')
            ->addOption('price', 'p', Option::VALUE_OPTIONAL, 'Eth gas price num,default 1.', 1)
            ->addOption('limit', 'l', Option::VALUE_OPTIONAL, 'Eth gas limit num,default 100000.', 100000)
            ->setDescription('RUN ETH Data RPC API');
    }
    
    protected function execute(Input $input, Output $output) {
        //write your code in here
        $paramM = new \web\common\model\sys\SysParameterModel();
        $out_address = $paramM->getValByName('out_address');
        $out_password = $paramM->getValByName('out_password');
        $key_head = strtolower(substr($out_address,0,2));
        if(($key_head!=="0x" || strlen($out_address) !==42)){
            return $output->error('地址是由0X开头的42位16进制数组成');
        }
        if(empty($out_address) || empty($out_password)){
            return $output->error('主账号信息不完全');
        }
        $this->client_account['address'] = $out_address;
        $this->client_account['password'] = $out_password;
        $this->gasprice = $input->getOption('price');
        $this->gaslimit = $input->getOption('limit');
        while(true){
            $tradeM = new \addons\trade\model\TradeModel();
            $data = $tradeM->getUncheckData();
            if(!empty($data)){
                $id = $data['id'];
                $to = $data['to'];
                $contract_address = $data['contract_address'];
                $frex_to = strtolower(substr($to,0,2));
                if(($frex_to !== "0x" || strlen($to) !== 42)){
                    //异常订单处理 更新订单状态非未通过
                    $tradeM->updateStatus($id,5,'','转出地址格式错误');
                }
                $ready = $this->_initTransaction();
                if($ready['success']){
                    if(empty($contract_address)){
                        //智能合约地址为空,则为eth转账
                        $ret = $this->_ethTrading($data, $to);
                    }else{
                        $ret = $this->_tokenTrading($data, $to, $contract_address);
                    }
                    if($ret['success']){
                        //更新订单txid
                        $tradeM->updateStatus($id, 4, $ret['data'], '创建交易hash成功');
                        $output->writeln('id:'.$id.' 转出成功。');
                    }else{
                        //异常订单
                        $tradeM->updateStatus($id, 5,'', $ret['message']);
                        $output->error($ret['message']);
                    }
                }else{
                    return $output->error($ready['message']);
                }
            }else{
                $output->writeln('没有新订单');
                sleep(5);
            }
        }
    }
    
    /**
     * eth 转出
     * $data 用户转出数据
     */
    private function _ethTrading($data, $to){
        $amount_dechex = $this->bc_dechex($data['amount'], bcpow(10, $data['byte']));
        $has_sign = $this->_signTransaction($to, '', $amount_dechex);
        if($has_sign['success']){
            $this->_lockAccount($this->client_account['address']);
            $transfer = $this->_sendRawTransaction($this->_raw);
            if($transfer['success']){
                return $this->successData($transfer['data']);
            }else{
                return $this->failData($transfer['message']);
            }
        }
    }
    
    /**
     * token 转出
     */
    private function _tokenTrading($data, $to, $contract_address){
        $amount_dechex = $this->bc_dechex($data['amount'], bcpow(10, $data['byte']));
        $amount_dechex = ltrim($amount_dechex,'0x');
        //64 个字符
        $default = "0000000000000000000000000000000000000000000000000000000000000000";
        //32 bytes argument amount
        $amount = substr($default,0, strlen($default) - strlen($amount_dechex)).$amount_dechex;
        $_to = substr($to, 2);
        //32 bytes argument address
        $_to = '000000000000000000000000'.$_to;
        $input_data = $this->_transfer_method_id . $_to . $amount;
        $has_sign = $this->_signTransaction($contract_address,$input_data);
        if($has_sign['success']){
            $this->_lockAccount($this->client_account['address']);
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
        $data = $this->http($url, null, 'GET');
        $data = json_decode($data, true);
        if(!empty($data['errmsg'])){
            return $this->failData($data['errmsg']);
        }
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
                return $this->failData('获取nonce参数失败');
            }
        }else{
            return $this->failData('解锁账号失败');
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
        unset($data);
        if($ret['success']){
            $data = $ret['data'];
            if(!empty($data['error'])){
                return $this->failData($data['message']);
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
        $data = $this->http($url, null, 'GET');
        $data = json_decode($data, true);
        if(!empty($data['errmsg'])){
            return $this->failData($data['errmsg']);
        }
        if(empty($data['error'])){
            return $this->successData($data['result']);
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
    private function _unlockAccount($address, $passphrase, $duration = 10){
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

    
    private function http($url, $param = null, $method = 'POST') {
        try {
            $opts = array(
                CURLOPT_TIMEOUT => 60,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                //CURLOPT_SAFE_UPLOAD => false
            );
            /* 根据请求类型设置特定参数 */
            $opts[CURLOPT_URL] = $url;
            if (strtoupper($method) == 'POST' && !is_null($param)) {
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $param;
                if (is_string($param)) { //发送Data数据
                    $opts[CURLOPT_HTTPHEADER] = array(
                        'Content-Type: application/json; charset=utf-8',
                        'Content-Length: ' . strlen($param),
                    );
                }
            }
            /* 初始化并执行curl请求 */
            $ch = curl_init();
            curl_setopt_array($ch, $opts);
            $data = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            if ($error) {//发生错误
                $data = '{"errcode":-1, "errmsg":"' . $error . '"}';
            }
        } catch (\Exception $ex) {
            $data = '{"errcode":-1, "errmsg":"' . $ex->getMessage() . '"}';
        }
        return $data;
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
     * 输出错误array信息。
     * @param type $message     
     */
    protected function failData($message) {
        $data = array('success' => false, 'message' => $message);        
        return $data;
    }

    /**
     * 输出成功array信息
     * @param type $data
     */
    protected function successData($data = NULL) {
        $data = array('success' => true, 'data' => $data);
        return $data;
    }

}
