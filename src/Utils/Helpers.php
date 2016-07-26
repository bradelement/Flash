<?php
namespace Flash\Utils;

class Helpers
{
    /*
     * type: 0为字符串， 1为数字
     */
    public static function getClientIp($type=0)
    {
        $type = $type ? 1 : 0;
        static $ip = null;
        if (!is_null($ip)) {
            return $ip[$type]; //cache
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($arr[0]);
        } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

    public static function uuid()
    {
        $chars = md5(uniqid(mt_rand(), true));
        $ret = array(
            substr($chars, 0, 8),
            substr($chars, 8, 4),
            substr($chars, 12, 4),
            substr($chars, 16, 4),
            substr($chars, 20, 12),
        );
        return implode('-', $ret);
    }
}
