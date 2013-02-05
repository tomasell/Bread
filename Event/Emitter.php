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

namespace Bread\Event;

abstract class Emitter implements Interfaces\Emitter {
  protected $listeners = [];

  public function on($event, callable $listener) {
    if (!isset($this->listeners[$event])) {
      $this->listeners[$event] = [];
    }
    $this->listeners[$event][] = $listener;
    return $this;
  }

  public function once($event, callable $listener) {
    $onceListener = function () use (&$onceListener, $event, $listener) {
      $this->removeListener($event, $onceListener);
      call_user_func_array($listener, func_get_args());
    };
    return $this->on($event, $onceListener);
  }

  public function removeListener($event, callable $listener) {
    if (isset($this->listeners[$event])) {
      if (false !== $index = array_search($listener, $this->listeners[$event], true)) {
        unset($this->listeners[$event][$index]);
      }
    }
  }

  public function removeAllListeners($event = null) {
    if ($event !== null) {
      unset($this->listeners[$event]);
    } else {
      $this->listeners = [];
    }
  }

  public function listeners($event) {
    return isset($this->listeners[$event]) ? $this->listeners[$event] : [];
  }

  public function emit($event, array $arguments = []) {
    foreach ($this->listeners($event) as $listener) {
      call_user_func_array($listener, $arguments);
    }
    return $this;
  }
}
