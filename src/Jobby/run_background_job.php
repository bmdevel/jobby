<?php

namespace Jobby;

// run this file, if executed directly
// @see: http://stackoverflow.com/questions/2413991/php-equivalent-of-pythons-name-main
// @codeCoverageIgnoreStart
if (!debug_backtrace()) {
    if (file_exists('vendor/autoload.php')) {
        require('vendor/autoload.php');
    } else {
        require(dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/vendor/autoload.php');
    }

    spl_autoload_register(function ($class) {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        require(dirname(__DIR__) . "/{$class}.php");
    });

    parse_str($argv[2], $config);

    $restoreNullValues = function ($config) {
        return array_merge(
            array(
                'recipients' => null,
                'mailer' => null,
                'maxRuntime' => null,
                'smtpHost' => null,
                'smtpPort' => null,
                'smtpUsername' => null,
                'smtpPassword' => null,
                'smtpSecurity' => null,
                'runAs' => null,
                'environment' => null,
                'runOnHost' => null,
                'output' => null,
                'dateFormat' => null,
                'enabled' => null,
                'haltDir' => null,
                'debug' => null,
            ),
            $config
        );
    };
    $config = $restoreNullValues($config);

    $job = new BackgroundJob($argv[1], $config);
    $job->run();
}
// @codeCoverageIgnoreEnd
