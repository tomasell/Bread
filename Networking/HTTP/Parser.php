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

  private $expecting;
  private $request;
  private $maxSize = 4096;
  private $contentLength = 0;
  private $bodyLength = 0;

  public function __construct() {
    $this->expecting = static::EXPECTING_REQUEST_LINE;
  }

  public function parse($data, $connection) {
    // TODO $data può contenere più righe della richiesta e anche già il body!
    try {
      if (strlen($data) > $this->maxSize) {
        throw new Client\Exceptions\RequestEntityTooLarge($this->maxSize);
      }
      switch ($this->expecting) {
      case static::EXPECTING_REQUEST_LINE:
        if (preg_match(static::REQUEST_LINE_PATTERN, $data, $matches)) {
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
            $this->emit('headers', array(
              $this->request
            ));
          }
        }
        elseif (!preg_match(static::EMPTY_LINE_PATTERN, $data)) {
          throw new Client\Exceptions\BadRequest();
        }
        break;
      case static::EXPECTING_HEADER_LINE:
        if (!$this->request) {
          throw new Server\Exceptions\InternalServerError();
        }
        if (preg_match(static::HEADER_LINE_PATTERN, $data, $matches)) {
          $this->request->header($matches['name'], trim($matches['value']));
          print("{$data}\n");
        }
        elseif (preg_match(static::EMPTY_LINE_PATTERN, $data)) {
          switch ($this->request->version) {
          case 'HTTP/1.1':
            if (!isset($this->request->headers['Host'])) {
              throw new Client\Exceptions\BadRequest();
            }
          }
          $this->emit('headers', array(
            $this->request
          ));
          if (($contentLength = isset($this->request->headers['Content-Length']))
            || ($transferEncoding = isset($this->request->headers['Transfer-Encoding']))) {
            if ($contentLength) {
              $this->contentLength = (int) $this->request->headers['Content-Length'];
              print("Content-Length detected: {$this->contentLength}\n");
            }
            $this->expecting = static::EXPECTING_BODY_LINE;
          }
          else {
            $this->request->emit('end');
          }
        }
        else {
          throw new Client\Exceptions\BadRequest("Expecting header line");
        }
        break;
      case static::EXPECTING_BODY_LINE:
        if (!$this->request) {
          throw new Server\Exceptions\InternalServerError();
        }
        $length = strlen($data);
        $this->bodyLength += strlen($data);
        print("Received $length ({$this->bodyLength} total) of {$this->contentLength}\n");
        $this->request->emit('data', $data);
        if ($this->contentLength <= $this->bodyLength) {
          $this->request->emit('end');
        }
        break;
      default:
      }
    } catch (Exception $exception) {
      $this->emit('error', array(
        $exception, $this
      ));
    }
  }
}
