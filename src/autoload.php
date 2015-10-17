<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

require_once realpath(__DIR__) . '/../vendor/autoload.php';

$_DEEBETLE_STARTUP_STATE = array(
    'time'            => microtime(TRUE),
    'memoryUsage'     => memory_get_usage(),
    'peakMemoryUsage' =>
        function_exists('memory_get_peak_usage')
        ? memory_get_peak_usage()
        : NULL,
    'includedFiles'   => sizeof(get_included_files()),
    'entryPoint' => array(
        'file' => __FILE__,
        'line' => __LINE__
    )
);

Deepelopment\Debeetle\Loader::startup(
    $GLOBALS['_DEBEETLE_CONFIG_PATH'],
    $GLOBALS['_DEBEETLE_ENV'],
    $GLOBALS['_DEBEETLE_SCRIPT_STARTUP_STATE'],
    $_DEEBETLE_STARTUP_STATE,
    isset($GLOBALS['_DEBEETLE_CUSTOM_SETTINGS'])
        ? $GLOBALS['_DEBEETLE_CUSTOM_SETTINGS']
        : array()
);

unset(
    $GLOBALS['_DEBEETLE_CONFIG_PATH'],
    $GLOBALS['_DEBEETLE_ENV'],
    $GLOBALS['_DEBEETLE_SCRIPT_STARTUP_STATE'],
    $_DEEBETLE_STARTUP_STATE,
    $GLOBALS['_DEBEETLE_CUSTOM_SETTINGS']
);
