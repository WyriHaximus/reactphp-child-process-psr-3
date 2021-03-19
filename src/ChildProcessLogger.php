<?php

declare(strict_types=1);

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

    private Messenger $messenger;

    private function __construct(Messenger $messenger, string $factory)
    {
        $this->messenger = $messenger;
        /**
         * @phpstan-ignore-next-line
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $this->messenger->rpc(MessageFactory::rpc(
            'logger.setup',
            ['factory' => $factory]
        ))->done();
    }

    public static function create(LoopInterface $loop, string $factory): PromiseInterface
    {
        /**
         * @psalm-suppress TooManyTemplateParams
         */
        return Factory::parentFromClass(
            ChildProcess::class,
            $loop
        )->then(static function (Messenger $messenger) use ($factory): ChildProcessLogger {
            return new self($messenger, $factory);
        });
    }

    /**
     * @param mixed        $level
     * @param array<mixed> $context
     *
     * @psalm-suppress MissingParamType
     */
    public function log($level, $message, array $context = []): void // phpcs:disabled
    {
        /**
         * @phpstan-ignore-next-line
         * @psalm-suppress UndefinedInterfaceMethod
         */
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
