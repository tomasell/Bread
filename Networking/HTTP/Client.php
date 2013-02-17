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

class Client {
  private $loop;

  public function __construct(Event\Interfaces\Loop $loop) {
    $this->loop = $loop;
  }

  public function get($url, $headers = array(), $protocol = 'HTTP/1.1') {
    $client = new Networking\Client($this->loop);
    $host = parse_url($url, PHP_URL_HOST);
    $port = parse_url($url, PHP_URL_PORT);
    $path = parse_url($url, PHP_URL_PATH);
    $client->connect($host, $port)->then(function ($connection) use ($path) {
      $this->handleConnection($connection);
      $request = new Request($connection, 'GET', $path, $protocol, $headers);
      $request->end($request);
    });
  }

  public function handleConnection($connection) {
    $parser = new Response\Parser();
    $parser->on('headers', function (Response $response, $data) use (
      $connection, $parser) {
      $this->handleResponse($conn, $parser, $response, $data);
      $connection->removeListener('data', array(
        $parser, 'parse'
      ));
      $connection->on('end', function () use ($response) {
        $response->emit('end');
      });
      $connection->on('data', function ($data) use ($response) {
        $response->emit('data', array(
          $data
        ));
      });
      $response->on('pause', function () use ($connection) {
        $connection->emit('pause');
      });
      $response->on('resume', function () use ($connection) {
        $connection->emit('resume');
      });
    });
    $connection->on('data', array(
      $parser, 'parse'
    ));
  }

  public function handleResponse(Networking\Interfaces\Connection $conn,
      Response\Parser $parser, Response $response, $data) {
  }
}

