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

use Bread\Stream;

use Bread\Event;

class Parser extends Event\Emitter {
  const EXPECTING_EMPTY_LINE = 0;
  const EXPECTING_REQUEST_LINE = 1;
  const EXPECTING_HEADER_LINE = 2;
  const EXPECTING_BODY = 4;

  /**
   * RFC 2616 Section 5.1
   *
   * Request-Line = Method SP Request-URI SP HTTP-Version CRLF
   */
  const REQUEST_LINE_PATTERN = '/^(?<method>[A-Z]+) (?<uri>\S+) (?<version>\S+)$/';

  /**
   * RFC 2616 Section 4.2
   *
   * message-header = field-name ":" [ field-value ]
   */
  const HEADER_LINE_PATTERN = '/^(?<name>\S+):\s?(?<value>.*)$/i';
  const EMPTY_LINE_PATTERN = '/^$/';

  private $expecting;
  private $request;
  private $maxSize = 4096;

  public function __construct() {
    $this->reset();
  }

  public function reset() {
    $this->request = null;
    $this->expecting = static::EXPECTING_REQUEST_LINE;
    return $this;
  }

  public function parse($data, $connection) {
    try {
      if (strlen($data) > $this->maxSize) {
        throw new Client\Exceptions\RequestEntityTooLarge($this->maxSize);
      }
      list($lines, $data) = explode("\r\n\r\n", $data, 2)
        + array(
          array(), null
        );
      foreach (explode("\r\n", $lines, -1) as $line) {
        switch ($this->expecting) {
        case static::EXPECTING_REQUEST_LINE:
          print("Expecting Request-Line\n");
          if (preg_match(static::REQUEST_LINE_PATTERN, $line, $matches)) {
            $this->request = new Request($connection, $matches['method'],
              $matches['uri'], $matches['version']);
            print("New request from " . $connection->getRemoteAddress() . "\n");
            print("{$this->request->requestLine}\n");
            $this->request->on('end', function () {
              $this->expecting = static::EXPECTING_REQUEST_LINE;
            })->on('close', function () {
              $this->removeAllListeners();
            });
            switch ($this->request->protocol) {
            case 'HTTP/1.0':
            case 'HTTP/1.1':
              $this->expecting = static::EXPECTING_HEADER_LINE;
              break;
            default:
              print("HTTP/0.9\n");
              $this->emit('headers', array(
                $this->request, $data
              ));
            }
          }
          elseif (!preg_match(static::EMPTY_LINE_PATTERN, $line)) {
            throw new Client\Exceptions\BadRequest(
              'Expecting RFC2616 Request-Line');
          }
          break;
        case static::EXPECTING_HEADER_LINE:
          if (!$this->request) {
            throw new Server\Exceptions\InternalServerError();
          }
          if (preg_match(static::HEADER_LINE_PATTERN, $line, $matches)) {
            $this->request->header($matches['name'], trim($matches['value']));
            print("{$line}\n");
          }
          elseif (preg_match(static::EMPTY_LINE_PATTERN, $line)) {
            switch ($this->request->protocol) {
            case 'HTTP/1.1':
              if (!isset($this->request->headers['Host'])) {
                throw new Client\Exceptions\BadRequest(
                  'Host header required on HTTP/1.1');
              }
            }
            $this->emit('headers', array(
              $this->request, $data
            ));
          }
          else {
            throw new Client\Exceptions\BadRequest("Expecting header line");
          }
          break;
        default:
          throw new Client\Exceptions\BadRequest();
        }
      }
      if (!is_null($data)) {
        $this->emit('headers', array(
          $this->request, $data
        ));
      }
    } catch (Exception $exception) {
    print("Exception: {$exception->getMessage()}\n");
    $this->emit('error', array(
      $exception, $this
    ));
    }
  }
}
