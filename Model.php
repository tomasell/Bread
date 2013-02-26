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

use Bread\Core;
use Bread\Model;
use JsonSerializable;

abstract class Model extends Core\Dough implements JsonSerializable {
  protected static $cache;
  protected static $database;
  protected static $attributes = array();
  protected static $configuration = array(
    'database' => array(
      'url' => 'mongodb://localhost/test'
    )
  );

  public function __construct($attributes = array()) {
    parent::__construct();
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
    static::database()->store($this);
  }

  protected function validate($attribute, $value) {
  }

  public static function configure($configuration = array()) {
    $self = get_called_class();
    if ($self::configured()) {
      return $self::configuration();
    }
    $configuration['attributes'] = $self::$attributes;
    $configuration = parent::configure($configuration);
    $self::$cache = Cache\Factory::create();
    if (!isset(self::$database[$self])) {
      self::$database[$self] = Model\Database\Factory::create($self::get('database.url'));
    }
    return $configuration;
  }

  public static function count($search = array(), $options = array()) {
    return static::database()->count(get_called_class(), $search, $options);
  }

  public static function first($search = array(), $options = array()) {
    $self = get_called_class();
    $key = $self . md5(json_encode($search + $options));
    //return static::$cache->get($key)->then(null, function ($key) use ($search,
    //  $options) {
    return static::database()->first(get_called_class(), $search, $options)->then(function (
      $result) use ($key) {
      static::$cache->set($key, $result);
      return $result;
    });
    //});
  }

  public static function fetch($search = array(), $options = array()) {
    return static::database()->fetch(get_called_class(), $search, $options);
  }
  
  public static function id() {
    $attributes = array_keys(get_class_vars(get_called_class()));
    return array_shift($attributes);
  }
  
  protected static function database() {
    $self = get_called_class();
    $self::configure();
    if (isset(self::$database[$self])) {
      return self::$database[$self];
    }
  }
}
