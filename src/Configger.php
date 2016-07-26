<?php
namespace Flash;

class Configger
{
    private static $directFile = array('slim');
    private static $envFile = array('logger', 'mysql');

    public static function getConfig($env)
    {
        $config = array();
        foreach (self::$directFile as $name) {
            $config = array_merge($config, self::getConfigFromFile($name));
        }
        foreach (self::$envFile as $name) {
            $config = array_merge($config, self::getConfigFromFile($name, $env));
        }
        return $config;
    }

    private static function getConfigFromFile($name, $env=null)
    {
        if (is_null($env)) {
            $path = '/src/Config/%s.php';
            $filename = WEB_ROOT . sprintf($path, $name);
        } else {
            $path = '/src/Config/%s.%s.php';
            $filename = WEB_ROOT . sprintf($path, $name, $env);
        }
        $config = require_once $filename;
        return $config;
    }
}
