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

namespace Bread\Caching\Engines;

use Bread;
use Bread\Caching;
use Bread\Promise;

class Internal implements Caching\Interfaces\Engine {
  private $data = array();

  public function fetch($key) {
    if (!isset($this->data[$key])) {
      return Promise\When::reject($key);
    }
    return Promise\When::resolve($this->data[$key]);
  }

  public function store($key, $value) {
    $this->data[$key] = $value;
    return Promise\When::resolve($value);
  }

  public function delete($key) {
    if (isset($this->data[$key])) {
      unset($this->data[$key]);
    }
    return Promise\When::resolve($key);
  }

  public function clear() {
    $this->data = array();
    return Promise\When::resolve();
  }
}
