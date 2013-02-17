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
use Bread\Promise;
use RuntimeException;

class Client extends Event\Emitter implements Interfaces\Client {
  private $loop;

  public function __construct(Event\Interfaces\Loop $loop) {
    $this->loop = $loop;
  }

  public function connect($host, $port) {
    $url = $this->socketUrl($host, $port);
    $socket = stream_socket_client($url, $errno, $errstr, 0, STREAM_CLIENT_ASYNC_CONNECT);
    if (false === $socket) {
      $message = "Could not connect to $uri: $errstr";
      throw new Exceptions\Connection($message, $errno);
    }
    stream_set_blocking($socket, 0);
    return $this->waitForStream($socket)->then(array(
      $this, 'handleConnection'
    ));
  }

  public function handleConnection($socket) {
    return new Connection($socket, $this->loop);
  }

  protected function waitForStream($stream) {
    $deferred = new Promise\Deferred();
    $this->loop->addWriteStream($stream, function ($stream) use ($deferred) {
      $this->loop->removeWriteStream($stream);
      $deferred->resolve($stream);
    });
    return $deferred->promise();
  }

  protected function socketUrl($host, $port, $transport = 'tcp') {
    return sprintf('%s://%s:%s', $transport, $host, $port);
  }
}

