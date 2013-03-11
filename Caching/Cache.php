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

namespace Bread\Caching;

use Bread\Dough;

class Cache extends Dough\Singleton {
  public static function factory() {
    if (class_exists('APCIterator')) {
      return new Engines\APC();
    }
    return new Engines\Internal();
  }

  public static function instance() {
    if (!isset(static::$instance)) {
      static::$instance = static::factory();
    }
    return static::$instance;
  }
}