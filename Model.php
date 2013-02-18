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

use Bread\Model;
use JsonSerializable;

abstract class Model implements JsonSerializable {
  protected static $configuration = array(
    'database' => 'mongodb://localhost/test'
  );

  protected static $database;

  public function __construct($attributes = array()) {
    foreach ($attributes as $attribute => $value) {
      $this->__set($attribute, $value);
    }
  }

  public function __get($attribute) {
    return $this->$attribute;
  }

  public function __set($attribute, $value) {
    $this->validate($attribute, $value);
    $this->$attribute = $value;
  }

  public function __isset($attribute) {
    return !is_null($this->__get($attribute));
  }

  public function __unset($attribute) {
    $this->__set($attribute, null);
  }

  public function __toString() {
    return $this->attributes();
  }

  public function jsonSerialize() {
    return $this->attributes();
  }

  public function attributes() {
    return get_object_vars($this);
  }

  public function store() {
    static::$database->store($this);
  }

  protected function validate($attribute, $value) {
  }

  public static function configure($configuration = array()) {
    static::$database = Model\Database\Factory::create(static::$configuration['database']);
  }

  public static function count($search = array(), $options = array()) {
    return static::$database->count(get_called_class(), $search, $options);
  }

  public static function first($search = array(), $options = array()) {
    array_multisort($search, $options);
    $key = get_called_class() . md5(json_encode($search + $options));
    return static::$cache->get($key)->then(null, function ($key) use ($search,
      $options) {
      return static::$database->first(get_called_class(), $search, $options)->then(function (
        $result) use ($key) {
        static::$cache->set($key, $result);
        return $result;
      });
    });
  }

  public static function fetch($search = array(), $options = array()) {
    return static::$database->fetch(get_called_class(), $search, $options);
  }
}
