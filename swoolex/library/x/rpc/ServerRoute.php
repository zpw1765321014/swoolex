<?php
/**
 * +----------------------------------------------------------------------
 * 微服务-服务端路由转发
 * +----------------------------------------------------------------------
 * 官网：https://www.sw-x.cn
 * +----------------------------------------------------------------------
 * 作者：小黄牛 <1731223728@qq.com>
 * +----------------------------------------------------------------------
 * 开源协议：http://www.apache.org/licenses/LICENSE-2.0
 * +----------------------------------------------------------------------
*/

namespace x\rpc;

class ServerRoute
{
    /**
     * 微服务请求路由转发
     * @todo 无
     * @author 小黄牛
     * @version v1.2.24 + 2021.1.9
     * @deprecated 暂不启用
     * @global 无
     * @param server $server
     * @param fd $fd
     * @param reactorId $reactorId
     * @param array $data
     * @return void
    */
    public function start($server, $fd, $reactorId, $data) {
        $ServerCurrency = new ServerCurrency();
        if (!$data) return $ServerCurrency->returnJson($server, $fd, '501', 'The data is empty, maybe AES decryption failed！', $data);
        if (empty($data['class'])) return $ServerCurrency->returnJson($server, $fd, '502', 'Parameter class cannot be empty！', $data);
        if (empty($data['function'])) return $ServerCurrency->returnJson($server, $fd, '503', 'Parameter function cannot be empty！', $data);

        $class = '\app\rpc\\'.str_replace('/', '\\', ltrim(rtrim($data['class'], '/'), '/'));
        if (!class_exists($class)) return $ServerCurrency->returnJson($server, $fd, '504', 'The requested processing class does not exist！', $data);
        $ref = new \ReflectionClass($class);
        if (!$ref->hasMethod($data['function'])) return $ServerCurrency->returnJson($server, $fd, '505', 'The requested method does not exist！', $data);

        // 实例化操作方法
        $function = $ref->getmethod($data['function']);
        if ($function->isStatic()) return $ServerCurrency->returnJson($server, $fd, '506', 'Static classes cannot be called！', $data);
        if (!$function->isPublic()) return $ServerCurrency->returnJson($server, $fd, '507', 'Private or protected methods cannot be called！', $data);
        
        // 成员属性注入
        $obj = $ref->newInstance();
        $obj->headers = $data['headers'] ?? [];
        $obj->param = $data['param'] ?? [];
        
        try {
            // 调用服务
            $return = $function->invokeArgs($obj, []);
            $return = $return ? $return : [];
            // 记录主动错误日志
            if (isset($obj->rpc_error) && $obj->rpc_error == true) {
                // 主动抛出错误日志内容
                if (isset($obj->rpc_msg)) {
                    $return = $obj->rpc_msg;
                }
                $this->create_rpc_error_log($data, $return);
            }
        } catch (\Throwable $throwable) {
            $msg = $throwable->getMessage().' Line：'.$throwable->getFile().'->'.$throwable->getLine();
            $return = false;
            $this->create_rpc_error_log($data, $msg);
        }

        return $ServerCurrency->returnJson($server, $fd, '200', ((isset($obj->msg)) ? $obj->msg : 'SUCCESS'), $return);
    }

    /**
     * 记录主动错误日志到Redis
     * @todo 无
     * @author 小黄牛
     * @version v1.2.24 + 2021.1.9
     * @deprecated 暂不启用
     * @global 无
     * @param string $data 请求节点信息
     * @param mixed $return 返回值
     * @return void
    */
    private function create_rpc_error_log($data, $return) {
        $max = \x\Config::get('rpc.rpc_error_max');

        $key = 'err_'.str_replace('/', '_', $data['class']).'|'.$data['function'];
        $redis = new \x\Redis();
        // 获取长度
        $res =  $redis->llen('rpc_err_list');
        if ($res == 0) {
            // 写入文件队列
            $redis->lpush('rpc_err_list', $key);
        }

        // 写入错误日志
        $ip = swoole_get_local_ip();
        $data['ip'] = current($ip);
        $data['port'] = \x\Config::get('server.port');
        $data['date'] = date('Y-m-d H:i:s', time());
        $length = $redis->lpush($key, json_encode(['config'=>$data, 'return' => $return], JSON_UNESCAPED_UNICODE));
        if ($length >= $max) {
            $redis->ltrim($key, 0, $max);
        }

        $redis->return();
    }
}