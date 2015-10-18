<?php
/**
 * Debeetle PHP Debug
 *
 * Script including from debeetle.php and returning resource files content as
 * CSS/JavaScript/HTML.<br />
 * This script expects 'type', 'skin', 'theme' and 'v' GET-parameters.
 *
 * @category  PHP_Debug
 * @package   Debeetle_View_HTML
 * @author    Anton Leontiev (http://deepelopment.com/weregod)
 * @copyright Copyright (c) XXI deepelopment.com (http://deepelopment.com/)
 * @see       debeetle.php
 */

new Debeetle_Resource(dirname(__FILE__) . '/.', $_GET, $settings);

/**
 * @package Debeetle_View_HTML
 * @todo    Describe
 */
class Debeetle_Resource
{
    /**
     * Settings
     *
     * @var type
     */
    protected $settings;
    /**
     * @param type  $path     Resources path
     * @param array $request  Request
     */
    public function __construct($path, array $request, array $settings)
    {
        $this->validateRequest($request);
        $this->sendResponse($path, $request, $settings);
    }

    /**
     * Validates request
     *
     * @param array $request  Request
     * @return void
     * @exitpoint             In case of invalid request
     * @todo   Use mb_*?
     */
    private function validateRequest(array $request)
    {
        if (
            empty($request['type']) ||
            empty($request['v']) ||
            empty($request['skin']) ||
            empty($request['theme']) ||
            strpos($request['skin'], '..') !== FALSE ||
            strpos($request['skin'], DIRECTORY_SEPARATOR) !== FALSE ||
            strpos($request['theme'], '..') !== FALSE ||
            strpos($request['theme'], DIRECTORY_SEPARATOR) !== FALSE
        ) {
            $this->send404Header('Invalid request');
        }
    }

    /**
     * Send appropriate response
     *
     * @param type  $path      Resources path
     * @param array $request   Request
     * @param array $settings  Settings
     * @exitpoint  In case of troubles with resources
     */
    private function sendResponse($path, array $request, array $settings)
    {
        $files = $this->getFiles($path, $request, $settings['plugins']);
        $this->validateFiles($files);
        if ($request['type'] === 'css' && !empty($settings['lessCSS'])){
            $less = new lessc;
            if (empty($request['dev'])) {
                $less->setFormatter('compressed');
            }else{
                $less->setPreserveComments(TRUE);
            }
            ob_start();
        }
        foreach ($files as $struct) {
            if (is_file($struct['path']) && is_readable($struct['path'])) {
                readfile($struct['path']);
            }
        }
        if (isset($less)) {
            $content = ob_get_clean();
            try{
                $content = $less->compile($content);
            }catch(Exception $oException){
                $message = str_replace(array("\r", "\n", '  '), array(' ', ' ', ' '), $oException->getMessage());
                echo
                    "/*\n" .
                    " * LESS ERROR: " . $message . "\n" .
                    " */\n\n";
            }
            echo $content;
        }
        if ($request['type'] === 'js') {
            $locales = require $path . '/locale/en.php';###
            array_walk($locales, array($this, 'convertStringCallback'));
            echo '$d.setDictionary({';
            $lastIndex = sizeof($locales);
            $index = 0;
            foreach ($locales as $key => $caption) {
                echo "'{$key}': {$caption}", ++$index < $lastIndex ? ', ' : '';
            }
            echo "});\n";
        }
    }

