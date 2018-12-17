<?php

namespace web\common\model;

use think\Db;
use think\Config;
use think\Exception\ValidateException;
use think\Loader;

/**
 * 模型。
 */
class Model {

    private $request = null;

    /**
     * 数据库连接配置
     * @var type 
     */
    protected $database_config = '';

    /**
     * 平台系统数据库连接配置
     * @var type 
     */
    protected $sys_database_config = '';

    /**
     * 数据库对象池
     * @var type 
     */
    protected static $links = [];
    private $class = null;

    /**
     * 错误信息
     * @var type 
     */
    protected $error;

    /**
     * 字段验证规则
     * @var type 
     */
    protected $validate;

    /**
     * 数据表字段信息
     * @var type 
     */
    protected $field = [];

    /**
     * 是否采用批量验证
     * @var type 
     */
    protected $batchValidate = false;

    /**
     * 数据表主键 复合主键使用数组定义 不设置则自动获取
     * @var type 
     */
    protected $pk;

    /**
     * 数据信息
     * @var type 
     */
    public $data = [];

    /**
     * 数据表名（不包含表前缀）
     * @var type 
     */
    protected $tableName = '';

    /**
     * 实际数据表名（包含表前缀）
     * @var type 
     */
    protected $trueTableName = '';

    /**
     * 表名前缀
     * @var type 
     */
    protected $tablePrefix = '';

    /**
     * 全局缓存对象
     * @var type 
     */
    protected $globalCache = null;

    /**
     * 本地文件缓存对象
     * @var type 
     */
    protected $fileCache = null;

    /**
     * 本地缓存对象
     * @var type 
     */
    protected $cache = null;

    protected function _initialize() {
        
    }

    /**
     *      
     */
    public function __construct() {
        $this->_initialize();
        $this->class = get_class($this);
        $this->sys_database_config = Config::get('database');
        if (empty($this->database_config))
            $this->database_config = $this->sys_database_config;
        $this->tablePrefix = $this->database_config['prefix'];
        $this->trueTableName = $this->tablePrefix . $this->tableName;
        $this->request = \think\Request::instance();
        $this->cache = $this->request->__get('data_cache');
        $this->globalCache = $this->request->__get('global_cache');
        $this->fileCache = $this->request->__get('file_cache');
    }

    /**
     * 获取当前模型的数据库查询对象
     * @return Quary
     */
    private function db($sys_database = false) {
        $model = $this->class;
        if ($sys_database)
            $model .= '_sys_database';
        if (!isset(self::$links[$model])) {
            // 设置当前模型 确保查询返回模型对象
            $connect = Db::connect($sys_database ? $this->sys_database_config : $this->database_config);
            $query = new \think\db\Query($connect);
            // 设置当前数据表和模型名
            if (!empty($this->trueTableName)) {
                $query->setTable($this->trueTableName);
            } else {
                $query->name($this->name);
            }
            if (!empty($this->pk)) {
                $query->pk($this->pk);
            }
            self::$links[$model] = $query;
        }
        // 返回当前模型的数据库查询对象
        return self::$links[$model];
    }

    /**
     * 获取模型对象的主键
     * @access public
     * @param string $name 模型名
     * @return mixed
     */
    public function getPk($name = '') {
        if (!empty($name)) {
            $table = $this->db()->getTable($name);
            return $this->db()->getPk($table);
        } elseif (empty($this->pk)) {
            $this->pk = $this->db()->getPk();
        }
        return $this->pk;
    }

    /**
     * 判断一个字段名是否为主键字段
     * @access public
     * @param string $key 名称
     * @return bool
     */
    protected function isPk($key) {
        $pk = $this->getPk();
        if (is_string($pk) && $pk == $key) {
            return true;
        } elseif (is_array($pk) && in_array($key, $pk)) {
            return true;
        }
        return false;
    }

