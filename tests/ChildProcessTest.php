<?php

namespace WyriHaximus\React\Tests\ChildProcess\PSR3;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\AbstractLogger;
use stdClass;
use React\EventLoop\Factory;
use RuntimeException;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;
use WyriHaximus\React\ChildProcess\Messenger\Messenger;
use WyriHaximus\React\ChildProcess\PSR3\ChildProcess;
use function Clue\React\Block\await;

final class ChildProcessTest extends TestCase
{
    public function testChildProcess()
    {
        $loop = Factory::create();

        $log = [
            'level'   => 250,
            'message' => 'Notice',
            'context' => [],
        ];

        $logger = new class extends AbstractLogger
        {
            public $logs = [];

            public function log($level, $message, array $context = [])
            {
                $this->logs[] = [
                    'level'   => $level,
                    'message' => $message,
                    'context' => $context,
                ];
            }
        };

        $setupCallback = null;
        $logCallback = null;

        $messenger = $this->prophesize(Messenger::class);
        $messenger->registerRpc(
            Argument::exact('logger.setup'),
            Argument::that(function ($callback) use (&$setupCallback) {
                $setupCallback = $callback;
                return true;
            })
        )->shouldBeCalled();
        $messenger->registerRpc(
            Argument::exact('logger.log'),
            Argument::that(function ($callback) use (&$logCallback) {
                $logCallback = $callback;
                return true;
            })
        )->shouldBeCalled();

        ChildProcess::create($messenger->reveal(), $loop);

        $setupCallback(
            new Payload([
                'factory' => function () use ($logger) {
                    return $logger;
                }
            ])
        );
        $logCallback(
            new Payload($log)
        );

        self::assertSame([$log], $logger->logs);
    }

    public function provideInvalidLoggers()
    {
        yield [null];
        yield ['string'];
        yield [new stdClass()];
        yield [123];
        yield [function () {}];
        yield [0xfff];
        yield [0b0001001100110111];
    }

    /**
     * @dataProvider provideInvalidLoggers
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Passed logger isn't a PSR-3 logger
     */
    public function testNotAlogger($logger)
    {
        $loop = Factory::create();

        $setupCallback = null;

        $messenger = $this->prophesize(Messenger::class);
        $messenger->registerRpc(
            Argument::exact('logger.setup'),
            Argument::that(function ($callback) use (&$setupCallback) {
                $setupCallback = $callback;
                return true;
            })
        )->shouldBeCalled();

        $messenger->registerRpc(
            Argument::exact('logger.log'),
            Argument::type('callable')
        )->shouldBeCalled();

        ChildProcess::create($messenger->reveal(), $loop);

        await(
            $setupCallback(
                new Payload([
                    'factory' => function () use ($logger) {
                        return $logger;
                    }
                ])
            ),
            $loop,
            3
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Something went wrong
     */
    public function testErrorWhileLogging()
    {
        $loop = Factory::create();

        $log = [
            'level'   => 250,
            'message' => 'Notice',
            'context' => [],
        ];

        $logger = new class extends AbstractLogger
        {
            public function log($level, $message, array $context = [])
            {
                throw new RuntimeException('Something went wrong');
            }
        };

        $setupCallback = null;
        $logCallback = null;

        $messenger = $this->prophesize(Messenger::class);
        $messenger->registerRpc(
            Argument::exact('logger.setup'),
            Argument::that(function ($callback) use (&$setupCallback) {
                $setupCallback = $callback;
                return true;
            })
        )->shouldBeCalled();
        $messenger->registerRpc(
            Argument::exact('logger.log'),
            Argument::that(function ($callback) use (&$logCallback) {
                $logCallback = $callback;
                return true;
            })
        )->shouldBeCalled();

        ChildProcess::create($messenger->reveal(), $loop);

        $setupCallback(
            new Payload([
                'factory' => function () use ($logger) {
                    return $logger;
                }
            ])
        );


        await(
            $logCallback(
                new Payload($log)
            ),
            $loop,
            3
        );

        self::assertSame([$log], $logger->logs);
    }

}
