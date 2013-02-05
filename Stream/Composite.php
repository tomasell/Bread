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

namespace Bread\Stream;

use Bread\Event;

class Composite extends Event\Emitter implements Interfaces\Readable,
  Interfaces\Writable {
  use Traits\Pipe;

  protected $readable;
  protected $writable;
  protected $pipeSource;

  public function __construct(Interfaces\Readable $readable,
    Interfaces\Writable $writable) {
    $this->readable = $readable;
    $this->writable = $writable;
    $this->forwardEvents($this->readable, $this, array(
      'data', 'end', 'error', 'close'
    ));
    $this->forwardEvents($this->writable, $this, array(
      'drain', 'error', 'close', 'pipe'
    ));
    $this->readable->on('close', array(
      $this, 'close'
    ));
    $this->writable->on('close', array(
      $this, 'close'
    ));
    $this->on('pipe', array(
      $this, 'handlePipeEvent'
    ));
  }

  public function handlePipeEvent($source) {
    $this->pipeSource = $source;
  }

  public function isReadable() {
    return $this->readable->isReadable();
  }

  public function pause() {
    if ($this->pipeSource) {
      $this->pipeSource->pause();
    }
    $this->readable->pause();
  }

  public function resume() {
    if ($this->pipeSource) {
      $this->pipeSource->resume();
    }
    $this->readable->resume();
  }

  public function isWritable() {
    return $this->writable->isWritable();
  }

  public function write($data) {
    return $this->writable->write($data);
  }

  public function end($data = null) {
    $this->writable->end($data);
  }

  public function close() {
    $this->pipeSource = true;
    $this->readable->close();
    $this->writable->close();
  }
}
