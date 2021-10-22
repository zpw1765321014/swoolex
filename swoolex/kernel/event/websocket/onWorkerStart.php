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

namespace event\websocket;

class onWorkerStart {
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

        $this->mount($workerId);

        // 调用二次转发，不做重载
        $on = new \box\event\server\onWorkerStart;
        $on->run($server, $workerId);
        
        // 生命周期转发
        \design\Lifecycle::worker_start($workerId);
    }

    /**
     * 任务挂载
     * @todo 无
     * @author 小黄牛
     * @version v1.1.4 + 2020.07.12
     * @deprecated 暂不启用
     * @global 无
     * @param int $workerId 进程ID
     * @return void
    */
    private function mount($workerId) {
        // 初始化HTTP路由
        \design\MountEvent::WorkerStart_RouteStart_Http();
        // 初始化WebSocket路由
        \design\MountEvent::WorkerStart_RouteStart_WebSocket();
        // 挂载PID-ENV更新
        \design\MountEvent::WorkerStart_PidENV($this->server, $workerId);
    }
}