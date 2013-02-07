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

namespace Bread\Networking\HTTP\Connectors\Apache2;

use Bread\Event;
use Bread\Stream;
use Bread\Networking;

class Connection extends Stream implements Networking\Interfaces\Connection {
  use Stream\Traits\Pipe;

  public $input;

  public function __construct(Event\Interfaces\Loop $loop) {
    $this->input = fopen('php://input', 'r');
    $this->stream = fopen('php://output', 'w');
    parent::__construct($this->stream, $loop);
    $this->loop->addReadStream($this->input, array(
      $this, 'handleData'
    ));
  }

  public function pause() {
    $this->loop->removeReadStream($this->input);
  }

  public function resume() {
    $this->loop->addReadStream($this->input, array(
      $this, 'handleData'
    ));
  }

  public function handleData($stream, $loop) {
    $data = fread($stream, $this->bufferSize);
    $this->emit('data', array(
        $data, $this
    ));
    if (!is_resource($stream) || feof($stream)) {
      return false;
    }
  }

  public function handleClose() {
    if (is_resource($this->input)) {
      fclose($this->input);
    }
    parent::handleClose();
  }

  public function getRemoteAddress() {
    return "{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']}";
  }
}
