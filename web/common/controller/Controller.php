<?php

namespace web\common\controller;

\think\Loader::import('controller/Jump', TRAIT_PATH, EXT);

/**
 * 控制器基类。
 */
class Controller {

    use \traits\controller\Jump;
    
    // 视图类实例
    protected $view;
    // Request实例
    protected $request;


    // 服务层实例
    protected $service;
    /**
     * 模板主题
     * @var type 
     */
    protected $theme = 'default';

    /**
     * 模板路径
     * @var type 
     */
    protected $view_path = '';

    public function __construct(\think\Request $request = null) {
        if (is_null($request)) {
            $request = \think\Request::instance();
        }
        $this->bindCache($request);
        $this->request = $request;
        $this->defineRequest();
        if (strtolower($this->request->controller()) != 'addonsexecute') {
            $this->globalCache = $this->request->__get('global_cache');
            if (!IS_AJAX || IS_PJAX) {
                $module = $this->request->module();
                $module = $module ? $module . DS : '';
                // 当前模块路径
                \think\App::$modulePath = APP_PATH . $module;
                $this->view_path = \think\App::$modulePath . 'view' . DS . ($this->theme ? $this->theme . DS : '');
                $this->view = \think\View::instance(\think\Config::get('template'), \think\Config::get('view_replace_str'));
            }
            $this->view = \think\View::instance(\think\Config::get('template'), \think\Config::get('view_replace_str'));
            // 控制器初始化
            $this->_initialize();
            if (!IS_AJAX || IS_PJAX) {
                if (defined('ADDON_NAME') && ADDON_NAME != '') {
                    // 加载模块配置
                    $config = \think\Config::load(CONF_PATH . $module . 'config' . CONF_EXT);
                    $this->view->replace($config['view_replace_str']);
                }
                $this->view->config('view_path', $this->view_path); //更新模板路径(含主题)             
            }
        }
    }
    
    private function bindCache(&$request) {        
        if (!$request->__isset('data_cache')) {         
            $dataCache = \think\Cache::connect(\think\Config::get('data_cache'));
            $request->bind('data_cache', $dataCache);
        }
        if (!$request->__isset('global_cache')) {
            $globalCache = \think\Cache::connect(\think\Config::get('global_cache'));
            $request->bind('global_cache', $globalCache);
        }
        if (!$request->__isset('file_cache')) {
            $fileCache = \think\Cache::connect(\think\Config::get('file_cache'));
            $request->bind('file_cache', $fileCache);
        }
    }

    
    /**
     * 定义常用请求变量。
     */
    protected function defineRequest() {
        defined('__ROOT__') or define('__ROOT__', $this->request->root());
        defined('IS_PJAX') or define('IS_PJAX', $this->request->isPjax());
        defined('IS_AJAX') or define('IS_AJAX', $this->request->isAjax());
        defined('IS_POST') or define('IS_POST', $this->request->isPost());
        defined('IS_GET') or define('IS_GET', $this->request->isGet());
        defined('NOW_DATETIME') or define('NOW_DATETIME', getTime());
        if (strtolower($this->request->controller()) != 'addonsexecute') {
            defined('ADDON_NAME') or define('ADDON_NAME', '');
            defined('MODULE_NAME') or define('MODULE_NAME', $this->request->module());
            defined('CONTROLLER_NAME') or define('CONTROLLER_NAME', $this->request->controller());
            defined('ACTION_NAME') or define('ACTION_NAME', $this->request->action());
        }
    }

