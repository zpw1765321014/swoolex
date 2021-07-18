<?php
/**
 * +----------------------------------------------------------------------
 * 数据库操作类
 * +----------------------------------------------------------------------
 * 官网：https://www.sw-x.cn
 * +----------------------------------------------------------------------
 * 作者：小黄牛 <1731223728@qq.com>
 * +----------------------------------------------------------------------
 * 开源协议：http://www.apache.org/licenses/LICENSE-2.0
 * +----------------------------------------------------------------------
*/

namespace x;

class Db
{
    /**
     * Mysql连接池实例
    */
    private $pool;
    /**
     * 连接池类型
    */
    private $type;
    /**
     * 表前缀
    */
    public $prefix;
    /**
     * SQL构造器反射类
    */
    private $sql_ref;
    /**
     * SQL构造器
    */
    private $sql;
    /**
     * 调试模式
    */
    private $debug;
    /**
     * 是否使用的连接池
    */
    private $is_pool;
    /**
     * 是否归还了链接
    */
    private $return_status = false;
    
    /**
     * 选择连接池
     * @todo 无
     * @author 小黄牛
     * @version v1.2.8 + 2020.07.29
     * @deprecated 暂不启用
     * @global 无
     * @param string $data 连接池标识，不传默认第一个标识
     * @return void
    */
    public function __construct($data=null) {
        // 这里实现不使用连接池
        if (is_array($data)) {
            try {
                $pool = new \PDO('mysql:dbname='.$data['database'].';host='.$data['host'].';port='.$data['port'], $data['user'], $data['password']);
                $pool->exec('SET NAMES '.$data['charset'].';');
            } catch (\PDOException $e) {
                return false;
            }
            $this->prefix = $data['prefix'] ?? '';
            $this->is_pool = false;
        } else {
            $arr = \x\Config::run()->get('mysql.pool_list');
            if (empty($data)) {
                $data = key($arr);
            }
            $this->type = $data;
            # 获取数据表前缀
            $this->prefix = $arr[$this->type]['prefix'];
            $pool = \x\db\MysqlPool::run()->pop($data);
            $this->is_pool = true;
        }

        if (!$pool) {
            return false;
        }

        $this->debug = \x\Config::get('app.de_bug');

        $this->pool = $pool;
    }

    /**
     * 利用析构函数，防止有漏掉没归还的连接，让其自动回收，减少不规范的开发者
     * @todo 无
     * @author 小黄牛
     * @version v1.2.24 + 2021.1.9
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public function __destruct() {
        if ($this->return_status === false) {
            $this->return();
        }
    }

    /**
     * 归还连接池
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2020.05.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public function return() {
        if ($this->return_status !== false) {
            return true;
        }
        if ($this->is_pool) {
            $this->return_status = true;
            return \x\db\MysqlPool::run()->free($this->type, $this->pool);
        } else {
            $this->pool = null;
            $this->return_status = true;
            return true;
        }
        
        return false;
    }

    /**
     * 开启事务
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2020.05.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public function begin() {
        return $this->pool->beginTransaction();
    }

    /**
     * 提交事务
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2020.05.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public function commit() {
        return $this->pool->commit();
    }

    /**
     * 回滚事务
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2020.05.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public function rollback() {
        return $this->pool->rollback();
    }

    /**
     * 执行Query操作
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2020.05.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public function query($sql, $status=true) {
        // 开启调试模式，则记录SQL语句
        if ($this->debug) {
            \x\Log::run()->sql($sql);
        }
        $res = $this->pool->query($sql);
        if ($status !== true) return $res;

        if ($res === false) return false;
        $list = $res->fetchAll(\PDO::FETCH_NAMED);
        if (empty($list)) $list = [];
        return $list;
    }

    /**
     * 执行新增的SQL操作
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2020.05.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public function exec($sql) {
        // 开启调试模式，则记录SQL语句
        if ($this->debug) {
            \x\Log::run()->sql($sql);
        }
        return $this->pool->exec($sql);
    }

    /**
     * SQL构造器注入
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2020.05.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public function __call($name, $arguments=[]) {
        return call_user_func_array([new \x\db\Sql($this), $name], $arguments);
    }
}