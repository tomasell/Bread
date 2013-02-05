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

namespace Bread\Promise\Deferred;

use Bread;

class Promise implements Bread\Interfaces\Promise {
  private $deferred;

  public function __construct(Deferred $deferred) {
    $this->deferred = $deferred;
  }

  public function then($fulfilledHandler = null, $errorHandler = null,
    $progressHandler = null) {
    return $this->deferred->then($fulfilledHandler, $errorHandler, $progressHandler);
  }
}
