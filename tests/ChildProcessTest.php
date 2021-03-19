<?php

declare(strict_types=1);

namespace WyriHaximus\React\Tests\ChildProcess\PSR3;

use Prophecy\Argument;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use RuntimeException;
use stdClass;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;
use WyriHaximus\React\ChildProcess\Messenger\MessengerInterface;
use WyriHaximus\React\ChildProcess\PSR3\ChildProcess;
use WyriHaximus\TestUtilities\TestCase;

use function Clue\React\Block\await;

/**
 * @internal
 */
final class ChildProcessTest extends TestCase
{
    public function testChildProcess(): void
    {
        $loop = Factory::create();

        $log = [
            'level'   => 250,
            'message' => 'Notice',
            'context' => [],
        ];

        $logger = new class () extends AbstractLogger {
            /** @var array<array<string, mixed>> */
            public array $logs = [];

            /**
             * @param mixed        $level
             * @param array<mixed> $context
             */
            public function log($level, $message, array $context = []): void // phpcs:disabled
            {
                $this->logs[] = [
                    'level'   => $level,
                    'message' => $message,
                    'context' => $context,
                ];
            }
        };

        $setupCallback = null;
        $logCallback   = null;

        $messenger = $this->prophesize(MessengerInterface::class);
        $messenger->registerRpc(
            Argument::exact('logger.setup'),
            Argument::that(static function ($callback) use (&$setupCallback): bool {
                $setupCallback = $callback;

                return true;
            })
        )->shouldBeCalled();
        $messenger->registerRpc(
            Argument::exact('logger.log'),
            Argument::that(static function ($callback) use (&$logCallback): bool {
                $logCallback = $callback;

                return true;
            })
        )->shouldBeCalled();

        ChildProcess::create($messenger->reveal(), $loop);

        $setupCallback(
            new Payload([
                'factory' => static function () use ($logger): LoggerInterface {
                    return $logger;
                },
            ])
        );
        $logCallback(
            new Payload($log)
        );

        self::assertSame([$log], $logger->logs);
    }

    /**
     * @return iterable<mixed>
     */
    public function provideInvalidLoggers(): iterable
    {
        yield [null];
        yield ['string'];
        yield [new stdClass()];
        yield [123];
        yield [
            static function (): void {
            },
        ];

        yield [0xfff];
        yield [0b0001001100110111];
    }

    /**
     * @param mixed $logger
     *
     * @dataProvider provideInvalidLoggers
     */
    public function testNotAlogger($logger): void
    {
        static::expectException(\Error::class);
        static::expectExceptionMessageMatches('/Argument 2 passed to WyriHaximus\\\React\\\ChildProcess\\\PSR3\\\ChildProcess::__construct/');
        static::expectExceptionMessageMatches('/must/');
        static::expectExceptionMessageMatches('/Psr\\\Log\\\LoggerInterface/');

        $loop = Factory::create();

        $setupCallback = null;

        $messenger = $this->prophesize(MessengerInterface::class);
        $messenger->registerRpc(
            Argument::exact('logger.setup'),
            Argument::that(static function ($callback) use (&$setupCallback): bool {
                $setupCallback = $callback;

                return true;
            })
        )->shouldBeCalled();

        ChildProcess::create($messenger->reveal(), $loop);

        await(
            $setupCallback(
                new Payload([
                    'factory' => static function () use ($logger) {
                        return $logger;
                    },
                ])
            ),
            $loop,
            3
        );
    }

    public function testErrorWhileLogging(): void
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Something went wrong');

        $loop = Factory::create();

        $log = [
            'level'   => 250,
            'message' => 'Notice',
            'context' => [],
        ];

        $logger = new class () extends AbstractLogger {
            /**
             * @param mixed        $level
             * @param array<mixed> $context
             */
            public function log($level, $message, array $context = []): void // phpcs:disabled
            {
                throw new RuntimeException('Something went wrong');
            }
        };

        $setupCallback = null;
        $logCallback   = null;

        $messenger = $this->prophesize(MessengerInterface::class);
        $messenger->registerRpc(
            Argument::exact('logger.setup'),
            Argument::that(static function ($callback) use (&$setupCallback): bool {
                $setupCallback = $callback;

                return true;
            })
        )->shouldBeCalled();
        $messenger->registerRpc(
            Argument::exact('logger.log'),
            Argument::that(static function ($callback) use (&$logCallback): bool {
                $logCallback = $callback;

                return true;
            })
        )->shouldBeCalled();

        ChildProcess::create($messenger->reveal(), $loop);

        $loop->futureTick(function () use ($loop): void {
            $loop->stop();
        });

        $setupCallback(
            new Payload([
                'factory' => static function () use ($logger): LoggerInterface {
                    return $logger;
                },
            ])
        );

        await(
            $logCallback(
                new Payload($log)
            ),
            $loop,
            3
        );

//        self::assertSame([$log], $logger->logs);
    }
}
