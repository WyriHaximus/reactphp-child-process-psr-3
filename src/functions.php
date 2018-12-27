<?php declare(strict_types=1);

namespace WyriHaximus\React\ChildProcess\PSR3;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

function example_stream_factory(): LoggerInterface
{
    return new Logger(
        'example',
        [
            new StreamHandler(\dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'example.log'),
        ]
    );
}
