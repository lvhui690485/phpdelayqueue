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
    public function getTopicInfo($topic)
    {
        return Config::$topic[$topic] ?? [];
    }
}