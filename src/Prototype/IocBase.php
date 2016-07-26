<?php
namespace Flash\Prototype;

use Interop\Container\ContainerInterface;

abstract class IocBase
{
    protected $ci;

    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
    }

    public function __get($name)
    {
        return $this->ci->get($name);
    }
}