    /**
     * 验证数据。
     * @param type $data
     * @param type $rule
     * @param type $batch
     * @return boolean
     * @throws ValidateException
     */
    protected function validateData($data, $rule = null, $batch = null) {
        $info = is_null($rule) ? $this->validate : $rule;
        if (!empty($info)) {
            if (is_array($info)) {
                $validate = Loader::validate();
                $validate->rule($info['rule']);
                $validate->message($info['msg']);
            } else {
                $name = is_string($info) ? $info : $this->name;
                if (strpos($name, '.')) {
                    list($name, $scene) = explode('.', $name);
                }
                $validate = Loader::validate($name);
                if (!empty($scene)) {
                    $validate->scene($scene);
                }
            }
            $batch = is_null($batch) ? $this->batchValidate : $batch;

            if (!$validate->batch($batch)->check($data)) {
                $this->error = $validate->getError();
                if ($this->failException) {
                    throw new ValidateException($this->error);
                } else {
                    return false;
                }
            }
            $this->validate = null;
        }
        return true;
    }

    /**
     * 添加数据。
     * @param type $data
     * @param boolean    $is_filter    是否过滤html
     * @return type
     */
    public function add($data = [], $is_filter = true) {
        if ($this->validate && !empty($data)) {
            if (!$this->validateData($data)) {
                return false;
            }
        }
        if (!empty($data)) {
            //LCJ注：字段可缓存优化
            $this->field = $this->db()->getTableInfo('', 'fields');
            foreach ($data as $key => $val) {
                if (!in_array($key, $this->field)) {
                    unset($data[$key]);
                } else if ($is_filter) {
                    if ($key != 'remark')
                        $data[$key] = $this->request->input($val);
                }
            }
        }
        $result = $this->db()->insert($data, false, true);
        if ($result !== false) {
            $pk = $this->getPk();
            if (is_string($pk)) {
                $data[$pk] = $result;
            }
            if (false === $this->_after_insert($data)) {
                return false;
            }
        }
        $this->data = $data;
        return $result;
    }

    /**
     * 保存当前数据对象
     * @access public
     * @param array     $data 数据
     * @param array     $where 更新条件
     * @param string    $sequence     自增序列名
     * @param boolean    $is_filter    是否过滤html
     * @return integer|false
     */
    public function save($data = [], $where = [], $sequence = null, $is_filter = true) {
        if ($this->validate && !empty($data)) {
            if (!$this->validateData($data)) {
                return false;
            }
        }
        if (!empty($data)) {
            $this->field = $this->db()->getTableInfo('', 'fields');
            foreach ($data as $key => $val) {
                if (!in_array($key, $this->field)) {
                    unset($data[$key]);
                } else if ($is_filter) {
                    if ($key != 'remark')
                        $data[$key] = $this->request->input($val);
                }
            }
        }
        $isUpdate = false;
        if (!empty($where)) {
            $isUpdate = true;
        } else {
            $pk = $this->getPk();
            if (is_string($pk) && isset($data[$pk])) {
                if ($data[$pk] != '' && intval($data[$pk]) > 0) {
                    $where[$pk] = $data[$pk];
                    $isUpdate = true;
                }
                unset($data[$pk]);
            }
        }
        if ($isUpdate) {
            $result = $this->db()->where($where)->update($data);
            if ($result !== false) {
                if (false === $this->_after_update($data)) {
                    return false;
                }
            }
        } else {
            $result = $this->db()->insert($data, false, true);
            if ($result !== false) {
                $pk = $this->getPk();
                if (is_string($pk)) {
                    $data[$pk] = $result;
                }
                if (false === $this->_after_insert($data)) {
                    return false;
                }
            }
        }
        $this->data = $data;
        return $result;
    }

