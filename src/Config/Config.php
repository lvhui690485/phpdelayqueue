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

    /*************************************基本不动的配置***************************************************/
    static $prifix = 'dq_';
    //进程
    static $processArr = [
        'dq_master' => 'dq-master', //master
        'dq_slave' => 'dq-slave', //slave
        'dq_delay' => 'dq-delay', //延迟
        'dq_consume' => 'dq-consume', //消费
    ];

    //延迟扫描队列
    const DQ_DELAY_NUM = 1;

    //消费队列
    const DQ_CONSUME_NUM = 2;

    //允许的命令
    const COMMAND = ['reload', 'start', 'stop', 'slave'];

    const CURD_COMMAND = ['add', 'get', 'del'];
    //redis配置
    const REDIS_CONFIG = [
        'socket_type' => 'tcp',
        'host' => 'r-bp1o6ysl4fbkblg1qk.redis.rds.aliyuncs.com',
        'password' => 'yuewan@2019',
        'port' => 6379,
        'timeout' => 0
    ];

    //redis配置
//    const REDIS_CONFIG = [
//        'socket_type' => 'tcp',
//        'host' => '127.0.0.1',
//        'password' => '',
//        'port' => 6379,
//        'timeout' => 0
//    ];

    /*************************************需要进行修改的配置***************************************************/
    //php bin 路径
    static $phpBinPath = '/usr/local/php-7.0.27/bin/php';

    //项目路径 独立成项目后会改变
    static $projectPath = '/data/web/yaf/manager.ad.hzyuewan.com';

    //日志路径
    const LOG_PATH = '/data/web/yaf/adLog';

    //最大运行脚本数量
    const MAX_RUNNING_TASK_NUM = 50;

    //钉钉报警url
    const DING_WARNING_URL = 'https://oapi.dingtalk.com/robot/send';

    //默认报警人
    const ORIGIN_DING_AT = 15258824343;

    //默认报警群
    const ORIGIN_DING_TOKEN = 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be';

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

    //1表示走config配置，2表示走redis
    static $topicType = 1;

    /**
     * topic信息后续需要改成线上配置 入库
     * @var array
     */
    static $topic = [
        'ksPushPlan' => [
            'ttl' => 600,
            'running_task' => 2,
            'is_retry' => true,
            'max_retry_time' => 1,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 18768122553,
            'level' => 2
        ],

        'ucPushPlan' => [
            'ttl' => 600,
            'running_task' => 2,
            'is_retry' => true,
            'max_retry_time' => 1,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 19814729813,
            'level' => 2
        ],

        'gdtPushSource' => [
            'ttl' => 60,
            'running_task' => 8,
            'is_retry' => true,
            'max_retry_time' => 3,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 15258824343,
            'level' => 3
        ],

        'ucPushSource' => [
            'ttl' => 60,
            'running_task' => 8,
            'is_retry' => true,
            'max_retry_time' => 3,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 19814729813,
            'level' => 3
        ],

        'ksPushSource' => [
            'ttl' => 70,
            'running_task' => 8,
            'is_retry' => true,
            'max_retry_time' => 3,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 18768122553,
            'level' => 3
        ],

        'baiduPushSource' => [
            'ttl' => 75,
            'running_task' => 5,
            'is_retry' => true,
            'max_retry_time' => 3,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 15623267851,
            'level' => 3
        ],

        'toutiaoPushSource' => [
            'ttl' => 60,
            'running_task' => 8,
            'is_retry' => true,
            'max_retry_time' => 3,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 15623267851,
            'level' => 3
        ],

        'toutiaoPerson' => [
            'ttl' => 60,
            'running_task' => 10,
            'is_retry' => true,
            'max_retry_time' => 2,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 18768122553,
            'level' => 1
        ],

        'toutiaoPersonPush' => [
            'ttl' => 600,
            'running_task' => 10,
            'is_retry' => true,
            'max_retry_time' => 1,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 18768122553,
            'level' => 1
        ],

        'bdPerson' => [
            'ttl' => 60,
            'running_task' => 5,
            'is_retry' => true,
            'max_retry_time' => 3,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 15623267851,
            'level' => 1
        ],

        'ucPerson' => [
            'ttl' => 80,
            'running_task' => 3,
            'is_retry' => true,
            'max_retry_time' => 1,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 19814729813,
            'level' => 1
        ],

        'gdtPerson' => [
            'ttl' => 300,
            'running_task' => 1,
            'is_retry' => true,
            'max_retry_time' => 2,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 19814729813,
            'level' => 1
        ],

        'uploadApk' => [
            'ttl' => 10,
            'running_task' => 2,
            'is_retry' => false,
            'max_retry_time' => 3,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 15258824343,
            'level' => 1
        ],

        'ksRtms' => [
            'ttl' => 300,
            'running_task' => 5,
            'is_retry' => true,
            'max_retry_time' => 2,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 18768122553,
            'level' => 2
        ],

        'ksPerson' => [
            'ttl' => 60,
            'running_task' => 5,
            'is_retry' => true,
            'max_retry_time' => 2,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 18768122553,
            'level' => 1
        ],

        'ksPersonPush' => [
            'ttl' => 180,
            'running_task' => 5,
            'is_retry' => true,
            'max_retry_time' => 2,
            'ding_token' => 'fa53745cab0223c7aba083baeeefe3f05ab937b934a5e58a5200b9ee7a1ec0be',
            'ding_at' => 18768122553,
            'level' => 3
        ],
    ];
}