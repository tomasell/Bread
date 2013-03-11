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

namespace Bread\Promise\Traits;

use Bread\Promise;
use Bread\Promise\Interfaces;

trait PromiseFor {
  public static function promiseFor($promiseOrValue) {
    if ($promiseOrValue instanceof Interfaces\Promise) {
      return $promiseOrValue;
    }
    return new Promise\Fulfilled($promiseOrValue);
  }

  public static function rejectedPromiseFor($promiseOrValue) {
    if ($promiseOrValue instanceof Interfaces\Promise) {
      return $promiseOrValue->then(function ($value) {
        return new Promise\Rejected($value);
      });
    }
    return new Promise\Rejected($promiseOrValue);
  }
}
