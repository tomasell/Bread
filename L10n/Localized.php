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

  public function __construct($attributes = array()) {
    foreach (static::$localized as $attribute) {
      $this->$attribute = new Attribute();
    }
    parent::__construct($attributes);
  }

  public function __get($attribute) {
    if (in_array($attribute, static::$localized)) {
      return $this->$attribute->offsetGet(Locale::$current);
    }
    return parent::__get($attribute);
  }

  public function __set($attribute, $value) {
    if (in_array($attribute, static::$localized)) {
      if ($value instanceof Attribute) {
        $this->validate($attribute, $value->getInfo());
        $this->$attribute = $value;
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
}
