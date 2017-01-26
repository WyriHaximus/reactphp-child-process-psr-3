<?php

namespace WyriHaximus\React\ChildProcess\PSR3;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use WyriHaximus\React\ChildProcess\Messenger\Factory;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Factory as MessageFactory;
use WyriHaximus\React\ChildProcess\Messenger\Messenger;

final class ChildProcessLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @var Messenger
     */
    private $messenger;

    public static function create(LoopInterface $loop, string $factory): PromiseInterface
    {
        return Factory::parentFromClass(
            ChildProcess::class,
            $loop
        )->then(function (Messenger $messenger) use ($factory) {
            return new self($messenger, $factory);
        });
    }

    /**
     * @param Messenger $messenger
     * @param string $factory
     */
    private function __construct(Messenger $messenger, string $factory)
    {
        $this->messenger = $messenger;
        $this->messenger->rpc(MessageFactory::rpc(
            'logger.setup',
            [
                'factory' => $factory,
            ]
        ))->done();
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = [])
    {
        $this->messenger->rpc(MessageFactory::rpc(
            'logger.log',
            [
                'level'   => $level,
                'message' => $message,
                'context' => $context,
            ]
        ))->done();
    }
}
