<?php

namespace App\Application\Middleware;

use Predis\Client as Redis;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class RateLimiterMiddleware implements Middleware
{
    private Redis $redis;
    private int $maxRequests;
    private int $decaySeconds;

    public function __construct(Redis $redis, int $maxRequests = 60, int $decaySeconds = 60)
    {
        $this->redis = $redis;
        $this->maxRequests = $maxRequests;
        $this->decaySeconds = $decaySeconds;
    }

    public function process(Request $request, Handler $handler): Response
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $path = $request->getUri()->getPath();

        $key = "rate:" . $ip . ":" . $path;

        $current = $this->redis->incr($key);

        if ($current === 1) {
            $this->redis->expire($key, $this->decaySeconds);
        }

        if ($current > $this->maxRequests) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                "message" => "Too Many Requests",
                "retry_after" => $this->redis->ttl($key)
            ]));

            return $response->withStatus(429)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
