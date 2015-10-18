<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle\Plugin;

use Deepelopment\Debeetle\Plugin;

/**
 * Debeetle phpinfo() plugin.
 *
 * @package Deepelopment/Debeetle
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
class PHPInfo extends Plugin
{
    /**
     * Plugin resource id
     *
     * @see Debeetle_Resource::getFiles()
     * @see Debeetle_Resource_Public::processRequest()
     */
    const ID = 'debeetle.plugin.phpinfo';

    /**
     * Plugin version
     *
     * Used for building url hash.
     *
     * @see Debeetle_Resource_Public::processRequest()
     */
    const VERSION = '1';

    /**
     * Initialize plugin
     *
     * @return void
     */
    public function init()
    {
        $options = $this->settings['defaults']['options'];
        if (
            isset($options['_phpinfo']) &&
            isset($options['_phpinfo']['tab'])
        ) {
            $tab = $options['_phpinfo']['tab'];
        } else {
            $tab = 'Server|PHP Info';
        }
        $this->tool->tab($tab);
        ob_start();
        require
            $this->settings['path']['resources'] . '/' . self::ID . '.phtml';
        $this->tool->write(
            ob_get_clean(),
            array(
                'htmlEntities' => FALSE,
                'nl2br'        => FALSE,
                'skipEncoding' => TRUE
            )
        );
    }

    /**
     * Process separate request and return data.
     *
     * @retun string
     */
    public function processRequest()
    {
        ob_start();
        phpinfo();
        die(ob_get_clean());
    }
}
