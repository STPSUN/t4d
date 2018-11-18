<?php

/**
 * ETH 钱包操作 API库
 * @author https://github.com/del-xiong
 */
class EthApi {

    private $url = 'http://47.74.186.55';
    private $contract = "0x9733442ef8a38ae8874fdaee8024ca1f271edfef";
    private $byte = 0;
    public $api_method = '';
    public $req_method = '';
    private $get = array();
    private $count = 100;

    function __construct() {
        $this->byte = bcpow(10, 18);
        date_default_timezone_set("Etc/GMT+0");
    }

    // 创建以太坊钱包地址
    function create_user($password = '') {
        $url = $this->url;
        $url .= "/api.php?act=newaccount&password={$password}";
        $return = $this->curl($url);
        $result = json_decode($return, 1);
        return $result;
    }

    //eth地址生成
    public function newEthAccount($password = null) {
        if (!empty($password)) {
            $params = array($password);
            $re = $this->jsonrpc("personal_newAccount", $params);
            if (empty($re["result"])) {
                return ["code" => "0", "msg" => "password error"];
            } else {
                return ["code" => "1", "msg" => $re["result"]];
            }
        } else {
            return ["code" => "0", "msg" => "password error"];
        }
    }

    //本地ETH进程请求
    private function jsonrpc($method, $params) {
        $url = $this->url;
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
            return $response;
        } else {
            return ["code" => "0", "msg" => "connect geth error"];
        }
    }

    //获取地址交易记录
    public function erscan_order($address, $cointype = 0, $type = "to", $contract = null, $count = 1000) {
        try {
            $contract = $contract ? $contract : $this->contract;
            $address = strtolower($address);
            $contract = strtolower($contract);
            $url = "http://api.etherscan.io/api?module=account&action=tokentx&address={$address}&startblock=0&endblock=999999999&sort=desc&contractAddress={$contract}";
            if ($cointype == 0) {
                $url = "http://api.etherscan.io/api?module=account&action=txlist&address={$address}&startblock=0&endblock=99999999&sort=desc&apikey=IYR3CPUCR15QED78SK11W573QS8DA2JTMI";
            }
            $return = $this->curl($url);

            $result = json_decode($return, 1);
            if (!$result || $result['status'] !== '1') {
                return array();
            }
            $resultData = $result['result'];
            $data = array();
            foreach ($resultData as $key => $val) {
                if ($val['to'] !== strtolower($address)) {
                    continue;
                }
                $value = array();
                $rate = $this->byte;
                $amount = $this->NumToStr($val['value']) / $rate;
                $value['block_number'] = $val['blockNumber'];
                $value['amount'] = $this->NumToStr($amount);
                $value['from'] = $val['from'];
                $value['to'] = $val['to'];
                $value['hash'] = $val['hash'];
                $value['timeStamp'] = $val['timeStamp'];

                $data[] = $value;
            }
            return $data;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param $num         科学计数法字符串  如 2.1E-5
     * @param int $double 小数点保留位数 默认5位
     * @return string
     */
    function NumToStr($num, $double = 8) {
        if (false !== stripos($num, "e")) {
            $a = explode("e", strtolower($num));
            return bcmul($a[0], bcpow(10, $a[1], $double), $double);
        }
        return bcmul($num, 1, $double);
    }

    public function set_contract($contract) {
        $this->contract = $contract;
    }

    public function set_url($url) {
        $this->url = $url;
    }

    public function set_byte($count) {
        $this->byte = bcpow(10, $count);
    }

    // 组合参数
    function bind_param($param, $url) {
        $str = "&";
        foreach ($param as $k => $v) {
            if ($v) {
                $str .= $k . "=" . $v . "&";
            }
        }
        $str = substr(0, -1);
        $url .= $str;
        return $url;
    }

    function curl($url, $postdata = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($this->req_method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
        ));
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return $output;
    }

}

?>
