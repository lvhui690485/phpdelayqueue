<?php
/**
 * Created by PhpStorm.
 * User: lvhui
 * Date: 2020/7/29
 * Time: 下午7:34
 */

namespace PhpDelayQueue\Handler;

use App\Utils\Singleton;
use PhpDelayQueue\Config\Config;
use PhpDelayQueue\Tools\DqLog;
use PhpDelayQueue\Tools\Tools;

Class MainHandler
{
    use Singleton;

    //检测自身
    public function checkSelf($processName)
    {
        return Tools::getInstance()->getChildNum($processName);
    }

    //获取服务id
    public function getAllServerId($processName)
    {
        return Tools::getInstance()->getServerId($processName);
    }

    //获取服务名称
    public function getAllServerName($processName, $cmdName)
    {
        return Tools::getInstance()->getServerProcessName($processName, $cmdName);
    }

    /**
     * kill服务
     * @param $ids
     */
    public function killServerId($ids)
    {
        return Tools::getInstance()->killServerId($ids);
    }

    /**
     * 获取注册信息
     * @param $topic
     * @return array
     */
    public function getTopicInfo($topic, $key = '*')
    {
        $topicInfo = (!empty(Config::$topicType) && Config::$topicType == 1) ? (Config::$topic[$topic] ?? []) : RedisHandler::getInstance()->getTopicInfo($topic);
        return $key == '*' ? $topicInfo : ($topicInfo[$key] ?? '');
    }

    //USR2 信号回调
    public function masterRegisterUsr2Sin()
    {
        static $regUsr2 = 0;
        if (!$regUsr2) {
            if (pcntl_signal(SIGUSR2, [new self(), "masterUsr2Handler"], false)) {
                DqLog::info('master', 'master install usr2 succ', date('Y-m-d H:i:s'), 'master');
                $regUsr2 = 1;
            } else {
                DqLog::error('master', 'master install usr2 fail', date('Y-m-d H:i:s'), 'master');
            }
        }
    }

    //USR2信号回调函数
    public function masterUsr2Handler($sigNo)
    {
        switch ($sigNo) {
            case SIGUSR2:
                DqLog::info('master', 'master process accept quiet_exit sig，pid=' . posix_getpid() . ',name=' . cli_get_process_title(), date('Y-m-d H:i:s'), 'master');
                RedisHandler::getInstance()->setServerStatus(1);
        }
    }

    //服务
    public function actionServer($action = 'reload')
    {
        try {
            $out = [];
            $command = Config::$phpBinPath . ' ' . Config::$projectPath . '/init.php ' . $action . ' > /dev/null 2>&1 &';
            exec($command, $out);
        } catch (Exception $e) {
            DqLog::error('master', $action . '异常', $e->getMessage(), 'master');
        }
    }

    //检测config
    public function checkConfig()
    {
        static $md5 = '';
        $func = new \ReflectionClass(Config::class);
        $config = $func->getFileName();
        if (empty($md5)) {
            $md5 = md5_file($config);
        } else {
            $tmp = md5_file($config);
            if ($md5 != $tmp) {
                $md5 = $tmp;
                return true;
            }
        }
        return false;
    }
}


