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

namespace Bread\Cache\Engines;

use Bread;
use Bread\Promise\When;
use APCIterator;

class APC implements Bread\Interfaces\Cache {
  public function get($key) {
    $result = apc_fetch($key, $success);
    if (!$success) {
      return When::reject();
    }
    return When::resolve($result);
  }

  public function set($key, $var) {
    apc_store($key, $var);
  }

  public function remove($key) {
    $iterator = new APCIterator('user',
      '/^' . preg_quote($key, '/') . '/', APC_ITER_VALUE);
    apc_delete($iterator);
  }

  public function clear() {
    return apc_clear_cache('user');
  }
}
