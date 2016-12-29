<?php
namespace Flash\Prototype;

use Flash\Utils\Clock;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RetryMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Exception\TransferException;

abstract class BaseRpc extends IocBase
{
    protected $client; //guzzle client
    protected $logger;

    protected $base_uri = array();
    protected $timeout = 10;
    protected $api_list = array();

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->init();
    }

    protected function init()
    {
        $this->logger = $this->ci->get('logger');

        $stack = HandlerStack::create();
        $stack->push($this->replace_uri());
        $stack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));
        $stack->push($this->log($this->logger));
        
        $this->client = new Client(array(
            'handler'  => $stack,
            'base_uri' => $this->base_uri[ENV],
            'timeout'  => $this->timeout,
        ));
    }


    public function request($api, $options=array())
    {
        $response = null;
        if (!isset($this->api_list[$api])) {
            return $response;
        }
        list($method, $uri, $default_options) = $this->api_list[$api];
        if (is_null($default_options)) {
            $default_options = array();
        }

        $request_option = $this->get_common_config();
        $request_option = $this->merge_option($request_option, $default_options);
        $request_option = $this->merge_option($request_option, $options);

        $clock = new Clock();
        try {
            $response = $this->client->request($method, $uri, $request_option);
        } catch (TransferException $e) {
            $response = $e->getResponse();
        }
        $time = $clock->spent();

        $log_id = LOG_ID;
        $res = $this->log_response($response);
        $this->logger->info("get response: LOG_ID($log_id) time($time) res($res)");

        return $response;
    }

    public function parse($response)
    {
        if (empty($response)) {
            return null;
        }
        return (string)$response->getBody();
    }

    public function get_common_config()
    {
        return array();
    }

    //---protected funciton begins---
    protected function merge_option($default, $option)
    {
        if (!is_array($option)) {
            return $option;
        }
        foreach ($option as $k=>$v) {
            if (!isset($default[$k])) {
                $default[$k] = $v;
            } else {
                $default[$k] = $this->merge_option($default[$k], $v);
            }
        }
        return $default;
    }

    protected function replace_uri()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (isset($options['replace'])) {
                    $replace = $options['replace'];
                    $uri = (string)$request->getUri();

                    $func = function($matches) use($replace) {
                        $key = substr($matches[0], 1, -1);
                        return $replace[$key];
                    };
                    $uri = preg_replace_callback('/\{.*?\}/', $func, $uri);

                    $func2 = function($matches) use($replace) {
                        $key = substr($matches[0], 3, -3);
                        return $replace[$key];
                    };
                    $uri = preg_replace_callback('/%7B.*?%7D/', $func2, $uri);
                    $request = $request->withUri(new Uri($uri));
                }
                return $handler($request, $options);
            };
        };
    }

    protected function log($logger)
    {
        return function(callable $handler) use($logger) {
            return function(RequestInterface $request, array $options)
            use ($handler, $logger) {
                $log_id = LOG_ID;
                $req = $this->log_request($request);
                $logger->info("send request: LOG_ID($log_id) req($req)");
                return $handler($request, $options);
            };
        };
    }

    protected function log_request($request)
    {
        $arr = array('curl', '-X');
        $arr[] = $request->getMethod();
        foreach ($request->getHeaders() as $name=>$values) {
            foreach ($values as $value) {
                $arr[] = '-H';
                $arr[] = "'$name: $value'";
            }
        }
        $body = (string)$request->getBody();
        if ($body) {
            $arr[] = '-d';
            $arr[] = "'$body'";
        }
        $uri = (string)$request->getUri();
        $arr[] = "'$uri'";
        return implode(' ', $arr);
    }

    protected function log_response($response)
    {
        if (method_exists($response, 'getBody')) {
            return (string)$response->getBody();
        }
        return '';
    }

    protected function retryDecider()
    {
        return function($retry, $request, $response, $exception){
            if ($retry >= 2) {//最多重试两次
                return false;
            }
            if ($exception instanceof ConnectException) {
                return true;
            }
            if ($response) {
                if ($response->getStatusCode() == 503) {
                    return true;
                }
            }
            return false;
        };
    }

    protected function retryDelay()
    {
        return function($num){
            return RetryMiddleware::exponentialDelay($num) * 500;
        };
    }
}
