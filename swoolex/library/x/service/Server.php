<?php
/**
 * +----------------------------------------------------------------------
 * 启动服务
 * +----------------------------------------------------------------------
 * 官网：https://www.sw-x.cn
 * +----------------------------------------------------------------------
 * 作者：小黄牛 <1731223728@qq.com>
 * +----------------------------------------------------------------------
 * 开源协议：http://www.apache.org/licenses/LICENSE-2.0
 * +----------------------------------------------------------------------
*/

namespace x\service;

use Swoole\Table;

class Server
{    
    /**
	 * 启动实例
	*/
    private $service;
    /**
     * 配置
    */
    private $config;
    
    /**
     * 应用启动入口
     * @todo 无
     * @author 小黄牛
     * @version v2.0.7 + 2020.04.28
     * @deprecated 暂不启用
     * @global 无
     * @param string $server 启动的服务类型
     * @param string $option 是否守护进程启动 -d
     * @return void
    */
    public function start($server, $option) {
        if ($option == '-d') {
            \x\Config::set('server.daemonize', true);
        }
        $config = \x\Config::get('server');

        # WSS配置
        $wss = SWOOLE_SOCK_TCP;
        $set = [
            'open_http2_protocol' => $config['open_http2_protocol'],
            'task_worker_num' => $config['task_worker_num'],
            'task_ipc_mode' => $config['task_ipc_mode'],
            'task_max_request' => $config['task_max_request'],
            'task_enable_coroutine' => $config['task_enable_coroutine'],
            'task_use_object' => $config['task_use_object'],
            'dispatch_mode' => $config['dispatch_mode'],
            'daemonize' => $config['daemonize'],
            'log_level' => $config['log_level'],
            'open_tcp_keepalive' => $config['open_tcp_keepalive'],
            'heartbeat_check_interval' => $config['heartbeat_check_interval'],
            'heartbeat_idle_time' => $config['heartbeat_idle_time'],
            'package_max_length' => $config['package_max_length'],
            'open_mqtt_protocol' => $config['open_mqtt_protocol'],
            'enable_coroutine' => $config['enable_coroutine'],
        ];
        if ($config['backlog']) $set['backlog'] = $config['backlog'];
        if ($config['reactor_num']) $set['reactor_num'] = $config['reactor_num'];
        if ($config['worker_num']) $set['worker_num'] = $config['worker_num'];
        if ($config['max_request']) $set['max_request'] = $config['max_request'];
        if ($config['max_connection']) $set['max_connection'] = $config['max_connection'];
        if ($config['task_tmpdir']) $set['task_tmpdir'] = $config['task_tmpdir'];
        if ($config['log_file']) $set['log_file'] = $config['log_file'];
        if ($config['document_root']) {
            $set['document_root'] = $config['document_root'];
            $set['enable_static_handler'] = true;
        }
        
        if ($config['open_tcp_keepalive']) {
            $set['tcp_keepidle'] = $config['tcp_keepidle'];
            $set['tcp_keepinterval'] = $config['tcp_keepinterval'];
            $set['tcp_keepcount'] = $config['tcp_keepcount'];
        }

        // 配置HTTPS
        if ($config['ssl_cert_file'] && $config['ssl_key_file']) {
            $set['ssl_cert_file'] = $config['ssl_cert_file'];
            $set['ssl_key_file'] = $config['ssl_key_file'];
            $wss = SWOOLE_SOCK_TCP | SWOOLE_SSL;
        }
        // 启动MQTT协议
        if ($server == 'mqtt') {
            $set['open_mqtt_protocol'] = true;
        }
        switch ($server) {
            case 'http':
                $this->service = new \Swoole\Http\Server($config['host'], $config['port'], SWOOLE_PROCESS, $wss);
            break;
            case 'websocket':
                $this->service = new \Swoole\WebSocket\Server($config['host'], $config['port'], SWOOLE_PROCESS, $wss);
            break;
            case 'server':
            case 'rpc':
            case 'mqtt':
                $this->service = new \Swoole\Server($config['host'], $config['port'], SWOOLE_PROCESS, $wss);
            break;
        }
        // 启动类型写入配置项
        \x\Config::set('server.sw_service_type', $server);
        
        $this->config = $config;
        // 注入配置
        $this->service->set($set);
        // 进入内存表创建
        if ($server == 'mqtt') {
            $this->create_mqtt_table();
        }
        // 进行事件绑定
        $this->event_binding();
    }

