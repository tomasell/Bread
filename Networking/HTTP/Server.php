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
      // TODO: http 1.1 keep-alive
      // TODO: chunked transfer encoding (also for outgoing data)
      // TODO: multipart parsing
      $parser = new Parser();
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
        $parser->removeAllListeners();
      });
      $conn->on('data', array(
        $parser, 'parse'
      ));
    });
  }

  public function handleRequest(Networking\Interfaces\Connection $conn,
    Parser $parser, Request $request, $data) {
    $response = new Response($request);
    $response->on('close', array(
      $request, 'close'
    ));
    if (!$this->listeners('request')) {
      $response->end();
      return;
    }
    if (isset($request->headers['Connection'])
      && 'close' === $request->headers['Connection']) {
      $parser->removeAllListeners();
    }
    else {
      $response->on('close', function () use ($conn, $parser) {
        $parser->reset();
        $conn->on('data', array(
          $parser, 'parse'
        ));
      });
    }
    $this->emit('request', array(
      $request, $response
    ));
    if ((isset($request->headers['Content-Length'])
      || isset($request->headers['Transfer-Encoding'])) && !is_null($data)) {
      $request->emit('data', array(
        $data
      ));
    }
    else {
      $request->emit('end');
    }
  }

  public function listen($port = 80, $host = '127.0.0.1') {
    return $this->io->listen($port, $host);
  }

  public function getPort() {
    return $this->io->getPort();
  }

  public function shutdown() {
    return $this->io->shutdown();
  }
}
