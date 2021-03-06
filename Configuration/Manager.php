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

namespace Bread\Configuration;

use Bread\Caching\Cache;
use Bread\Promise;
use Exception;

class Manager {
  private static $defaults = array();
  private static $configurations = array();

  public static function initialize($url) {
    $directory = parse_url($url, PHP_URL_PATH);
    return Cache::instance()->fetch(__METHOD__)->then(null, function ($key) use (
      $directory) {
      $configurations = array();
      if (!is_dir($directory)) {
        throw new Exception("Configuration directory $directory is not valid.");
      }
      foreach ((array) scandir($directory) as $path) {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $path = $directory . DIRECTORY_SEPARATOR . $path;
        if ($Parser = static::get(__CLASS__, "parsers.$extension")) {
          $configurations = array_replace_recursive($configurations, $Parser::parse($path));
        }
      }
      Cache::instance()->store($key, $configurations);
      return $configurations;
    })->then(function ($configurations) {
      static::$configurations = array_merge($configurations, static::$configurations);
    });
  }

  public static function defaults($class, $configuration = array()) {
    if ($parent = get_parent_class($class)) {
      $configuration = array_replace_recursive(static::get($parent), $configuration);
    }
    if (isset(static::$configurations[$class])) {
      $configuration = array_replace_recursive($configuration, static::$configurations[$class]);
    }
    static::$configurations[$class] = $configuration;
  }

  public static function configure($class, $configuration = array()) {
    if (!isset(static::$configurations[$class])) {
      static::$configurations[$class] = array();
    }
    static::$configurations[$class] = array_replace_recursive(static::$configurations[$class], $configuration);
  }

  public static function get($class, $key = null) {
    static::defaults($class);
    if (!isset(static::$configurations[$class])) {
      return null;
    }
    $configuration = static::$configurations[$class];
    if (null === $key) {
      return $configuration;
    }
    foreach (explode('.', $key) as $key) {
      if (!isset($configuration[$key])) {
        return null;
      }
      $configuration = $configuration[$key];
    }
    return $configuration;
  }
}

Manager::defaults('Bread\Configuration\Manager', array(
  'parsers' => array(
    'ini' => 'Bread\Configuration\Parsers\Initialization',
    'php' => 'Bread\Configuration\Parsers\PHP'
  )
));
