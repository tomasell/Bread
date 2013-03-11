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

namespace Bread\Model\Database;

use Bread;
use Bread\Model\Database;
use Bread\Configuration\Manager as CM;
use Exception;

class Reference {
  public $_class;
  public $_key;

  public function __construct($object) {
    $this->_class = get_class($object);
    $this->_key = $this->keys($object);
  }

  protected function keys($object) {
    $class = get_class($object);
    $keys = array();
    foreach ((array) CM::get($class, 'keys') as $key) {
      $keys[$key] = $object->$key;
    }
    return $keys;
  }

  public static function is($reference) {
    if ($reference instanceof Reference) {
      return true;
    }
    elseif (is_array($reference)) {
      $reference = (object) $reference;
    }
    elseif (is_string($reference)) {
      $reference = json_decode($reference);
    }
    if (isset($reference->_class) && isset($reference->_key)) {
      return true;
    }
    return false;
  }

  public static function fetch($reference) {
    if (!static::is($reference)) {
      throw new Exception("Not a valid reference");
    }
    elseif (is_string($reference)) {
      $reference = json_decode($reference, true);
    }
    if (is_array($reference)) {
      $reference = (object) $reference;
    }
    $class = $reference->_class;
    return Database::driver($class)->first($class, $reference->_key);
  }
}