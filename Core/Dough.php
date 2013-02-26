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

namespace Bread\Core;

/**
 * This abstract class provides a dough for any Bread receipt
 *
 * It includes a runtime cache and class configuration storage.
 *
 * @author Giovanni Lovato <heruan@aldu.net>
 */

abstract class Dough {
  /**
   * Return value for cache fetching failure
   */
  const CACHE_FAILURE = 'CACHE_FAILURE';

  /**
   * Runtime cache.
   *
   * @var array
   */
  protected static $_cache = array();

  /**
   * Class configuration storage
   *
   * @var array
   */
  protected static $_configurations = array();

  /**
   * Class static configuration
   *
   * @var array
   */
  private static $configuration = array();

  /**
   * Class constructor
   */
  public function __construct() {
    $self = get_class($this);
    $self::configure();
  }

  /**
   * Class destructor
   */
  public function __destruct() {
    ;
  }

  /**
   * Class configuration method
   *
   * Configures a class merging configurations from parent classes, static configuration and user-defined initialization files.
   *
   * @param array $configuration
   * @return array
   */
  public static function configure($configuration = array()) {
    $self = get_called_class();
    if (!isset(self::$_configurations[$self])) {
      $parentConfiguration = array();
      $staticConfiguration = array();
      $userConfiguration = array();
      if ($parent = get_parent_class($self)) {
        $parentConfiguration = $parent::configure();
      }
      if (isset($self::$configuration)) {
        $staticConfiguration = $self::$configuration;
      }
      if (is_dir(BREAD_CONFIGURATION)) {
        if (self::CACHE_FAILURE
          === ($cache = self::_cache(__CLASS__, __FUNCTION__))) {
          $ini = '';
          foreach (scandir(BREAD_CONFIGURATION) as $path) {
            if (preg_match('/\.ini$/', $path)) {
              $ini .= file_get_contents(BREAD_CONFIGURATION
                . DIRECTORY_SEPARATOR . $path) . "\n";
            }
          }
          $cache = Configuration::parse($ini, true);
          self::_cache(__CLASS__, __FUNCTION__, $cache);
        }
        if (isset($cache[$self])) {
          $userConfiguration = $cache[$self];
        }
      }
      $configuration = array_replace_recursive($parentConfiguration, $staticConfiguration, $userConfiguration, $configuration);
      self::$_configurations[$self] = $configuration;
    }
    return self::$_configurations[$self];
  }

  public static function configuration() {
    $self = get_called_Class();
    if (isset(self::$_configurations[$self])) {
      return self::$_configurations[$self];
    }
    return array();
  }
  
  public static function configured() {
    $self = get_called_class();
    return isset(self::$_configurations[$self]);
  }
  
  /**
   * Gets configuration values
   *
   * @param string $key
   * @return mixed
   */
  public static function get($key = null) {
    $configuration = null;
    $self = get_called_class();
    $self::configure();
    if (is_null($key)) {
      return isset(self::$_configurations[$self]) ? self::$_configurations[$self]
        : $configuration;
    }
    $keys = explode('.', $key);
    if (isset(self::$_configurations[$self])) {
      $configuration = self::$_configurations[$self];
      foreach ($keys as $key) {
        if (!is_array($configuration) || !isset($configuration[$key])) {
          return null;
        }
        $configuration = $configuration[$key];
      }
    }
    return $configuration;
  }

  /**
   * Sets configuration values
   *
   * @param string $key
   * @param mixed $value
   */
  public static function set($key = null, $value = null) {
    $self = get_called_class();
    $self::configure();
    if (is_array($key)) {
      return self::$_configurations[$self] = array_replace_recursive(self::$_configurations[$self], $key);
    }
    return self::$_configurations[$self] = array_replace_recursive(self::$_configurations[$self], Configuration::parse(array(
      $key => $value
    )));
  }

  /**
   * Caches a runtime value
   *
   * @param string $type
   * @param string $key
   * @param mixed $value
   * @return mixed
   */
  protected static function _cache($type, $key, $value = self::CACHE_FAILURE) {
    if ($value !== self::CACHE_FAILURE) {
      static::$_cache[$type][$key] = $value;
    }
    if (!isset(static::$_cache[$type][$key])) {
      return self::CACHE_FAILURE;
    }
    return static::$_cache[$type][$key];
  }
}
