<?php
// +----------------------------------------------------------------------
// | 统一门面
// +----------------------------------------------------------------------
// | Copyright (c) 2018 https://blog.junphp.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小黄牛 <1731223728@qq.com>
// +----------------------------------------------------------------------

namespace x;

class Facade
{
    /**
     * 单例注入
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2020.05.29
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public static function __callStatic($name, $arguments=[]) {
        if (empty($name)) return false;
        
        $class = "\x\\entity\\".str_replace('x\\', '', get_called_class());
        return call_user_func_array([$class::run(), $name], $arguments);
    }
}
