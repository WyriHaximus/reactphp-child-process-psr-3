<?php

declare(strict_types=1);

namespace WyriHaximus\React\ChildProcess\PSR3;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Throwable;
use WyriHaximus\React\ChildProcess\Messenger\ChildInterface;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;
use WyriHaximus\React\ChildProcess\Messenger\MessengerInterface;

use function React\Promise\reject;
use function React\Promise\resolve;

final class ChildProcess implements ChildInterface
{
    private LoggerInterface $logger;

    private function __construct(MessengerInterface $messenger, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $messenger->registerRpc('logger.log', function (Payload $payload): PromiseInterface {
            return $this->log($payload);
        });
    }

    public static function create(MessengerInterface $messenger, LoopInterface $loop): void
    {
        $loop->futureTick(static function (): void {
        });
        $messenger->registerRpc('logger.setup', static function (Payload $payload) use ($messenger): PromiseInterface {
            /**
             * @psalm-suppress PossiblyNullFunctionCall
             */
            new static($messenger, $payload['factory']());

            return resolve(['success' => true]);
        });
    }

    private function log(Payload $payload): PromiseInterface
    {
        try {
            /**
             * @psalm-suppress PossiblyNullArgument
             */
            $this->logger->log(
                $payload['level'],
                $payload['message'],
                $payload['context']
            );

            return resolve(['success' => true]);
        } catch (Throwable $throwable) { /** @phpstan-ignore-line */
            return reject($throwable);
        }
    }
}
