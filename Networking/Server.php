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

use Bread\Event;
use Bread\Networking\HTTP\Exception;

class Server extends Event\Listener {
  const DEFAULT_TIMEOUT = 10;
  const MAX_CLIENTS = 25;
  const MAX_LINE_LENGTH = 8190;

  protected $socket;
  protected $clients;

  public function __construct($address = 'localhost', $port = 8000) {
    if (false
      === ($this->socket = stream_socket_server("$address:$port", $errno,
        $errstr))) {
      throw new Exception("$errstr ($errno)");
    }
    $this->clients = array();
  }

  public function __destruct() {
    $this->close();
  }

  public function accept() {
    stream_set_blocking($this->socket, 0);
    while (true) {
      $read = array(
        $this->socket
      );
      foreach ($this->clients as $client) {
        $read[] = $client->input;
      }
      if (!stream_select($read, $write = null, $except = null, 0)) {
        continue;
      }
      if (false !== ($key = array_search($this->socket, $read))) {
        if (false
          !== ($connection = stream_socket_accept($this->socket,
            self::DEFAULT_TIMEOUT, $peer))) {
          list($address, $port) = explode(':', $peer);
          $this->clients[] = new Connection($address, $port, $connection,
            $connection);
        }
      }
      foreach ($this->clients as $i => $client) {
        if (false !== ($key = array_search($client->input, $read))) {
          if (false !== ($data = $client->read(self::MAX_LINE_LENGTH))) {
            $this->trigger('data', $client, $data);
          }
          else {
            print("Disconnected\n");
            unset($this->clients[$i]);
          }
        }
      }
    }
  }

  public function send($connection, $stream) {
    while ($data = fgets($stream)) {
      $connection->write($data);
    }
  }

  protected function close() {
    foreach ($this->clients as $client) {
      $client->close();
    }
    fclose($this->socket);
  }
}
