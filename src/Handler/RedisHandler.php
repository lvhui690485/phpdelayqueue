<?php

namespace PhpDelayQueue\Handler;


use App\Utils\Singleton;
use PhpDelayQueue\Config\Config;

Class RedisHandler
{
    use Singleton;

    //任务调度
    const DQ_READY_TASK = 'ready_task';

    //低优先级
    const DQ_READY_TASK_LOW = 'ready_task_low';

    //高优先级
    const DQ_READY_TASK_HIGH = 'ready_task_high';

    //有序集合
    const DQ_DEPLY_TASK = 'deply_task';

    //task hash值 以topic隔开
    const TASK_HASH_INFO = 'task_hash_info_%s';

    //计划重试次数
    const TASK_RETRY_TIME = 'task_retry_time_%s';

    //进程id绑定的cmd信息
    const TASK_PROCESS_ID_INFO = 'task_process_id_info_%s';

    //topic信息
    const TOPIC_INFO = 'topic_info_%s';

    //服务重启
    const SERVER_RELOAD = 'server_reload';

    /**
     * 获取redis句柄
     * @return \Redis
     */
    public function getRedisHandler()
    {
        $redis = new \Redis();
        $config = Config::REDIS_CONFIG;
        $redis->connect($config['host'], $config['port']);
        if (isset($config['password'])) {
            $redis->auth($config['password']);
        }
        return $redis;
    }

    /**
     * 利用lua脚本获取有序集合中对应的分数的值
     * @param $key
     * @param $score
     * @return mixed
     */
    function zrangebyscoreByLua($redis, $key, $score)
    {
        $script = "local resultDelay = {};
         local result = redis.call('zrangebyscore', KEYS[1], '0', ARGV[1]) ;
         if next(result) == nil then return resultDelay  
         end;
         if redis.call('zrem', KEYS[1], result[1]) > 0 then 
            table.insert(resultDelay, result[1])
            return resultDelay 
         end;
         return resultDelay;";
        return $redis->eval($script, [$key, $score], 1);
    }

    /**
     * 左入消费队列
     * @param $data
     * @return int
     */
    public function lPushReadyTask($redis, $data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        return $redis->lPush(self::getKey(self::DQ_READY_TASK), $data);
    }

    /**
     * 右入消费队列
     * @param $data
     * @return int
     */
    public function rPushReadyTask($redis, $data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        return $redis->rPush(self::getKey(self::DQ_READY_TASK), $data);
    }

    /**
     * 左入消费队列
     * @param $data
     * @return int
     */
    public function lPushReadyTaskLow($redis, $data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        return $redis->lPush(self::getKey(self::DQ_READY_TASK_LOW), $data);
    }

    /**
     * 左入消费队列
     * @param $data
     * @return int
     */
    public function lPushReadyTaskHigh($redis, $data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        return $redis->lPush(self::getKey(self::DQ_READY_TASK_HIGH), $data);
    }

    /**
     * 出队列堵塞
     * @return mixed
     */
    public function brpopReadyTask($redis)
    {
        return $redis->brPop([self::getKey(self::DQ_READY_TASK_HIGH), self::getKey(self::DQ_READY_TASK), self::getKey(self::DQ_READY_TASK_LOW)], 58);
    }

    /**
     * 右出消费队列
     * @return mixed
     */
    public function rpopReadyTask($redis)
    {
        return $redis->rPop(self::getKey(self::DQ_READY_TASK));
    }


    /**
     * 往集合里面添加
     * @param $value
     * @param $score
     * @return int
     */
    public function zAddTaskSchedule($redis, $value, $score)
    {
        return $redis->zAdd(self::getKey(self::DQ_DEPLY_TASK), $score, $value);
    }

    /**
     * hash set
     * @param $topic
     * @param $jobId
     * @param $value
     * @param int $ttl
     * @return int
     */
    public function hSetTaskInfo($redis, $topic, $jobId, $value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $redis->hSet(self::getKey(self::TASK_HASH_INFO, $topic), $jobId, $value);
        return $redis->expire(self::getKey(self::TASK_HASH_INFO, $topic), 86400 * 30);
    }

    /**
     * hash get
     * @param $topic
     * @param $jobId
     * @return string
     */
    public function hGetTaskInfo($redis, $topic, $jobId)
    {
        return $redis->hGet(self::getKey(self::TASK_HASH_INFO, $topic), $jobId);
    }

    /**
     * hash exists
     * @param $topic
     * @param $jobId
     * @return string
     */
    public function hExistsTaskInfo($redis, $topic, $jobId)
    {
        return $redis->hExists(self::getKey(self::TASK_HASH_INFO, $topic), $jobId);
    }

    /**
     * hash del
     * @param $topic
     * @param $jobId
     * @return string
     */
    public function hDelTaskInfo($redis, $topic, $jobId)
    {
        return $redis->hDel(self::getKey(self::TASK_HASH_INFO, $topic), $jobId);
    }

    /**
     * incr
     * @param $topic
     * @param $jobId
     * @return string
     */
    public function incrTaskRetryTime($redis, $jobId)
    {
        $res = $redis->incr(self::getKey(self::TASK_RETRY_TIME, $jobId), 1);
        $redis->expire(self::getKey(self::TASK_RETRY_TIME, $jobId), 3600);
        return $res;
    }


    /**
     * 设置进程绑定的cmd信息
     * @param $redis
     * @param $processId
     * @param $info
     * @param int $ttl
     * @return mixed
     */
    public function setTaskProcessIdInfo($redis, $processId, $info, $ttl = 3600)
    {
        $res = $redis->set(self::getKey(self::TASK_PROCESS_ID_INFO, $processId), $info);
        $redis->expire(self::getKey(self::TASK_PROCESS_ID_INFO, $processId), $ttl);
        return $res;
    }

    /**
     * 获取进程绑定的cmd信息
     * @param $redis
     * @param $processId
     * @return mixed
     */
    public function getTaskProcessIdInfo($redis, $processId)
    {
        return $redis->get(self::getKey(self::TASK_PROCESS_ID_INFO, $processId));
    }

    /**
     * 删除绑定进程的cmd信息
     * @param $redis
     * @param $processId
     * @return mixed
     */
    public function delTaskProcessIdInfo($redis, $processId)
    {
        return $redis->del(self::getKey(self::TASK_PROCESS_ID_INFO, $processId));
    }


    /**
     * 获取键值,第一个参数是键值,其他参数跟随
     */
    public static function getKey()
    {
        $arr = func_get_args();
        $arr[0] = Config::$prifix . $arr[0];
        return call_user_func_array('sprintf', $arr);
    }

    /**
     * 获取topic
     * @param $topic
     * @return bool|string
     */
    public function getTopicInfo($topic)
    {
        $info = self::getRedisHandler()->get(self::getKey(self::TOPIC_INFO, $topic));
        return json_decode($info, true);
    }

    /**
     * 设置topic信息
     * @param $planId
     * @param $value
     * @param float|int $ttl
     * @return bool
     */
    public function setTopicInfo($topic, $info)
    {
        if (is_array($info)) {
            $info = json_encode($info);
        }
        return self::getRedisHandler()->set(self::getKey(self::TOPIC_INFO, $topic), $info);
    }

    /**
     * 获取状态
     * @return bool|string
     */
    public function getServerStatus()
    {
        return self::getRedisHandler()->get(self::getKey(self::SERVER_RELOAD));
    }

    /**
     * 设置状态
     * @param $value
     * @return bool
     */
    public function setServerStatus($value)
    {
        self::getRedisHandler()->set(self::getKey(self::SERVER_RELOAD), $value);
        return self::getRedisHandler()->expire(self::getKey(self::SERVER_RELOAD), 60);
    }
}