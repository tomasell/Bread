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

namespace Bread\Networking;

class Connection {
  public $address;
  public $port;
  public $output;
  public $input;

  public function __construct($address, $port, $output, $input) {
    $this->address = $address;
    $this->port = $port;
    $this->output = $output;
    $this->input = $input;
  }

  public function __destruct() {
    $this->close();
  }

  public function write($data) {
    return fwrite($this->output, $data);
  }

  public function read($length = null) {
    return fgets($this->input, $length);
  }

  public function close() {
  }
}