    /**
     * 加载模板输出
     * @access protected
     * @param string $template 模板文件名     
     * @param array  $vars     模板输出变量
     * @param array  $replace  模板替换
     * @param array  $config   模板参数
     * @return mixed
     */
    protected function fetch($template = '', $vars = [], $replace = [], $config = []) {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplate($template);
        }
        // 模板不存在 抛出异常
        if (!is_file($template)) {
            throw new \think\exception\TemplateNotFoundException('template not exists:' . $template, $template);
        }
        return $this->view->fetch($template, $vars, $replace, $config);
    }

    /**
     * 
     * @param type $template 模板文件名称
     * @param type $vars
     * @param type $replace
     * @param type $config
     * @return type  返回模板地址
     * @throws \think\exception\TemplateNotFoundException
     */
    protected function path($template = '', $vars = [], $replace = [], $config = []) {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplate($template);
        }        
        // 模板不存在 抛出异常
        if (!is_file($template)) {
            throw new \think\exception\TemplateNotFoundException('template not exists:' . $template, $template);
        }
        return $template;
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param mixed $name  要显示的模板变量
     * @param mixed $value 变量的值
     * @return void
     */
    protected function assign($name, $value = '') {
        $this->view->assign($name, $value);
    }

    /**
     * 自动定位模板文件
     * @access private
     * @param string $template 模板文件规则     
     * @return string
     */
    private function parseTemplate($template) {
        $config = config('template');
        if (empty($config['view_path'])) {
            $config['view_path'] = $this->view_path;
        }
        // 获取视图根目录
        if (strpos($template, '@')) {
            // 跨模块调用
            list($module, $template) = explode('@', $template);
            $path = APP_PATH . $module . DS . 'view' . DS;
        } else {
            // 当前视图目录
            $path = $config['view_path'];
        }
        // 分析模板文件规则        
        $controller = \think\Loader::parseName($this->request->controller());
        $controller = explode('.', $controller)[0];
        if ($controller && 0 !== strpos($template, '/')) {
            $depr = $config['view_depr'];
            $template = str_replace(['/', ':'], $depr, $template);
            if ('' == $template) {
                // 如果模板文件名为空 按照默认规则定位
                $template = str_replace('.', DS, $controller) . $depr . $this->request->action();
            } elseif (false === strpos($template, $depr)) {
                $template = str_replace('.', DS, $controller) . $depr . $template;
            }
        }
        return $path . ltrim($template, '/') . '.' . ltrim($config['view_suffix'], '.');
    }

    /**
     * 获取POST值
     * @param type $name 变量名
     * @param type $default 默认值
     * @param type $filter 过滤方法
     * @return type
     */
    protected function _post($name, $default = '', $filter = '') {
        return request()->param($name, $default, $filter);
    }

    /**
     * 获取GET值
     * @param type $name
     * @param type $default 默认值
     * @param type $filter 过滤方法
     * @return type
     */
    protected function _get($name, $default = '', $filter = '') {
        return request()->param($name, $default, $filter);
    }
   
    /**
     * 获取request值。
     * @param type $name     
     */
    protected function _request($name) {
        if (isset($_REQUEST[$name]))
            return trim($_REQUEST[$name]);
        else
            return '';
    }

    /**
     * 空操作，用于输出404页面
     */
    public function _empty() {
        $config = config('template');
        $path = APP_PATH . MODULE_NAME . DS . 'view' . DS;
        if ($this->theme != '')
            $path .= $this->theme . DS;
        $path .= '404.' . ltrim($config['view_suffix'], '.');
        return $this->fetch($path);
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

    /**
     * 
     * @param type $url
     */
    protected function redirect($url) {
        header('Location:' . $url);
        exit;
    }

    /**
     * 加锁。
     * @param type $key 
     * @param type $waitime 等待时间(毫秒)
     * @param type $timeout 超时时间(秒)
     */
    protected function lock($key, $waitime = 500, $timeout = 20) {
        $totalWaitime = 0;
        //一微秒等于百万分之一秒。
        $waitime = $waitime * 1000; //毫秒转成微秒
        $time = $timeout * 1000000; //秒转成微秒
        $data = $this->getGlobalCache($key);
        if ($data) {
            set_time_limit(0);
            while ($totalWaitime < $time) {
                usleep($waitime);
                $totalWaitime += $waitime;
            }
            if ($totalWaitime >= $time) {
                $this->rmGlobalCache($key);
                //throw new \Exception('can not get lock for waiting ' . $timeout . 's.');
            }
        } else
            $this->setGlobalCache(1, $key, null);
    }

    /**
     * 解锁。
     * @param type $key
     */
    protected function unLock($key) {
        if ($this->globalCache) {
            $this->globalCache->rm($key);
        }
    }


    /**
     * 初始化eth类
     * @param t &$msg
     */
    protected function _initArguments( &$msg){
        $ethApi = new \addons\eth\user\utils\EthApi();
        $paramM = new \web\common\model\sys\SysParameterModel();
        $sys_param = $paramM->getParameterDataByKey(3);

        $key_head = strtolower(substr($sys_param['out_address'],0,2));
        if(($key_head!=="0x" || strlen($sys_param['out_address']) !==42)){
            $msg = "address mast be started by 0x";
            return false;
        }
        if(empty($sys_param['out_address']) || empty($sys_param['out_password'])){
            $msg = "client account verify is fail";
            return false;
        }
        if(empty($sys_param['port'])){
            $msg = "eth jsonrpc2.0 init fail";
            return false;
        }
        $account = array(
            'address' => $sys_param['out_address'],
            'password' => $sys_param['out_password']
        );
        $ethApi->client_account = $account;
        $ethApi->eth_client_port = $sys_param['port'];
        $ethApi->gaslimit = $sys_param['gaslimit'];
        $ethApi->gasprice = $sys_param['gasprice'];
        return $ethApi;
    }

}
