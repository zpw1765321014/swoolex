<?php
/**
 * +----------------------------------------------------------------------
 * Worker 进程 / Task 进程启动
 * +----------------------------------------------------------------------
 * 官网：https://www.sw-x.cn
 * +----------------------------------------------------------------------
 * 作者：小黄牛 <1731223728@qq.com>
 * +----------------------------------------------------------------------
 * 开源协议：http://www.apache.org/licenses/LICENSE-2.0
 * +----------------------------------------------------------------------
*/

namespace event;

class onWorkerStart
{
    /**
	 * 启动实例
	*/
    public $server;

    /**
     * 统一回调入口
     * @todo 无
     * @author 小黄牛
     * @version v1.1.4 + 2020.07.12
     * @deprecated 暂不启用
     * @global 无
     * @param Swoole $server
     * @param int $workerId 进程ID
     * @return void
    */
    public function run($server, $workerId) {
        $this->server = $server;

        // 初始化路由表
        \x\doc\Table::run()->start();

        $config = \x\Config::get('server');
        /*
        可以将公用的，不易变的php文件放置到onWorkerStart之前。
        这样虽然不能重载入代码，
        但所有worker是共享的，不需要额外的内存来保存这些数据。
        onWorkerStart之后的代码每个worker都需要在内存中保存一份
        workerId大于配置文件中worker_num的，
        则为task worker进程，反则是普通worker进程
        */
        if ($workerId >= $config['worker_num']){
            swoole_set_process_name($config['tasker']);

            if (is_file($config['tasker_pid_file'])) {
                file_put_contents($config['tasker_pid_file'], $workerId.':'.$server->worker_pid.'|', FILE_APPEND);
            }
        } else {
            swoole_set_process_name($config['worker']);
            if (is_file($config['worker_pid_file'])) {
                file_put_contents($config['worker_pid_file'], $workerId.':'.$server->worker_pid.'|', FILE_APPEND );
            }
        }

        // 启动数据库连接池
        $this->start_mysql($workerId);
        // 启动Redis连接池
        $this->start_redis($workerId);

        // 自动载入所有定时任务
        if ($workerId == 0) {
            // 初始化微服务
            if (\x\Config::get('rpc.http_rpc_is') == true) {
                \x\Rpc::run()->start();
            }

            $crontab_list = \x\Config::get('crontab');
            foreach ($crontab_list as $app=>$fun) {
                // 载入定时器
                $obj = new $app();
                $obj->$fun($server);
            }

            // MQTT设备在线状态更新
            if (\x\Config::get('server.sw_service_type') == 'mqtt') {
                $this->mqtt_crontab();
            }
        }
        
        // 调用二次转发，不做重载
        $on = new \app\event\onWorkerStart;
        $on->run($server, $workerId);
    }

    /**
     * MQTT设备在线状态更新
     * @todo 无
     * @author 小黄牛
     * @version v2.0.11 + 2021.07.02
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    private function mqtt_crontab() {
        $time = \x\Config::get('mqtt.ping_crontab_time')*1000;
        $ping_max_time = \x\Config::get('mqtt.ping_max_time');
        $server = $this->server;

        \Swoole\Timer::tick($time, function ($timer_id) use ($server, $ping_max_time) {
            $times = time();
            foreach ($this->server->device_list as $v) {
                // 过期了
                if ($v['status'] == 1 && ($v['ping_time']+$ping_max_time) < $times) {
                    $this->server->device_list->set($v['client_id'], [
                        'status' => 2, // 离线
                    ]);
                }
            }
        });
    }
    
    /**
     * 打开Mysql连接池
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2020.05.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    private function start_mysql($workerId) {
        // 启动数据库连接池
        \x\db\MysqlPool::run()->init();
        // 启动连接池检测定时器
        \x\db\MysqlPool::run()->timing_recovery($workerId);

    }
    
    /**
     * 打开Redis连接池
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2020.05.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    private function start_redis($workerId) {
        // 启动数据库连接池
        \x\redis\Redis2Pool::run()->init();
        // 启动连接池检测定时器
        \x\redis\Redis2Pool::run()->timing_recovery($workerId);
    }
}

