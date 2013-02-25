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
use RuntimeException, ErrorException;

class Buffer extends Event\Emitter implements Interfaces\Writable {
  public $stream;
  public $listening = false;
  public $softLimit = 2048;
  private $writable = true;
  private $loop;
  private $data;
  private $lastError = array(
    'number' => 0, 'message' => '', 'file' => '', 'line' => 0,
  );

  public function __construct($stream, Event\Interfaces\Loop $loop) {
    $this->stream = $stream;
    $this->loop = $loop;
    $this->data = '';
  }

  public function isWritable() {
    return $this->writable;
  }

  public function write($data) {
    if (!$this->writable) {
      return;
    }
    $this->data .= $data;
    if (!$this->listening) {
      $this->listening = true;
      $this->loop->addWriteStream($this->stream, array(
        $this, 'handleWrite'
      ));
    }
    $belowSoftLimit = strlen($this->data) < $this->softLimit;
    return $belowSoftLimit;
  }

  public function end($data = null) {
    if (null !== $data) {
      $this->write($data);
    }
    $this->writable = false;
    if ($this->listening) {
      $this->on('full-drain', array(
        $this, 'close'
      ));
    }
    else {
      $this->close();
    }
  }

  public function close() {
    $this->writable = false;
    $this->listening = false;
    $this->data = '';
    $this->emit('close');
  }

  public function handleWrite() {
    if (!is_resource($this->stream)) {// || feof($this->stream)) {
      $this->emit('error', array(
        new RuntimeException('Tried to write to closed or invalid stream.')
      ));
      return;
    }
    set_error_handler(array(
      $this, 'errorHandler'
    ));
    $sent = fwrite($this->stream, $this->data);
    restore_error_handler();
    if (false === $sent) {
      $this->emit('error', array(
        new ErrorException($this->lastError['message'], 0,
          $this->lastError['number'], $this->lastError['file'],
          $this->lastError['line'])
      ));
      return;
    }
    $len = strlen($this->data);
    if ($len >= $this->softLimit && $len - $sent < $this->softLimit) {
      $this->emit('drain');
    }
    $this->data = (string) substr($this->data, $sent);
    if (0 === strlen($this->data)) {
      $this->loop->removeWriteStream($this->stream);
      $this->listening = false;
      $this->emit('full-drain');
    }
  }

  private function errorHandler($errno, $errstr, $errfile, $errline) {
    $this->lastError['number'] = $errno;
    $this->lastError['message'] = $errstr;
    $this->lastError['file'] = $errfile;
    $this->lastError['line'] = $errline;
  }
}
