<?php

namespace PhpDelayQueue\Handler;

use App\Utils\Singleton;
use PhpDelayQueue\Config\Config;
use PhpDelayQueue\Init\Init;
use PhpDelayQueue\Tools\DqLog;
use PhpDelayQueue\Tools\Tools;

Class ConsumeHandler
{
    use Singleton;

    /**
     * 消费服务启动
     * @param $num
     */
    public function startConsumeService($num)
    {
        if (Tools::getInstance()->getChildNum(Config::$processArr['dq_consume']) < $num) {
            for ($i = 1; $i <= $num; $i++) {
                $pid = pcntl_fork();
                if ($pid == -1) {
                    DqLog::info('consume', 'fork error', '', 'consume');
                } elseif ($pid) {
                } else {// 子进程处理
                    cli_set_process_title(Config::$processArr['dq_consume'] . '_' . $i);
                    $id = posix_getpid();
                    DqLog::info('consume', 'consume_process_id', $id, 'consume');
                    $redis = (new RedisHandler())->getRedisHandler();
                    $this->consumeReadyList($redis);
                    $redis->close();
                    // 一定要注意退出子进程,否则pcntl_fork() 会被子进程再fork,带来处理上的影响。
                    exit;
                }
            }
        }
    }

    /**
     * 扫描消费队列
     * @param $redis
     */
    private function consumeReadyList($redis)
    {
        while (true) {
            try {
                if ($info = RedisHandler::getInstance()->rpopReadyTask($redis)) {
                    $info = json_decode($info, true);
                    if ($info) {
                        DqLog::info('consume', '检测到一条待消费数据', $info, 'consume');
                        if (!isset($info['topic']) || !isset($info['jobId'])) {
                            DqLog::error('consume', '一条待消费数据结构异常', $info, 'consume');
                            continue;
                        }
                        $detailInfo = $info['body'] ?? '';
                        if (empty($detailInfo) || empty($detailInfo['type'])) {
                            DqLog::error('consume', '一条待消费数据未查询到详细信息', $info, 'consume');
                            //报警
                            Tools::getInstance()->sendOriginWarnToDing('一条待消费数据未查询到详细信息' . json_encode($detailInfo));
                            continue;
                        }
                        switch ($detailInfo['type']) {
                            case 1://脚本回调
                                $detailInfo['topic'] = $info['topic'];
                                $detailInfo['jobId'] = $info['jobId'];
                                self::runScriptTask($detailInfo, $redis);
                                break;
                            case 2://http回调
                                self::runHttpTask($detailInfo);
                                break;
                            case 3://检测脚本是否超时
                                $detailInfo['topic'] = $info['topic'];
                                $detailInfo['jobId'] = $info['jobId'];
                                self::runScrTimeOutTask($detailInfo, $redis);
                                break;
                        }
                    }
                }
            } catch (\Exception $e) {
                DqLog::error('consume', '消费队列有异常 ' . $e->getMessage(), '行数：' . $e->getLine(), 'consume');
            }
            usleep(100000);
        }
    }

    /**
     * 启动脚本
     * @param $detailInfo
     */
    public static function runScriptTask($detailInfo, $redis)
    {
        //限流
        if (self::checkAllRunningTask() >= Config::MAX_RUNNING_TASK_NUM || self::checkTopicRunningTask($detailInfo['payload'] ?? '') >= Config::$topic[$detailInfo['topic']]['running_task']) {
            //延迟消费
            $data = [
                'topic' => $detailInfo['topic'],
                'jobId' => $detailInfo['jobId'],
                'cmd' => 'add',
                'body' => [
                    'type' => 1,
                    'payload' => $detailInfo['payload'],
                    'cmd' => Config::$phpBinPath,
                    'runTime' => time() + mt_rand(1, 60),
                    'param' => $detailInfo['param']
                ],
            ];
            $zsetInfo = ['topic' => $data['topic'], 'jobId' => $data['jobId']];
            CurdlHandler::getInstance()->handleTaskInfo($data);
            DqLog::error('consume', $detailInfo['payload'] . '触发限流,延迟消费', $detailInfo, 'consume');
        } else {
            self::doScriptTask($detailInfo, $redis);
        }
    }

    /**
     * 回调http服务
     * @param $detailInfo
     */
    public static function runHttpTask($detailInfo)
    {

    }

    public static function runScrTimeOutTask($detailInfo, $redis)
    {
        //超时
        $processId = $detailInfo['processId'] ?? 0;
        if ($processId) {
            $isDel = true;
            $processIds = MainHandler::getInstance()->getAllServerId($processId);
            if ($processIds) {
                //记录日志
                DqLog::error('consume', $processId . '-进程超时kill', $detailInfo['cmd'] ?? '', 'consume');
                MainHandler::getInstance()->killServerId($processIds);
                Tools::getInstance()->sendWarnToDing($detailInfo['cmd'] ?? '', $processId, '超时被kill', $detailInfo['topic']);
                //报警
                //判断是否重推
                if (Config::$topic[$detailInfo['topic']]['is_retry'] && RedisHandler::getInstance()->incrTaskRetryTime($redis, $detailInfo['oldBody']['jobId']) <= Config::$topic[$detailInfo['topic']]['max_retry_time']) {
                    //继续推
                    $data = [
                        'topic' => $detailInfo['topic'],
                        'jobId' => $detailInfo['oldBody']['jobId'],
                        'cmd' => 'add',
                        'body' => [
                            'type' => 1,
                            'payload' => $detailInfo['oldBody']['payload'],
                            'cmd' => Config::$phpBinPath,
                            'runTime' => time() + 30,
                            'param' => $detailInfo['oldBody']['param']
                        ],
                    ];
                    CurdlHandler::getInstance()->handleTaskInfo($data);
                    $isDel = false;
                } else {
                    //报警
                    Tools::getInstance()->sendWarnToDing($detailInfo['cmd'] ?? '', $processId, '重推次数达到上线。已丢弃', $detailInfo['topic']);
                    DqLog::error('consume', $processId . '-进程没有设置重推或者重推次数达到上线。已丢弃', $detailInfo['cmd'] ?? '', 'consume');
                }
            }
            if ($isDel) {
                //删除hash&操作mysql
                RedisHandler::getInstance()->hDelTaskInfo($redis, $detailInfo['topic'], $detailInfo['oldBody']['jobId']);
            }
            RedisHandler::getInstance()->hDelTaskInfo($redis, $detailInfo['topic'], $detailInfo['jobId']);
        }
    }

    private static function doScriptTask($detailInfo, $redis)
    {
        DqLog::info('consume', '开始处理待消费数据', $detailInfo, 'consume');
        list($cmd, $cmdName, $param) = self::_checkParam($detailInfo);
        $str = '';
        if (!empty($param)) {
            foreach ($param as $item) {
                $str .= $item . ' ';
            }
        }

        $_command = $cmd . ' ' . $cmdName . ' ' . $str;
        $command = $_command . ' > /dev/null 2>&1 & echo $!';
        $output = [];
        $commandResult = 0;
        exec($command, $output, $commandResult);
        $pid = (int)$output[0];
        if ($commandResult == 0) {
            $data = [
                'topic' => $detailInfo['topic'],
                'jobId' => md5(microtime()),
                'cmd' => 'add',
                'body' => [
                    'type' => 3,
                    'processId' => $pid,
                    'runTime' => time() + Config::$topic[$detailInfo['topic']]['ttl'],
                    'cmd' => $command,
                    'oldBody' => $detailInfo
                ],
            ];
            CurdlHandler::getInstance()->handleTaskInfo($data);
            DqLog::info('consume', '待消费数据' . $cmdName, '执行成功,process_id' . $pid, 'consume');
        } else {
            DqLog::error('consume', '执行待消费脚本异常' . $cmdName, '执行失败', 'consume');
        }
    }

    private static function _checkParam($taskData)
    {
        return [$taskData['cmd'], $taskData['payload'], $taskData['param'] ?? []];
    }

    private static function checkAllRunningTask()
    {
        return (int)MainHandler::getInstance()->checkSelf(Config::$prifix) + (int)MainHandler::getInstance()->checkSelf(Config::$phpBinPath);
    }

    private static function checkTopicRunningTask($topic)
    {
        return (int)MainHandler::getInstance()->checkSelf($topic);
    }
}