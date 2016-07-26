<?php
namespace Flash\Prototype;

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Client;
use Flash\Utils\SignatureV4;

abstract class V4Rpc extends BaseRpc
{
    protected function init()
    {
        $this->logger = $ci->get('logger');

        $stack = new HandlerStack();
        $stack->setHandler(\GuzzleHttp\choose_handler());
        $stack->push($this->replace_uri());
        $stack->push($this->v4_sign());
        $stack->push($this->log($this->logger));

        $this->client = new Client(array(
            'handler'  => $stack,
            'base_uri' => $this->base_uri[ENV],
            'timeout'  => $this->timeout,
        ));
    }

    protected function v4_sign()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                $v4 = new SignatureV4();
                $credentials = $options['v4_credentials'];
                $request = $v4->signRequest($request, $credentials);
                return $handler($request, $options);
            };
        };
    }
}
