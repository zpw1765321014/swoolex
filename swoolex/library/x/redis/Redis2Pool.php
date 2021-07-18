<?php
/**
 * +----------------------------------------------------------------------
 * Redis连接池
 * +----------------------------------------------------------------------
 * 官网：https://www.sw-x.cn
 * +----------------------------------------------------------------------
 * 作者：小黄牛 <1731223728@qq.com>
 * +----------------------------------------------------------------------
 * 开源协议：http://www.apache.org/licenses/LICENSE-2.0
 * +----------------------------------------------------------------------
*/

namespace x\redis;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;

class Redis2Pool{
    /**
     * 配置项
    */
    protected $config;
    /**
     * 创建静态对象变量,用于存储唯一的对象实例  
    */
    protected static $instance = null;

    /**
     * 私有化克隆函数，防止外部克隆对象
    */
    private function __clone() {}

    /**
     * 初始化参数
     * @todo 无
     * @author 小黄牛
     * @version v1.0.12 + 2020.04.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    private function __construct() {
        // 读取配置类
        $this->config = \x\Config::get('redis.pool_list');
    }

    /**
     * 单例入口
     * @todo 无
     * @author 小黄牛
     * @version v1.0.12 + 2020.04.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public static function run() {
        # 只有第一次调用，才允许创建对象实例
        if (empty(self::$instance)) {
            self::$instance = new \x\redis\Redis2Pool();
        }
        return self::$instance;
    }

    /**
     * 初始连接池
     * @todo 无
     * @author 小黄牛
     * @version v1.0.12 + 2020.04.29
     * @deprecated 暂不启用
     * @global 无
     * @param string $
     * @param mixed $
     * @return $this|null
    */
    public function init() {
        foreach ($this->config as $key=>$value) {
            $this->config[$key]['connections'] = $this->createRedis($value, $value['pool_num']);
        }
        return $this;
    }
    
    /**
     * 获取一个连接
     * @todo 无
     * @author 小黄牛
     * @version v1.0.12 + 2020.04.29
     * @deprecated 暂不启用
     * @global 无
     * @return obj
    */
    public function pop($key) {
        if (!isset($this->config[$key])) return false;

        if ($this->config[$key]['pool_num'] <= 0) {
            $this->pop_error($key);
            throw new \Exception("Redis ".$key." Pop <= 0");
            return false;
        }

        $this->config[$key]['pool_num']--;
        
        if (!$this->config[$key]['connections']) return false;

        return $this->config[$key]['connections']->get();
    }

    /**
     * 归还一个连接
     * @todo 无
     * @author 小黄牛
     * @version v1.0.12 + 2020.04.29
     * @deprecated 暂不启用
     * @global 无
     * @param obj $obj 数据库连接实例
     * @return void
    */
    public function free($key, $obj) {
        $this->config[$key]['pool_num']++;
        if (!$this->config[$key]['connections']) return false;

        return $this->config[$key]['connections']->put($obj);
    }
    
    /**
     * 定时回收空闲连接
     * @todo 无
     * @author 小黄牛
     * @version v1.0.12 + 2020.04.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public function timing_recovery($workerId) {
        // 5秒更新一次当前Redis连接数
        if (\x\Config::get('redis.is_monitor')) {
            \Swoole\Timer::tick(5000, function () use($workerId) {
                $path = ROOT_PATH.'/other/env/redis_pool_num.count';
                $json = \Swoole\Coroutine\System::readFile($path);
                $array = [];
                if ($json) {
                    $array = json_decode($json, true);
                }
                $num = 0;
                foreach ($this->config as $key=>$value) {
                    $num += $value['pool_num'];
                }
                $array[$workerId] = $num;
                \Swoole\Coroutine\System::writeFile($path, json_encode($array));
                unset($json);
                unset($array);
                unset($path);
            });
        }
    }
    
    /**
     * 清空连接池
     * @todo 无
     * @author 小黄牛
     * @version v1.0.12 + 2020.04.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public function clean() {
        foreach ($this->config as $key=>$value) {
            $value['connections']->close();
            $this->config[$key]['pool_num'] = 0;
        }
    }

    /**
     * 创建数据库连接实例
     * @todo 无
     * @author 小黄牛
     * @version v1.0.12 + 2020.04.29
     * @deprecated 暂不启用
     * @param array $database 连接配置
     * @param int $szie 连接池长度
     * @return swoole_redis
    */
    protected function createRedis($database, $size) {
        return new RedisPool((new RedisConfig)
            ->withHost($database['host'])
            ->withPort($database['port'])
            ->withAuth($database['pwd'])
            ->withDbIndex($database['dbindex'])
            ->withTimeout($database['timeout'])
        , $size);
    }

    /**
     * 当连接池数小于等于0时，回调的通知函数
     * @todo 无
     * @author 小黄牛
     * @version v1.1.5 + 2020.07.15
     * @deprecated 暂不启用
     * @global 无
     * @param string $type 连接池类型
     * @return void
    */
    protected function pop_error($type) {
        $obj = new \other\lifecycle\redis_pop_error();
        $obj->run($type);
        return false;
    }
}