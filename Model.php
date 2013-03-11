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

use Bread\Promise;
use Bread\Caching\Cache;
use Bread\Configuration;
use Bread\Model\Database;
use Exception;
use IteratorAggregate, ArrayIterator;
use JsonSerializable;

abstract class Model implements IteratorAggregate, JsonSerializable {
  public function __construct($attributes = array()) {
    static::configure();
    foreach (array_intersect_key($attributes, get_object_vars($this)) as $attribute => $value) {
      $this->__set($attribute, $value);
    }
  }

  public function __set($attribute, $value) {
    $this->validate($attribute, $value);
    $this->$attribute = $value;
  }

  public function __get($attribute) {
    return $this->$attribute;
  }
  
  public function getIterator() {
    return new ArrayIterator(get_object_vars($this));
  }

  public function jsonSerialize() {
    return get_object_vars($this);
  }
  
  public function validate($attribute, $value) {
  }

  public function store() {
    $class = get_class($this);
    return Database::driver($class)->store($this);
  }

  public function delete() {
    $class = get_class($this);
    return Database::driver($class)->delete($this);
  }

  public static function count($search = array(), $options = array()) {
    return static::cached(__FUNCTION__, $search, $options);
  }

  public static function first($search = array(), $options = array()) {
    return static::cached(__FUNCTION__, $search, $options);
  }

  public static function fetch($search = array(), $options = array()) {
    return static::cached(__FUNCTION__, $search, $options);
  }

  public static function purge($search = array(), $options = array()) {
    return static::cached(__FUNCTION__, $search, $options);
  }

  public static function get($key = null) {
    $class = get_called_class();
    return Configuration\Manager::get($class, $key);
  }

  public static function configure() {
    $class = get_called_class();
    try {
      Database::driver($class);
    }
    catch (Database\Exceptions\DriverNotRegistered $dnr) {
      Database::register(static::get('database.url'), $class);
    }
  }
  
  protected static function cached($function, $search = array(),
    $options = array()) {
    static::configure();
    $class = get_called_class();
    $key = implode('::', array(
      $class,
      $function,
      json_encode($search + $options)
    ));
    return Cache::instance()->fetch($key)->then(null, function ($key) use (
      $class, $function, $search, $options) {
      return Database::driver($class)->$function($class, $search, $options)->then(function (
        $result) use ($key) {
        Cache::instance()->store($key, $result);
        return $result;
      });
    });
  }
}
