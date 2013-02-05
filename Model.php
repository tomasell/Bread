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

use JsonSerializable;
use SplObjectStorage, SplObserver, SplSubject;

abstract class Model implements JsonSerializable {
  protected static $observers;

  public function __construct($attributes = array()) {
    //parent::__construct();
    //static::$observers->attach($this, new SplObjectStorage());
    foreach ($attributes as $attribute => $value) {
      $this->__set($attribute, $value);
    }
  }

  public function __get($attribute) {
    return $this->$attribute;
  }

  public function __set($attribute, $value) {
    //$this->validate($attribute, $value);
    $this->$attribute = $value;
  }

  public function __isset($attribute) {
    return !is_null($this->__get($attribute));
  }

  public function __unset($attribute) {
    $this->__set($attribute, null);
  }

  public function jsonSerialize() {
    return $this->attributes();
  }

  public function attach(SplObserver $view) {
    static::$observers->offsetGet($this)->attach($view);
  }

  public function detach(SplObserver $view) {
    static::$observers->offsetGet($this)->detach($view);
  }

  public function notify() {
    foreach (static::$observers->offsetGet($this) as $observer) {
      $observer->update($this);
    }
  }
}
