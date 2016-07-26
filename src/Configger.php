<?php
namespace Flash;

class Configger
{
    protected $directFile;
    protected $envFile;
    protected $env;

    public function __construct($env, $directFile, $envFile)
    {
        $this->env = $env;
        $this->directFile = $directFile;
        $this->envFile    = $envFile;
    }

    public function getConfig()
    {
        $config = array();
        foreach ($this->directFile as $name) {
            $config = array_merge($config, $this->getConfigFromFile($name));
        }
        foreach ($this->envFile as $name) {
            $config = array_merge($config, $this->getConfigFromFile($name, $env));
        }
        return $config;
    }

    protected function getConfigFromFile($name, $env=null)
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
