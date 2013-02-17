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

namespace Bread\Networking\HTTP\Message;

use ArrayIterator, ArrayAccess, Countable, IteratorAggregate;

class Headers implements ArrayAccess, Countable, IteratorAggregate {
  protected $headers;

  public function __construct($headers = array()) {
    $this->headers = $headers;
  }

  public function __toString() {
    return implode("\r\n",
      array_map(
        function ($offset, $value) {
          return "{$offset}: {$value}";
        }, array_keys($this->headers), $this->headers)) . "\r\n";
  }

  public function offsetExists($offset) {
    return isset($this->headers[$offset]);
  }

  public function offsetGet($offset) {
    return isset($this->headers[$offset]) ? $this->headers[$offset] : null;
  }

  public function offsetSet($offset, $value) {
    $this->headers[$offset] = $value;
  }

  public function offsetUnset($offset) {
    unset($this->headers[$offset]);
  }

  public function count() {
    return count($this->headers);
  }

  public function getIterator() {
    return new ArrayIterator($this->headers);
  }
}
