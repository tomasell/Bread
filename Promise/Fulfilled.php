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

namespace Bread\Promise;

use Exception;

class Fulfilled implements Interfaces\Promise {
  use Traits\PromiseFor;

  private $result;

  public function __construct($result = null) {
    $this->result = $result;
  }

  public function then($fulfilledHandler = null, $errorHandler = null,
    $progressHandler = null) {
    try {
      $result = $this->result;
      if (is_callable($fulfilledHandler)) {
        $result = call_user_func($fulfilledHandler, $result);
      }
      elseif (null !== $fulfilledHandler) {
        trigger_error('Invalid $fulfilledHandler argument passed to then(), must be null or callable.', E_USER_NOTICE);
      }
      return static::promiseFor($result);
    } catch (Exception $exception) {
      return new Rejected($exception);
    }
  }
}