    /**
     * Returns resources depending on type
     *
     * @param  type  $path     Resources path
     * @param  array $request  Request
     * @param  array $plugins  Plugins
     * @return array
     * @todo   Wipe comments
     */
    private function getFiles($path, array $request, array $plugins)
    {
        $files = array();
        switch ($request['type']) {
            case 'js':
                $file =
                    empty($request['dev'])
                        ? 'jquery-1.8.3.min.js' // 'jquery-1.7.2.min.js'
                        : 'jquery-1.8.3.js'; // 'jquery-1.7.2.js';
                $files[] =
                    array(
                        'path'     => sprintf("%s/{$file}", $path),
                        'required' => TRUE
                    );
                $files[] =
                    array(
                        'path'     => sprintf('%s/jquery.cookie.js', $path),
                        'required' => TRUE
                    );
                $files[] =
                    array(
                        'path'     => sprintf('%s/jquery-ui.js', $path),
                        'required' => TRUE
                    );
                $files[] =
                    array(
                        'path'     => sprintf('%s/common.js', $path),
                        'required' => TRUE
                    );
                foreach ($plugins as $plugin) {
                    if ($plugin::ID) {
                        $files[] =
                            array(
                                'path'     =>
                                    sprintf(
                                        '%s/%s.js',
                                        $path,
                                    $plugin::ID
                                    ),
                                'required' => FALSE
                            );
                    }
                }
                $files[] =
                    array(
                        'path' =>
                            sprintf(
                                '%s/skin/%s/skin.js',
                                $path,
                                $request['skin']
                            ),
                        'required' => FALSE
                    );
                $files[] =
                    array(
                        'path' =>
                            sprintf(
                                '%s/skin/%s/theme/%s.js',
                                $path,
                                $request['skin'],
                                $request['theme']
                            ),
                        'required' => FALSE
                    );
                break;
            case 'css':
                if (empty($request['target'])) {
                    $files[] =
                        array(
                            'path' =>
                                sprintf(
                                    '%s/skin/%s/skin.css',
                                    $path,
                                    $request['skin']
                                ),
                            'required' => TRUE
                        );
                    $files[] =
                        array(
                            'path' =>
                                sprintf(
                                    '%s/jquery-ui.css',
                                    $path
                                ),
                            'required' => TRUE
                        );
                    foreach ($plugins as $plugin) {
                        if ($plugin::ID) {
                            $files[] =
                                array(
                                    'path'     =>
                                        sprintf(
                                            '%s/skin/%s/%s.css',
                                            $path,
                                            $request['skin'],
                                            $plugin::ID
                                        ),
                                    'required' => FALSE
                                );
                        }
                    }
                    $files[] =
                        array(
                            'path' =>
                                sprintf(
                                    '%s/skin/%s/theme/%s.css',
                                    $path,
                                    $request['skin'],
                                    $request['theme']
                                ),
                            'required' => FALSE
                        );
                } else {
                    $files[] =
                        array(
                            'path'     => sprintf('%s/frame.css', $path),
                            'required' => TRUE
                        );
                }
                break;
            case 'html':
                $files[] =
                    array(
                        'path' =>
                            sprintf(
                                '%s/skin/%s/skin.html',
                                $path,
                                $request['skin']
                            ),
                        'required' => FALSE
                    );
                break;
        }
        return $files;
    }
    /**
     * Validate resource files
     *
     * @param  array $files
     * @return void
     * @exitpoint            In case of troubles with path
     */
    private function validateFiles($files)
    {
        foreach ($files as $struct) {
            if (
                $struct['required'] && (
                    !is_file($struct['path']) ||
                    !is_readable($struct['path'])
                )
            ) {
                $this->send404Header(
                    sprintf(
                        "Required file '%s' not found or cannot be read",
                        $struct['path']
                    )
                );
            }
        }
    }

    /**
     * @param  string $reason  Reason (for debug purpose only)
     * @return void
     * @exitpoint
     */
    private function send404Header($reason = '')
    {
        $protocol = @getenv('SERVER_PROTOCOL');
        if (!$protocol) {
            $protocol = 'HTTP/1.1';
        }
        header(
            sprintf(
                '%s 404 Not Found',
                $protocol
            )
        );
        die; // die($reason);
    }

    private function convertStringCallback(&$string)
    {
        $string = $this->convertStringToJS($string);
    }

    /**
     * @param  type $string
     * @return type
     * @todo   Try ro extract this method to the common library?
     */
    private function convertStringToJS($string)###
    {
        return
            "'" .
            str_replace(
                array(
                   "'",
                   "\r\n",
                   "\n\r",
                   "\r",
                   "\n",
                   '\\\\',
                   '</',
                   '/>'
                ),
                array(
                   "''",
                   '\\n',
                   '\\n',
                   '\\n',
                   '\\n',
                   '\\\\\\\\',
                   "<' + '/",
                   "/' + '>"
                ),
                $string
            ) .
            "'";
    }
}
