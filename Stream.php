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

namespace Bread;

use Bread\Event;
use Bread\Stream;

class Stream extends Event\Emitter implements Stream\Interfaces\Readable,
  Stream\Interfaces\Writable {
  use Stream\Traits\Pipe;

  public $loop;
  public $stream;
  protected $readable = true;
  protected $writable = true;
  protected $closing = false;
  protected $bufferSize = 4096;
  protected $buffer;

  public function __construct($stream, Event\Interfaces\Loop $loop) {
    $this->stream = $stream;
    $this->loop = $loop;
    $this->buffer = new Stream\Buffer($this->stream, $this->loop);
    $this->buffer->on('error', function ($error) {
      $this->emit('error', array(
        $error, $this
      ));
      $this->close();
    });
    $this->buffer->on('drain', function () {
      $this->emit('drain');
    });
    $this->resume();
  }

  public function isReadable() {
    return $this->readable;
  }

  public function isWritable() {
    return $this->writable;
  }

  public function pause() {
    $this->loop->removeReadStream($this->stream);
  }

  public function resume() {
    $this->loop->addReadStream($this->stream, array(
      $this, 'handleData'
    ));
  }

  public function write($data) {
    if (!$this->writable) {
      return;
    }
    return $this->buffer->write($data);
  }

  public function close() {
    if (!$this->writable && !$this->closing) {
      return;
    }
    $this->closing = false;
    $this->readable = false;
    $this->writable = false;
    $this->emit('close', array(
      $this
    ));
    $this->loop->removeStream($this->stream);
    $this->buffer->removeAllListeners();
    $this->removeAllListeners();
    $this->handleClose();
  }

  public function end($data = null) {
    if (!$this->writable) {
      return;
    }
    $this->closing = true;
    $this->readable = false;
    $this->writable = false;
    $this->emit('end', array(
        $data
    ));
    $this->buffer->on('close', function () {
      $this->close();
    });
    $this->buffer->end($data);
  }

  public function handleData($stream) {
    $data = fread($stream, $this->bufferSize);
    $this->emit('data', array(
      $data, $this
    ));
    if (!is_resource($stream) || feof($stream)) {
      $this->end();
    }
  }

  public function handleClose() {
    if (is_resource($this->stream)) {
      fclose($this->stream);
    }
  }

  public function getBuffer() {
    return $this->buffer;
  }
}
