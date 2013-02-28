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

namespace Bread\L10n;

use Bread;
use Bread\Model\Attribute;
use Bread\L10n\Locale\Controller as Locale;

abstract class Localized extends Bread\Model {
  protected static $localized = array();
  
  public function __get($attribute) {
    if (in_array($attribute, static::$localized)) {
      if (!is_a($this->$attribute, 'Bread\Model\Attribute')) {
        $this->$attribute = new Attribute();
      }
      return $this->$attribute->offsetGet(Locale::$current);
    }
    else {
      return parent::__get($attribute);
    }
  }
  
  public function __set($attribute, $value) {
    if (in_array($attribute, static::$localized)) {
      if (!is_a($this->$attribute, 'Bread\Model\Attribute')) {
        $this->$attribute = new Attribute();
      }
      var_dump($value);
      if (is_array($value)) {
        foreach ($value as $v) {
          $this->validate($attribute, $v['$val']);
          $this->$attribute->attach($v['$key'], $v['$val']);
        }
      }
      else {
        $this->validate($attribute, $value);
        $this->$attribute->attach(Locale::$current, $value);
      }
    }
    else {
      parent::__set($attribute, $value);
    }
  }
  
  public static function configure($configuration = array()) {
    Locale::configure();
    return parent::configure($configuration);
  }
}