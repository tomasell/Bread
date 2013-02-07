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
use RuntimeException;

class Server extends Event\Emitter implements Interfaces\Server {
  public $master;
  private $loop;

  public function __construct(Event\Interfaces\Loop $loop) {
    $this->loop = $loop;
  }

  public function listen($port, $host = '127.0.0.1') {
    $this->master = stream_socket_server("tcp://$host:$port", $errno, $errstr);
    if (false === $this->master) {
      $message = "Could not bind to tcp://$host:$port: $errstr";
      throw new Exceptions\Connection($message, $errno);
    }
    stream_set_blocking($this->master, 0);
    $this->loop->addReadStream($this->master, function ($master) {
      $newSocket = stream_socket_accept($master);
      if (false === $newSocket) {
        $this->emit('error', array(
          new RuntimeException('Error accepting new connection')
        ));
        return;
      }
      $this->handleConnection($newSocket);
    });
  }

  public function handleConnection($socket) {
    stream_set_blocking($socket, 0);
    $client = $this->createConnection($socket);
    $this->emit('connection', array(
      $client
    ));
  }

  public function getPort() {
    $name = stream_socket_get_name($this->master, false);
    return (int) substr(strrchr($name, ':'), 1);
  }

  public function shutdown() {
    $this->loop->removeStream($this->master);
    fclose($this->master);
  }

  public function createConnection($socket) {
    return new Connection($socket, $this->loop);
  }

  public function run() {
    return $this->loop->run();
  }
}

