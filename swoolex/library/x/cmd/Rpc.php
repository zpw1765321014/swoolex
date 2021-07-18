<?php
/**
 * +----------------------------------------------------------------------
 * 自定义CMD命令 - HTTP-RPC 控制台组件创建
 * +----------------------------------------------------------------------
 * 官网：https://www.sw-x.cn
 * +----------------------------------------------------------------------
 * 作者：小黄牛 <1731223728@qq.com>
 * +----------------------------------------------------------------------
 * 开源协议：http://www.apache.org/licenses/LICENSE-2.0
 * +----------------------------------------------------------------------
*/

namespace x\cmd;

class Rpc
{
    /**
     * 命令行参数
    */
    private $argv=[];

    /**
     * 调用入口
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2020.05.27
     * @deprecated 暂不启用
     * @global 无
     * @param array $argv
     * @return void
    */
    public function run($argv) {
        $this->argv = $argv;
        if (empty($argv[2])) die('SwooleX-ERROR：sw-x rpc missing parameter 1！'.PHP_EOL);
        if ($argv[2] != 'start') die('SwooleX-ERROR：Correct writing sw-x rpc start'.PHP_EOL);

        if (\x\Config::get('rpc.http_rpc_is') != true) {
            die('SwooleX-ERROR：sw-x rpc WEB组件，只允许设置为服务中心的应用，才能部署安装。请在/config/rpc.php 文件中进行设置'.PHP_EOL);
        }

        $this->copy_controller();
        $this->copy_view();

        $html  = 'SwooleX-ERROR：sw-x rpc Create Success！'.PHP_EOL.PHP_EOL;
        $html .= 'HTTP-RPC监控台-WEB路由地址：/HttpRpc/login'.PHP_EOL;
        $html .= '初始化账号密码在：/config/rpc.php 文件中进行修改。'.PHP_EOL;

        die($html);
    }

    /**
     * 复制控制器到指定位置
     * @todo 无
     * @author 小黄牛
     * @version v1.2.22 + 2021.1.7
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    private function copy_controller() {
        $dir = ROOT_PATH.'/app/controller/HttpRpc.php';
        if (file_exists($dir)) return false;

        return copy(dirname(__FILE__).'/rpc/controller/HttpRpc.php', $dir);
    }

    /**
     * 复制视图到指定位置
     * @todo 无
     * @author 小黄牛
     * @version v1.2.22 + 2021.1.7
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    private function copy_view() {
        $dir = ROOT_PATH.'/app/view/HttpRpc/';
        if (file_exists($dir)) return false;
        mkdir($dir, 0755);

        $file = dirname(__FILE__).'/rpc/view/';
        $temp = scandir($file);
        //遍历文件夹
        foreach ($temp as $v){
            if ($v=='.' || $v=='..') continue;
            copy($file.$v, $dir.$v);
        }

        return true;
    }

}