    /**
     * MQTT服务创建内存表
     * @todo 无
     * @author 小黄牛
     * @version v2.0.11 + 2021.07.03
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    private function create_mqtt_table() {
        // 创建设备表
        $device_list = new Table(\x\Config::get('mqtt.device_max_num'));
        $device_list->column('fd', Table::TYPE_INT, 12); // FD
        $device_list->column('client_id', Table::TYPE_STRING, 64); // 客户端
        $device_list->column('status', Table::TYPE_INT, 1); // 离线状态
        $device_list->column('ping_time', Table::TYPE_INT, 12); // 心跳更新时间
        $device_list->create();
        // 将表附加到SW实例里，方便后续使用
        $this->service->device_list = $device_list;

        // 创建FD表，用于更新设备在线状态
        $device_fd = new Table(\x\Config::get('mqtt.device_max_num'));
        $device_fd->column('client_id', Table::TYPE_STRING, 64); // 客户端
        $device_fd->create();
        // 将表附加到SW实例里，方便后续使用
        $this->service->device_fd = $device_fd;
    }

    /**
     * 事件绑定
     * @todo 无
     * @author 小黄牛
     * @version v1.1.1 + 2020.07.08
     * @deprecated 暂不启用
     * @global 无
     * @param Swoole\Server $service 实例
     * @return void
    */
    protected function event_binding() {
        # 监听进程启动事件
        $this->service->on('Start', [$this->ioc('onStart'), 'run']);
        # 监听进程关闭事件
        $this->service->on('Shutdown', [$this->ioc('onShutdown'), 'run']);
        # Worker 进程 / Task 进程启动
        $this->service->on('WorkerStart', [$this->ioc('onWorkerStart'), 'run']);
        # 在 (Worker) 进程终止时发生
        $this->service->on('WorkerStop', [$this->ioc('onWorkerStop'), 'run']);
        # 在 (Worker) 进程重启前触发
        $this->service->on('WorkerExit', [$this->ioc('onWorkerExit'), 'run']);
        # 有新的连接进入
        $this->service->on('Connect', [$this->ioc('onConnect'), 'run']);
        # 接收到数据时
        $this->service->on('Receive', [$this->ioc('onReceive'), 'run']);
        # 接收到 UDP 数据包时
        $this->service->on('Packet', [$this->ioc('onPacket'), 'run']);
        # 监听客户端退出事件
        $this->service->on('Close', [$this->ioc('onClose'), 'run']);
        # 接收到异步任务时
        $this->service->on('Task', [$this->ioc('onTask'), 'run']);
        # 异步任务完成时
        $this->service->on('Finish', [$this->ioc('onFinish'), 'run']);
        # 接收到unixSocket时
        $this->service->on('PipeMessage', [$this->ioc('onPipeMessage'), 'run']);
        # Worker/Task 进程发生异常后
        $this->service->on('WorkerError', [$this->ioc('onWorkerError'), 'run']);
        # 当管理进程启动时
        $this->service->on('ManagerStart', [$this->ioc('onManagerStart'), 'run']);
        # 当管理进程结束时
        $this->service->on('ManagerStop', [$this->ioc('onManagerStop'), 'run']);
        # Worker进程重载前
        $this->service->on('BeforeReload', [$this->ioc('onBeforeReload'), 'run']);
        # Worker进程重载后
        $this->service->on('AfterReload', [$this->ioc('onAfterReload'), 'run']);
        # 监听WebSokcet握手过程
        if (isset($this->config['is_onHandShake']) && $this->config['is_onHandShake']==true) {
            $this->service->on('HandShake', [$this->ioc('onHandShake', $this->service), 'run']);
        }
        # 监听WebSocket握手成功
        $this->service->on('Open', [$this->ioc('onOpen'), 'run']);
        # 监听客户端消息发送请求
        $this->service->on('Message', [$this->ioc('onMessage'), 'run']);
        # 监听外部调用请求
        $this->service->on('Request', [$this->ioc('onRequest', $this->service, $this->config), 'run']);

        # 启动服务
        $this->service->start();
    }

    /**
     * 构造回调事件的new对象
     * @todo 无
     * @author 小黄牛
     * @version v1.1.1 + 2020.07.08
     * @deprecated 暂不启用
     * @global 无
     * @param string $event 事件对象名称
     * @param array $argc 其余参数
     * @return void
    */
    private function ioc($event, ...$argc) {
        $class = '\event\\'.$event;
        if ($event == 'onMessage') {
            if (!isset($this->config['is_onMessage']) || $this->config['is_onMessage'] != true) {
                # 关闭系统分包流程
                $class = '\app'.$class;
            }
        }
        
        $reflection = new \ReflectionClass($class);
        return $reflection->newInstanceArgs($argc);
    }
}