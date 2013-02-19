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

namespace Bread\Networking\HTTP\Connectors;

use Bread\Networking\HTTP;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Event;

class Apache2 extends Event\Emitter implements HTTP\Interfaces\Server {
  private $loop;

  public function __construct(Event\Interfaces\Loop $loop) {
    $this->loop = $loop;
  }

  public function run() {
    $headers = apache_request_headers();
    if (isset($headers['Content-Type'])) {
      $contentType = $headers['Content-Type'];
      if (preg_match('|^multipart/form-data-apache|', $contentType)) {
        $contentType = $headers['X-Content-Type'];
        unset($headers['X-Content-Type']);
        $headers['Content-Type'] = $contentType;
      }
    }
    $connection = new Apache2\Connection($this->loop);
    $request = new Request($connection, $_SERVER['REQUEST_METHOD'],
      $_SERVER['REQUEST_URI'], $_SERVER['SERVER_PROTOCOL'], $headers);
    $connection->on('end', function () use ($request) {
      $request->emit('end');
    });
    $connection->on('data', function ($data) use ($request, $connection) {
      $request->emit('data', array(
        $data
      ));
      if (feof($connection->input)) {
        $request->end();
      }
    });
    $request->on('pause', function () use ($connection) {
      $connection->emit('pause');
    });
    $request->on('resume', function () use ($connection) {
      $connection->emit('resume');
    });
    $response = new Response($connection);
    $response->once('headers', function($response) {
      foreach (apache_response_headers() as $name => $value) {
        header_remove($name);
      }
      header($response->statusLine);
      foreach ($response->headers as $name => $value) {
        header("{$name}: {$value}");
      }
      header("X-Powered-By: " . __CLASS__);
    });
    $this->emit('request', array(
      $request, $response
    ));
    $this->loop->run();
  }

  public function listen($port = 80, $host = '127.0.0.1') {
  }

  public function getPort() {
    return (int) $_SERVER['SERVER_PORT'];
  }

  public function shutdown() {
    $this->loop->stop();
  }
}
