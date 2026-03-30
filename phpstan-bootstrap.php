<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

$path = __DIR__.'/';

for ($i=1; $i < 10; $i++) {
    if (file_exists($path.'/vendor/autoload.php')) {
        define('OPENDXP_PROJECT_ROOT', $path);

        break;
    } else {
        $path = $path.'../';
    }
}

include OPENDXP_PROJECT_ROOT . '/vendor/autoload.php';
\OpenDxp\Bootstrap::setProjectRoot();
\OpenDxp\Bootstrap::bootstrap();

if (!defined('OPENDXP_TEST')) {
    define('OPENDXP_TEST', true);
}
