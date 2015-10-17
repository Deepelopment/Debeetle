<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

use Deepelopment\Debeetle\ITool;
use Deepelopment\Debeetle\Loader;

/**
 * Short call support.
 *
 * @package Deepelopment\Debeetle
 * @static
 */
class d
{
    /**
     * @var ITool
     */
    protected static $instance;

    /**
     * @param  ITool $instance
     * @return void
     * @see    Loader::startup()
     */
    public static function setInstance(ITool $instance)
    {
        self::$instance = $instance;
        $instance->setInstance();
    }

    /**
     * Returns instance.
     *
     * @return ITool
     * @throws RuntimeException
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            $settings = Loader::getSettings();
            if (Loader::ENV_PROD == $settings['env']) {
                return NULL;
            }
            throw new RuntimeException('No instance');
        }

        return self::$instance;
    }

    /**
     * Calls plugins methods.
     *
     * @param  string $name  Method name
     * @param  array  $args  Method arguments
     * @return mixed
     */
    public static function __callStatic($name, array $args)
    {
        $instance = self::getInstance();
        $result = NULL;
        if ($instance) {
            $instance->setTrace(1);
            $result = call_user_func_array(array($instance, $name), $args);
        }

        return $result;
    }

    /**
     * Returns instance, alias of self::getInstance().
     *
     * @return ITool
     */
    public static function i()
    {
        return self::getInstance();
    }

    /**
     * @param  string $tab     Target tab
     * @param  string $before  Tab to specify tab order
     * @return void
     * @see    ITool::tab()
     */
    public static function t($tab = '', $before = '')
    {
        $instance = self::getInstance();
        if ($instance) {
            $instance->tab($tab, $before);
        }
    }

    /**
     * Adds checkpoint.
     *
     * @param  string $name    Checkpoint name
     * @param  string $tab     Target tab
     * @param  bool   $unwrap  Unwrap checkpoints having same names
     * @return void
     * @see    ITool::checkpoint()
     * @todo   Implement?
     */
/*
    public function cp($name, $unwrap = FALSE){
        self::getInstance()->checkpoint($name, $tab, $unwrap);
    }
*/

    /**
     * @param  string $string   String to write
     * @param  array  $options  Reserved array for functionality enhancement
     * @return void
     * @see    ITool::write()
     */
    public static function w($string, array $options = array())
    {
        $instance = self::getInstance();
        if ($instance) {
            $instance->write($string, $options);
        }
    }
}
