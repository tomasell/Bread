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

namespace Bread\Model;

use SplObjectStorage, JsonSerializable;

class Attribute extends SplObjectStorage implements JsonSerializable {
  protected $current;

  public function __construct($storage = array()) {
    foreach ($storage as $item) {
      $this->attach($item['_obj'], $item['_inf']);
    }
  }

  public function __toString() {
    return $this->offsetGet($this->current);
  }

  public function __toArray() {
    $storage = array();
    foreach ($this as $obj) {
      $storage[] = array(
        '_obj' => $obj, '_inf' => $this->offsetGet($obj)
      );
    }
    return $storage;
  }

  public static function is($array) {
    if ($array instanceof static) {
      return true;
    }
    if (is_array($array)) {
      if (($current = current($array)) && is_array($current)) {
        return (isset($current['_obj']) && isset($current['_inf']));
      }
    }
    return false;
  }

  public function seek($model) {
    $this->current = $model;
  }

  public function getHash($model) {
    return $model->key(true);
  }
  
  public function jsonSerialize() {
    return $this->offsetGet($this->current);
  }
}
