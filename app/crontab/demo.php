<?php
/**
 * +----------------------------------------------------------------------
 * 测试的定时器
 * +----------------------------------------------------------------------
 * 官网：https://www.sw-x.cn
 * +----------------------------------------------------------------------
 * 作者：小黄牛 <1731223728@qq.com>
 * +----------------------------------------------------------------------
 * 开源协议：http://www.apache.org/licenses/LICENSE-2.0
 * +----------------------------------------------------------------------
*/

namespace app\crontab;

class demo {

    /**
     * 统一入口
     * @todo 无
     * @author 小黄牛
     * @version v1.0.9 + 2020.04.16
     * @deprecated 暂不启用
     * @global 无
     * @param Swoole\Server $server
     * @return void
    */
    public function run($server) {
        \Swoole\Timer::tick(1000, function ($timer_id) use ($server) {
            echo "SW-X：Hello Word!\n";
        });
    }
}
