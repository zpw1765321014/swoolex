<?php
// +----------------------------------------------------------------------
// | 数据库模型类
// +----------------------------------------------------------------------
// | Copyright (c) 2018 https://blog.junphp.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小黄牛 <1731223728@qq.com>
// +----------------------------------------------------------------------

namespace x;

class Model
{
    /**
     * 大写字母
    */
    private $_word = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
    /**
     * 表名
    */
    protected $table;
    /**
     * Db实例
    */
    protected $Db;

    /**
     * 初始化连接池
     * @todo 无
     * @author 小黄牛
     * @version v1.1.3 + 2020.07.11
     * @deprecated 暂不启用
     * @global 无
     * @param string $type 连接池类型select或者log，为空则为写入
     * @return void
    */
    public function __construct($type=null) {
        // 获取子类名
        $array = explode('\\', static::class);
        $class = rtrim(end($array), 'Model');

        // 为表名大写字母转_符号拼接
        foreach ($this->_word as $v) {
            $class = str_replace($v, '_'.$v, $class);
        }
        $this->table = strtolower(ltrim($class, '_'));
        // 自动创建Db实例
        $this->Db = new \x\Db($type);
        $this->Db->name($this->table);
    }

    /**
     * 当实例使用完成后，自动归还连接池
     * @todo 无
     * @author 小黄牛
     * @version v1.1.3 + 2020.07.11
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public function __destruct() {
		$this->Db->return();
    }
    
    /**
     * 驱动函数注入
     * @todo 无
     * @author 小黄牛
     * @version v1.1.3 + 2020.07.11
     * @deprecated 暂不启用
     * @global 无
     * @return void
    */
    public function __call($name, $arguments=[]) {
        if (!$this->Db) return false;
        if (empty($name)) return false;
        
        return $this->Db->$name(...$arguments);
    }
}