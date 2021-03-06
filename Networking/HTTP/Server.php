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

namespace Bread\Networking\HTTP;

use Bread\Networking;
use Bread\Event;

class Server extends Event\Emitter implements Interfaces\Server {
  private $io;

  public function __construct(Event\Interfaces\Loop $loop) {
    $this->io = new Networking\Server($loop);
    $this->io->on('connection', function ($conn) {
      // TODO: chunked transfer encoding
      // TODO: multipart parsing
      $parser = new Request\Parser();
      $parser->on('headers', function (Request $request, $data) use ($conn,
        $parser) {
        $this->handleRequest($conn, $parser, $request, $data);
        $conn->removeListener('data', array(
          $parser, 'parse'
        ));
        $conn->on('end', function () use ($request) {
          $request->emit('end');
        });
        $conn->on('data', function ($data) use ($request) {
          $request->emit('data', array(
            $data
          ));
        });
        $request->on('pause', function () use ($conn) {
          $conn->emit('pause');
        });
        $request->on('resume', function () use ($conn) {
          $conn->emit('resume');
        });
      });
      $conn->on('data', array(
        $parser, 'parse'
      ));
    });
  }

  public function handleRequest(Networking\Interfaces\Connection $conn,
    Request\Parser $parser, Request $request, $data) {
    $response = new Response($conn);
    $response->once('headers', function ($response) {
      $response->write((string) $response);
    });
    if (!$this->listeners('request')) {
      $response->end();
      return;
    }
    if (isset($request->headers['Connection'])
      && 'close' === $request->headers['Connection']) {
      $parser->removeAllListeners();
    }
    else {
      $request->on('end', function () use ($conn, $parser) {
        $conn->on('data', array(
          $parser->reset(), 'parse'
        ));
      });
    }
    $this->emit('request', array(
      $request, $response
    ));
    $request->on('data', function ($data) use ($request) {
      $request->receivedLength += strlen($data);
      printf("Received %d of %d\n", $request->receivedLength, $request->contentLength);
      if ($request->receivedLength >= $request->contentLength) {
        $request->end();
      }
    });
    if ((isset($request->headers['Content-Length'])
      || isset($request->headers['Transfer-Encoding']))) {
      is_null($data) || $request->emit('data', array(
          $data
        ));
    }
    else {
      $request->end();
    }
  }

  public function listen($port = 80, $host = '127.0.0.1') {
    return $this->io->listen($port, $host);
  }

  public function getPort() {
    return $this->io->getPort();
  }

  public function run() {
    return $this->io->run();
  }

  public function shutdown() {
    return $this->io->shutdown();
  }
}
