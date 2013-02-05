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

namespace Bread\Stream\Traits;

use Bread\Stream\Interfaces;

trait Pipe {
  public function pipe(Interfaces\Writable $dest, array $options = array()) {
    // TODO: use stream_copy_to_stream
    // it is 4x faster than this but can lose data under load with no way to
    // recover it
    $dest->emit('pipe', array(
      $this
    ));
    $this->on('data', function ($data) use ($dest) {
      $feedMore = $dest->write($data);
      if (false === $feedMore) {
        $this->pause();
      }
    });
    $dest->on('drain', function () {
      $this->resume();
    });
    $end = isset($options['end']) ? $options['end'] : true;
    if ($end && $this !== $dest) {
      $this->on('end', function () use ($dest) {
        $dest->end();
      });
    }
    return $dest;
  }

  public function forwardEvents($source, $target, array $events) {
    foreach ($events as $event) {
      $source->on($event, function () use ($event, $target) {
        $target->emit($event, func_get_args());
      });
    }
  }
}
