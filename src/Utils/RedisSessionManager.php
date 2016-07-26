<?php
namespace Flash\Utils;

class RedisSessionManager implements \SessionHandlerInterface
{
    const SESSION_PREFIX = 'ele:';

    private $client;
    private $ttl;

    public function __construct($client, $ttl=1440)
    {
        $this->client = $client;
        $this->ttl    = $ttl;
    }

    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        $key = $this->key($id);
        $data = $this->client->get($key);
        return empty($data) ? '' : $data;
    }

    public function write($id, $data)
    {
        $key = $this->key($id);
        $this->client->setex($key, $this->ttl, $data);
        return true;
    }

    public function destroy($id)
    {
        $key = $this->key($id);
        $this->client->del($key);
        return true;
    }

    public function gc($maxlifetime)
    {
        return true;
    }

    private function key($id)
    {
        return self::SESSION_PREFIX . $id;
    }
}
