<?php

namespace WyriHaximus\React\ChildProcess\PSR3;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Throwable;
use WyriHaximus\React\ChildProcess\Messenger\ChildInterface;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;
use WyriHaximus\React\ChildProcess\Messenger\Messenger;
use function React\Promise\reject;
use function React\Promise\resolve;

final class ChildProcess implements ChildInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public static function create(Messenger $messenger, LoopInterface $loop)
    {
        new static($messenger, $loop);
    }

    protected function __construct(Messenger $messenger, LoopInterface $loop)
    {
        $messenger->registerRpc('logger.setup', function (Payload $payload) {
            return $this->setup($payload);
        });
        $messenger->registerRpc('logger.log', function (Payload $payload) {
            return $this->log($payload);
        });
    }

    private function setup(Payload $payload): PromiseInterface
    {
        try {
            $this->logger = $payload['factory']();
            if (!($this->logger instanceof LoggerInterface)) {
                throw new InvalidArgumentException('Passed logger isn\'t a PSR-3 logger');
            }
            return resolve([
                'success' => true,
            ]);
        } catch (Throwable $throwable) {
            return reject($throwable);
        }
    }

    private function log(Payload $payload): PromiseInterface
    {
        try {
            $this->logger->log(
                $payload['level'],
                $payload['message'],
                $payload['context']
            );
            return resolve([
                'success' => true,
            ]);
        } catch (Throwable $throwable) {
            return reject($throwable);
        }
    }
}