    /**
     * 保存多个数据到当前数据对象
     * @access public
     * @param array   $dataSet 数据     
     * @return array|false
     * @throws \Exception
     */
    public function saveAll($dataSet) {
        if ($this->validate) {
            // 数据批量验证
            $validate = $this->validate;
            foreach ($dataSet as $data) {
                if (!$this->validateData($data, $validate)) {
                    return false;
                }
            }
        }
        $result = [];
        $this->startTrans();
        try {
            foreach ($dataSet as $key => $data) {
                $result[$key] = $this->save($data);
            }
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 删除当前的记录
     * @access public
     * @return integer
     */
    public function delete() {
        $result = $this->db()->delete();
        $this->_after_execute($result);
        return $result;
    }

    /**
     * 执行语句
     * @access public
     * @param string  $sql          sql指令
     * @param array   $bind         参数绑定
     * @return int
     * @throws BindParamException
     * @throws PDOException
     */
    public function execute($sql, $bind = []) {
        $numRows = $this->db()->execute($sql, $bind);
        $this->_after_execute($numRows);
        return $numRows;
    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param string      $sql    sql指令
     * @param array       $bind   参数绑定
     * @param boolean     $master 是否在主服务器读操作     
     * @param boolean     $sys_database 是否在平台系统数据服务器读操作     
     * @return mixed
     * @throws BindParamException
     * @throws PDOException
     */
    public function query($sql, $bind = [], $master = false, $sys_database = false) {
        return $this->db($sys_database)->query($sql, $bind, $master);
    }

    public function __call($method, $args) {
        $query = $this->db();
        return call_user_func_array([$query, $method], $args);
    }

    /**
     * 返回模型的错误信息
     * @access public
     * @return string
     */
    public function getError() {
        return $this->error;
    }

    /**
     *  获取数据表名（包含表前缀）
     * @return type
     */
    public function getTableName() {
        return $this->trueTableName;
    }

    /**
     *  获取数据表名（不包含表前缀）
     * @return type
     */
    public function getTable() {
        return $this->tableName;
    }

    /**
     *  获取数据表前缀
     * @return type
     */
    public function getTablePrefix() {
        return $this->tablePrefix;
    }

    /**
     * 插入成功之后。
     * @param type $data     
     * @return boolean
     */
    protected function _after_insert($data) {
        $this->setGlobalCacheFlag();
        $this->clearCache();
        return true;
    }

    /**
     * 更新成功之后
     * @param type $data     
     * @return boolean
     */
    protected function _after_update($data) {
        $this->setGlobalCacheFlag();
        $this->clearCache();
        return true;
    }

    /**
     * 执行成功之后
     * @param type $data
     * @param type $options
     * @return boolean
     */
    protected function _after_execute($numRows) {
        $this->setGlobalCacheFlag();
        $this->clearCache();
        return true;
    }

    protected function find($data = null) {
        return $this->db()->find($data);
    }

    /**
     * 指定AND查询条件
     * @access public
     * @param mixed $field     查询字段
     * @param mixed $op        查询表达式
     * @param mixed $condition 查询条件
     * @return $this
     */
    public function where($field, $op = null, $condition = null) {
        return $this->db()->where($field, $op, $condition);
    }

    /**
     * 启动事务
     */
    public function startTrans() {
        $this->db()->startTrans();
    }

    /**
     * 提交事务
     */
    public function commit() {
        $this->db()->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback() {
        $this->db()->rollback();
    }

    /**
     * 自动控制事务处理
     * @param type $callback
     * @return type
     * @throws \Exception
     */
    public function transaction($callback) {
        $this->startTrans();
        try {
            $result = null;
            if (is_callable($callback)) {
                $result = call_user_func_array($callback, [$this]);
            }
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 获取记录总数。
     * @param type $filter 过滤条件
     * @return int
     */
    public function getTotal($filter = '') {
        $sql = 'select count(*) c from ' . $this->getTableName();
        if (!empty($filter))
            $sql .= ' where ' . $filter;
        $result = $this->query($sql);
        if (count($result) > 0)
            return intval($result[0]['c']);
        else
            return 0;
    }

    /**
     * 获取列表数据
     * @param type $pageIndex 当前页
     * @param type $pageSize 每页数量
     * @param type $filter 过滤条件
     * @param type $fields 字段信息
     * @param type $order 排序     
     * @return type
     */
    public function getDataList($pageIndex = -1, $pageSize = -1, $filter = '', $fields = '', $order = 'id desc') {
        $sql = 'select ';
        if (!empty($fields))
            $sql .= $fields;
        else
            $sql .= '*';
        $sql .= ' from ' . $this->getTableName();
        if (!empty($filter))
            $sql .= ' where ' . $filter;
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }

    /**
     * 根据SQL获取列表数据
     * @param type $sql
     * @param type $pageIndex 当前页
     * @param type $pageSize 每页数量
     * @param type $order 排序
     */
    public function getDataListBySQL($sql, $pageIndex, $pageSize, $order = '') {
        if (!empty($order))
            $sql .= ' order by ' . $order;
        if ($pageIndex >= 0 && $pageSize > 0) {
            $offset = 0;
            if ($pageIndex > 0)
                $offset = (intval($pageIndex) - 1) * intval($pageSize);
            $sql .= ' limit ' . $offset . ',' . $pageSize;
        }

        return $this->query($sql);
    }

    /**
     * 获取一个字段的值
     * @param type $sql
     * @param type $field
     * @return type
     */
    public function getFieldValue($sql, $field) {
        $result = $this->query($sql);
        if (!empty($result))
            return $result[0][$field];
        else
            return null;
    }

    /**
     *  获取数据详情。
     * @param type $id
     * @return type
     */
    public function getDetail($id) {
        $where = array('id' => $id);
        return $this->where($where)->find();
    }

    /**
     * 删除数据。
     * @param type $id
     */
    public function deleteData($id) {
        $where = array('id' => $id);
        return $this->where($where)->delete();
    }

    /**
     * 逻辑删除。
     * @param type $id
     * @return type
     */
    public function deleteLogic($id) {
        $where = array('id' => $id);
        $data = array('logic_delete' => 1, 'delete_time' => getTime());
        return $this->save($data, $where);
    }

    /**
     * 获取默认缓存KEY。
     * @return type
     */
    protected function getCacheKey() {
        return $this->getTable();
    }

    /**
     * 获取全局缓存数据
     * @param type $key
     * @return type
     */
    protected function getGlobalCache($key = null) {
        $data = null;
        if ($this->globalCache) {
            if (empty($key))
                $key = $this->getCacheKey();
            $data = $this->globalCache->get($key);
        }
        return $data;
    }

    /**
     * 设置全局缓存数据
     * @param type $data
     * @param type $key
     * @param type $expire
     */
    protected function setGlobalCache($data, $key = null, $expire = null) {
        if ($this->globalCache) {
            if (empty($key))
                $key = $this->getCacheKey();
            return $this->globalCache->set($key, $data, $expire);
        }
    }

    /**
     * 移除全局缓存数据
     * @param type $key
     * @param type $expire
     */
    protected function rmGlobalCache($key = null) {
        if ($this->globalCache) {
            if (empty($key))
                $key = $this->getCacheKey();
            $this->globalCache->rm($key);
        }
    }

    /**
     * 获取本地文件缓存数据
     * @param type $key
     * @return type
     */
    protected function getLocalFileCache($key = null) {
        $data = null;
        if ($this->fileCache) {
            if ($this->hasUpdateFlag($key))
                return null;
            if (empty($key))
                $key = $this->getCacheKey();
            $data = $this->fileCache->get($key);
        }
        return $data;
    }

    /**
     * 设置本地文件缓存数据
     * @param type $data
     * @param type $key
     * @param type $expire
     */
    protected function setLocalFileCache($data, $key = null, $expire = null) {
        if ($this->fileCache) {
            if (empty($key))
                $key = $this->getCacheKey();
            $this->fileCache->set($key, $data, $expire);
        }
    }

    /**
     * 清除本地文件缓存数据     
     * @param type $key     
     */
    protected function clearLocalFileCache($key = null) {
        if ($this->fileCache) {
            if (empty($key))
                $key = $this->getCacheKey();
            $this->fileCache->rm($key);
        }
    }

    /**
     * 根据KEY获取缓存数据
     * @param type $key
     * @return type
     */
    protected function getCache($key = null) {
        $data = null;
        if (empty($key))
            $key = $this->getCacheKey();
        if (!$this->hasUpdateFlag($key))
            $data = $this->cache->get($key);
        return $data;
    }

    /**
     * 设置缓存数据。
     * @param type $data     
     * @param type $key
     * @param type $expire
     * @return type
     */
    protected function setCache($data, $key = null, $expire = null) {
        if ($this->cache) {
            if (empty($key))
                $key = $this->getCacheKey();
            $result = $this->cache->set($key, $data, $expire);
            if ($result) {
                $this->syncCacheFlag($key);
            }
        }
    }

    /**
     * 清除缓存     
     * @param type $key
     */
    protected function clearCache($key = null) {
        if ($this->cache) {
            if (empty($key))
                $key = $this->getCacheKey();
            $this->cache->rm($key);
        }
    }

    /**
     * 设置全局缓存标识。
     * @param type $key
     * @return type
     */
    protected function setGlobalCacheFlag($key = null) {
        if (!empty($this->globalCache)) {
            if (empty($key))
                $key = $this->getCacheKey();
            $key = 'gflag_' . $key;
            return $this->globalCache->set($key, time());
        } else
            return null;
    }

    /**
     * 获取全局缓存标识。
     * @param type $key
     * @return type
     */
    protected function getGlobalCacheFlag($key = null) {
        if (!empty($this->globalCache)) {
            if (empty($key))
                $key = $this->getCacheKey();
            $key = 'gflag_' . $key;
            return $this->globalCache->get($key);
        } else
            return null;
    }

    /**
     * 获取本地缓存标识
     * @param string $key
     * @return type
     */
    private function getCacheFlag($key = null) {
        if (!empty($this->cache)) {
            if (empty($key))
                $key = $this->getCacheKey();
            $key = 'flag_' . $key;
            $flag = $this->cache->get($key);
        } else
            $flag = false;
        return $flag;
    }

    /**
     * 同步本地和全局缓存标识。     
     * @param string $key
     * @return type
     */
    protected function syncCacheFlag($key = null) {
        $flag = false;
        if (!empty($this->cache)) {
            if (empty($key))
                $key = $this->getCacheKey();
            $data = $this->getGlobalCacheFlag($key);
            $key = 'flag_' . $key;
            $flag = $this->cache->set($key, $data);
        }
        return $flag;
    }

    /**
     * 判断数据标识是否有更新(如果有更新则本地需要更新缓存)
     * @param type $key
     * @return type
     */
    protected function hasUpdateFlag($key = null) {
        $globalFlag = $this->getGlobalCacheFlag($key);
        if (empty($globalFlag))
            return false;
        $localFlag = $this->getCacheFlag($key);
        //本地标示与全局标示比较
        if ($localFlag != $globalFlag)
            return true;
        else
            return false;
    }

    /**
     * 清空已经存储的所有的元素     
     * @return type
     */
    protected function clearGlobalCache() {
        if (!empty($this->globalCache))
            return $this->globalCache->clear();
    }

    /**
     * 加锁。
     * @param type $key 
     * @param type $waitime 等待时间(毫秒)
     * @param type $timeout 超时时间(秒)
     */
    protected function lock($key = null, $waitime = 500, $timeout = 5) {
        $totalWaitime = 0;
        //一微秒等于百万分之一秒。
        $waitime = $waitime * 1000; //毫秒转成微秒
        $time = $timeout * 1000000; //秒转成微秒
        $data = $this->getGlobalCache($key);
        if ($data) {
            set_time_limit(0);
            while ($totalWaitime < $time) {
                usleep($waitime);
                if (!$this->getGlobalCache($key)) {
                    return;
                }
                $totalWaitime += $waitime;
            }
            if ($totalWaitime >= $time) {
                $this->rmGlobalCache($key);
                //throw new \Exception('can not get lock for waiting ' . $timeout . 's.');
            }
        } else
            $this->setGlobalCache(1, $key, $timeout);
    }

    /**
     * 解锁。
     * @param type $key
     */
    protected function unLock($key = null) {
        $this->rmGlobalCache($key);
    }

    /**
     * 上报日志信息到服务器
     * @param type 类型如：(weixin,redis,subscribe,publish,mysql)         
     * @param msg 消息内容
     * @param code 消息code
     * @param server 对应服务器
     * @param level  等级(FATAL、ERROR、WARN、INFO、DEBUG)
     */
    public function logReport($type, $msg, $code = '', $server = '', $level = 'ERROR') {
        $sql = 'insert into ' . $this->tablePrefix . 'sys_err_log set log_type=\'' . $type . '\',log_msg=\'' . $msg . '\'';
        $sql .= ',log_code=\'' . $code . '\',log_server=\'' . $server . '\',log_level=\'' . $level . '\',log_time=now()';
        return $this->db(true)->execute($sql);
    }

}
