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

class Rejected implements Interfaces\Promise {
  use Traits\PromiseFor;

  private $reason;

  public function __construct($reason = null) {
    $this->reason = $reason;
  }

  public function then($fulfilledHandler = null, $errorHandler = null,
    $progressHandler = null) {
    try {
      if (!is_callable($errorHandler)) {
        if (null !== $errorHandler) {
          trigger_error('Invalid $errorHandler argument passed to then(), must be null or callable.', E_USER_NOTICE);
        }
        return new Rejected($this->reason);
      }
      return static::promiseFor(call_user_func($errorHandler, $this->reason));
    } catch (Exception $exception) {
      return new Rejected($exception);
    }
  }
}
