<?php
/**
 * Bread PHP Framework (http://github.com/saiv/Bread)
 * Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 *
 * Licensed under a Creative Commons Attribution 3.0 Unported License.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright  Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 * @link       http://github.com/saiv/Bread Bread PHP Framework
 * @package    Bread
 * @since      Bread PHP Framework
 * @license    http://creativecommons.org/licenses/by/3.0/
 */

namespace Bread;

use Bread\Core\ClassLoader;

require 'Core' . DIRECTORY_SEPARATOR . 'ClassLoader.php';

/**
 * Define a shortcut for DIRECTORY_SEPARATOR
 */
define('DS', DIRECTORY_SEPARATOR);

/**
 * Define a namespace separator
 */
define('NAMESPACE_SEPARATOR', "\\");

/**
 * Define a shortcut for NAMESPACE_SEPARATOR
 */
define('NS', NAMESPACE_SEPARATOR);

/**
 * Define the framework's base directory
 */
if (!defined('BREAD_BASE')) {
  define('BREAD_BASE', dirname(__DIR__));
}

/**
 * Define the current webroot directory
 */
if (!defined('BREAD_ROOT')) {
  define('BREAD_ROOT', getcwd());
}

/**
 * Define the default application's directory
 */
if (!defined('BREAD_APPLICATION')) {
  define('BREAD_APPLICATION', BREAD_ROOT . DS . "application");
}

/**
 * Define the default configuration's directory
 */
if (!defined('BREAD_CONFIGURATION')) {
  define('BREAD_CONFIGURATION', BREAD_ROOT . DS . "configuration");
}

/**
 * Define the default private directory
 */
if (!defined('BREAD_PRIVATE')) {
  define('BREAD_PRIVATE', BREAD_ROOT . DS . "private");
}

/**
 * Define the default public directory
 */
if (!defined('BREAD_PUBLIC')) {
  define('BREAD_PUBLIC', BREAD_ROOT . DS . "public");
}

if (!defined('BREAD_GETTEXT')) {
  define('BREAD_GETTEXT', "gettext://localhost" . BREAD_ROOT . DS . "locale");
}

foreach (array(
  new ClassLoader(__NAMESPACE__, BREAD_BASE),
  new ClassLoader(null, BREAD_APPLICATION),
  new ClassLoader()
) as $cl) {
  $cl->register();
}
