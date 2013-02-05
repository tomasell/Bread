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
use SplObjectStorage, UnexpectedValueException;

class Server extends Networking\Server {
  const EXPECTING_EMPTY_LINE = 'EXPECTING_EMPTY_LINE';
  const EXPECTING_REQUEST_LINE = 'EXPECTING_REQUEST_LINE';
  const EXPECTING_HEADER_LINE = 'EXPECTING_HEADER_LINE';
  const EXPECTING_BODY_LINE = 'EXPECTING_BODY_LINE';

  /**
   * RFC 2616 Section 5.1
   *
   * Request-Line = Method SP Request-URI SP HTTP-Version CRLF
   */
  const REQUEST_LINE_PATTERN = '/^(?<method>[A-Z]+) (?<uri>\S+) (?<version>\S+)\r\n$/';

  /**
   * RFC 2616 Section 4.2
   *
   * message-header = field-name ":" [ field-value ]
   */
  const HEADER_LINE_PATTERN = '/^(?<name>\S+):\s?(?<value>.*)\r\n$/i';

  const EMPTY_LINE_PATTERN = '/^\r\n$/';

  protected $protocol;
  protected $requests;
  protected $expecting;

  public function __construct($address = 'localhost', $port = 8000) {
    parent::__construct($address, $port);
    $this->protocol = 'HTTP/1.1';
    $this->requests = new SplObjectStorage();
    $this->expecting = new SplObjectStorage();
    $this->on('data', array(
        $this, 'process'
      ));
  }

  public function send(Response $response) {
    $response->header('Server', "Bread Framework");
    $response->connection->write("{$response->statusLine}\r\n");
    foreach ($response->headers as $header => $value) {
      $response->connection->write("{$header}: {$value}\r\n");
    }
    $response->connection->write("\r\n");
    while ($data = fgets($response->body)) {
      $response->connection->write($data);
    }
    $response->trigger('sent');
  }

  protected function process($connection, $data) {
    if (!$this->requests->offsetExists($connection)) {
      $this->expecting->attach($connection, self::EXPECTING_REQUEST_LINE);
    }
    try {
      switch ($this->expecting->offsetGet($connection)) {
      case self::EXPECTING_REQUEST_LINE:
        if (preg_match(self::REQUEST_LINE_PATTERN, $data, $matches)) {
          $request = new Request($connection, $matches['method'],
            $matches['uri'], $matches['version']);
          $request
            ->on('end',
              function () use ($connection) {
                $this->expecting
                  ->attach($connection, self::EXPECTING_REQUEST_LINE);
                $this->requests->detach($connection);
              });
          $this->requests->attach($connection, $request);
          switch ($request->protocol) {
          case 'HTTP/1.0':
          case 'HTTP/1.1':
            $this->expecting->attach($connection, self::EXPECTING_HEADER_LINE);
            break;
          default:
            $this->trigger('request', $request);
          }
        }
        elseif (!preg_match(self::EMPTY_LINE_PATTERN, $data)) {
          throw new Client\Exceptions\BadRequest();
        }
        break;
      case self::EXPECTING_HEADER_LINE:
        if (null === ($request = $this->requests->offsetGet($connection))) {
          throw new Server\Exceptions\InternalServerError();
        }
        if (preg_match(self::HEADER_LINE_PATTERN, $data, $matches)) {
          $request->header($matches['name'], trim($matches['value']));
        }
        elseif (preg_match(self::EMPTY_LINE_PATTERN, $data)) {
          switch ($request->version) {
          case 'HTTP/1.1':
            if (!isset($request->headers['Host'])) {
              throw new Client\Exceptions\BadRequest();
            }
          }
          $this->trigger('request', $request);
          if (isset($request->headers['Content-Length'])
            || isset($request->headers['Transfer-Encoding'])) {
            $this->expecting->attach($connection, self::EXPECTING_BODY_LINE);
            //$request->body(fopen('php://temp', 'w+'));
          }
          else {
            $request->trigger('end', $this);
          }
        }
        else {
          throw new Client\Exceptions\BadRequest("Expecting header.");
        }
        break;
      case self::EXPECTING_BODY_LINE:
        if (null === ($request = $this->requests->offsetGet($connection))) {
          throw new Server\Exceptions\InternalServerError();
        }
        if (isset($request->headers['Content-Length'])) {
          $contentLength = (int) $request->headers['Content-Length'];
          //fwrite($request->body, $data);
          $request->trigger('data', $data);
          if ($contentLength === ftell($request->body)) {
            fseek($request->body, 0);
            $request->trigger('end', $this);
          }
        }
        break;
      default:
      }
    } catch (Exception $exception) {
      try {
        $request = $this->requests->offsetGet($connection);
      } catch (UnexpectedValueException $uve) {
        $request = new Request($connection);
      }
      $this->requests->detach($connection);
      $this->expecting->detach($connection);
      $this
        ->send(
          new Response($request, $exception->getCode(),
            $exception->getMessage(),
            array(
              'Content-Type' => 'text/plain; charset=utf8',
              'Content-Length' => strlen($exception->getMessage())
            )));
    }
  }
}
