<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle;

use ErrorException;
use RuntimeException;

/**
 * PHP Debug Tool loader.
 *
 * @package Deepelopment/Debeetle
 * @static
 * @author  deepeloper ({@see https://github.com/deepeloper})
 * @todo    Logging
 */
class Loader
{
    /**
     * Production environment
     */
    const ENV_PROD = 1;

    /**
     * Development environment
     */
    const ENV_DEV  = 2;

    /**
     * Path to XML configuration file
     *
     * @var string
     */
    protected static $configPath;

    /**
     * Flag specifying that settings are parsed already
     *
     * @var bool
     */
    protected $settingsParsed;

    /**
     * Settings parsed from passed XML configuration file
     *
     * @var array
     */
    protected static $settings;

    /**
     * Tool instance
     *
     * @var ITool
     */
    protected static $instance;

    /**
     * Entry point
     *
     * @param  string $configPath          Path to XML configuration file
     * @param  array  $scriptStartupState  Script entry point struct
     * @param  array  $startupState        Debeetle entry point struct
     * @param  string $env                 'production' (by default) or 'development'
     * @param  array  $settings            Settings to override read from XML config file
     * @return void
     */
    public static function startup(
        $configPath,
        array $scriptStartupState,
        array $startupState,
        $env                      = 'production',
        array $settings           = array()
    )
    {
        if (
            (is_null($configPath) ? NULL : realpath($configPath)) ===
            self::$configPath
        ) {
            // Instance already initialized.

            return;
        }
        self::$configPath = is_null($configPath) ? NULL : realpath($configPath);
        switch ($env) {
            case 'development':
                $env = self::ENV_DEV;
                break;
            default:
                $env = self::ENV_PROD;
        }
        self::$settings = $settings + array(
            'env'                => $env,
            'scriptStartupState' => $scriptStartupState,
            'startupState'       => $startupState,
        );

        if (!self::checkRequirements()) {
            self::$settingsParsed = TRUE;
            self::$instance = new Tool_Stub;
            return;
        }
        self::$settingsParsed = false;
        $settings = self::getSettings();

        $shortAlias = $settings['shortAlias'];
        class_exists($shortAlias);
        if (empty($settings['launch'])) {
            self::$instance = new Debeetle_Stub;
        } else {
            self::$instance = new Debeetle($settings);
            // Load plugins
            foreach ($settings['plugins'] as $plugin) {
                if (class_exists($plugin)) {
                    /**
                     * @var Debeetle_Plugin_Interface
                     */
                    $plugin = new $plugin;
                    $plugin->setInstance($instance);
                    $plugin->init();
                }
            }
            call_user_func(array($shortAlias, 'setInstance'), $instance);
        }
    }

    /**
     * Parses and returns settings from passed configuration XML file.
     *
     * @return array
     */
    public static function getSettings()
    {
        if (!self::$settingsParsed) {
            if (!is_null(self::$configPath)) {
                try {
                    $parser = new ConfigParser(self::$configPath);
                    $settings = $parser->parse();
                } catch (RuntimeException $exception) {
                    if (self::ENV_PROD != self::$settings['env']) {
                        throw $exception;
                    }
                }
                self::$settings += $settings;
            }
            self::$settingsParsed = TRUE;
        }

        return self::$settings;
    }

    /**
     * Checks PHP requirements.
     *
     * @return bool
     * @throws ErrorException
     */
    protected static function checkRequirements()
    {
        $required = array(
            'spl_autoload_register',
            'memory_get_usage',
            'mb_convert_encoding',
            'json_decode',

            /** @see Deepelopment\XML */
            'libxml_use_internal_errors'
        );
        $missing = array();
        foreach ($required as $function) {
            if (!function_exists($function)) {
                $missing[] = $function . '()';
            }
        }
        if (sizeof($missing)) {
            if (self::ENV_PROD == self::$settings['env']) {
                // Silent in production environment
                return FALSE;
            }
            throw new ErrorException(
                sprintf(
                    'Missing required functions: %s',
                    implode(', ', $missing)
                )
        }

        return TRUE;
    }
}
