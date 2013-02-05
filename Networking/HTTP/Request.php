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

class Request extends Message {
  public $requestLine;
  public $method;
  public $uri;

  public function __construct($connection, $method = 'GET', $uri = '/',
    $protocol = 'HTTP/1.1', $headers = array(), $body = null) {
    $this->requestLine = implode(' ',
      array(
        $method, $uri, $protocol
      ));
    $this->method = $method;
    $this->uri = $uri;
    parent::__construct($connection, $protocol, $this->requestLine, $headers,
      $body);
  }

  public function __get($name) {
    switch ($name) {
    case 'host':
      return isset($this->headers['Host']) ? $this->headers['Host'] : null;
    default:
      return parent::__get($name);
    }
  }
}
