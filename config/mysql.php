<?php
// +----------------------------------------------------------------------
// | Mysql配置
// +----------------------------------------------------------------------
// | Copyright (c) 2018 https://blog.junphp.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小黄牛 <1731223728@qq.com>
// +----------------------------------------------------------------------

return [
    // 数据库表前缀
    'prefix'          => 'tp_',
    // SQL防注入过滤函数
    'function'        => ['addslashes'],

    // +----------------------------------------------------------------------
    // | 数据库连接池配置 - 读写分离
    // +----------------------------------------------------------------------   

    // 空闲回收定时检查时间 (S)
    'mysql_timing_recovery' => 600,

    # 读
    // 最小连接数
    'pool_read_min' => 5,
    // 最大连接数
    'pool_read_max' => 10,
    // 空闲连接回收时间
    'pool_read_spare_time' => 3600,
    
    # 写
    // 最小连接数
    'pool_write_min' => 5,
    // 最大连接数
    'pool_write_max' => 10,
    // 空闲连接回收时间
    'pool_write_spare_time' => 3600,

    # 日志
    // 最小连接数
    'pool_log_min' => 5,
    // 最大连接数
    'pool_log_max' => 10,
    // 空闲连接回收时间
    'pool_log_spare_time' => 3600,

    
    // +----------------------------------------------------------------------
    // | 数据库配置 - 读 - 注意：会循环使用配置创建连接池
    // +----------------------------------------------------------------------
    'pool_read_database' => [
        [
            'host'     => '127.0.0.1', // 地址
            'port'     => '3306', // 端口
            'user'     => 'root', // 用户名
            'password' => 'root', // 密码
            'database' => 'websocket', // 库
            'charset'  => 'utf8mb4', // 字符集
            'timeout'  => 10, // 连接超时时间
        ],
    ],
    // +----------------------------------------------------------------------
    // | 数据库配置 - 写 - 注意：会循环使用配置创建连接池
    // +----------------------------------------------------------------------
    'pool_write_database' => [
        [
            'host'     => '127.0.0.1', // 地址
            'port'     => '3306', // 端口
            'user'     => 'root', // 用户名
            'password' => 'root', // 密码
            'database' => 'websocket', // 库
            'charset'  => 'utf8mb4', // 字符集
            'timeout'  => 10, // 连接超时时间
        ],
    ],
    // +----------------------------------------------------------------------
    // | 数据库配置 - 日志 - 注意：会循环使用配置创建连接池
    // +----------------------------------------------------------------------
    'pool_log_database' => [
        [
            'host'     => '127.0.0.1', // 地址
            'port'     => '3306', // 端口
            'user'     => 'root', // 用户名
            'password' => 'root', // 密码
            'database' => 'websocket', // 库
            'charset'  => 'utf8mb4', // 字符集
            'timeout'  => 10, // 连接超时时间
        ],
    ],
];
