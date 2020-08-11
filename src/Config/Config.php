<?php
/**
 * Created by PhpStorm.
 * User: lvhui
 * Date: 2020/7/29
 * Time: 上午11:49
 */

namespace PhpDelayQueue\Config;

use App\Utils\Singleton;

Class Config
{
    use Singleton;

    static $prifix = 'dq_';
    //进程
    static $processArr = [
        'dq_master' => 'dq-master', //master
        'dq_slave' => 'dq-slave', //slave
        'dq_delay' => 'dq-delay', //延迟
        'dq_consume' => 'dq-consume', //消费
    ];

    //消费队列进程名称
    const DQ_CONSUME = 'dq-consume';

    //延迟扫描队列
    const DQ_DELAY_NUM = 1;

    //消费队列
    const DQ_CONSUME_NUM = 1;

    static $phpBinPath = '/usr/local/php-7.0.27/bin/php';

    //允许的命令
    const COMMAND = ['reload', 'start', 'stop', 'slave'];

    const CURD_COMMAND = ['add', 'get', 'del'];

    //日志路径
    const LOG_PATH = '/data/web/yaf/adLog';

    //最大运行脚本数量
    const MAX_RUNNING_TASK_NUM = 55;

    //钉钉报警url
    const DING_WARNING_URL = 'https://oapi.dingtalk.com/robot/send';

    //默认报警人
    const ORIGIN_DING_AT = 15258824343;

    //默认报警群
    const ORIGIN_DING_TOKEN = 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be';
    //redis配置
//    const REDIS_CONFIG = [
//        'socket_type' => 'tcp',
//        'host' => 'r-bp1o6ysl4fbkblg1qk.redis.rds.aliyuncs.com',
//        'password' => 'yuewan@2019',
//        'port' => 6379,
//        'timeout' => 0
//    ];


    //redis配置
    const REDIS_CONFIG = [
        'socket_type' => 'tcp',
        'host' => '127.0.0.1',
        'password' => '',
        'port' => 6379,
        'timeout' => 0
    ];

    /**
     * 数据库配置
     */
    static $db = [
        'host' => '192.168.10.203',
        'port' => '3306',
        'user' => 'syhzyuewan',
        'password' => 'syhzyuewan',
        'database' => 'ad_center',
    ];


    /**
     * topic信息后续需要改成线上配置 入库
     * @var array
     */
    static $topic = [
        'ksPushPlan' => [
            'ttl' => 10,
            'running_task' => 3,
            'is_retry' => true,
            'max_retry_time' => 2,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 18768122553
        ],

        'GdtSource' => [
            'ttl' => 60,
            'running_task' => 10,
            'is_retry' => true,
            'max_retry_time' => 3,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 15258824343
        ],
    ];
}