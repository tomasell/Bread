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

namespace Bread\Networking\HTTP\Server\Connectors;

use Bread\Networking\Connection;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Server\ConnectorInterface;

class Apache2 implements ConnectorInterface {
  public function accept() {
    $headers = apache_request_headers();
    if (isset($headers['Content-Type'])) {
      $contentType = $headers['Content-Type'];
      if (preg_match('|^multipart/form-data-apache|', $contentType)) {
        $contentType = $headers['X-Content-Type'];
        unset($headers['X-Content-Type']);
        $headers['Content-Type'] = $contentType;
      }
    }
    $request = new Request(
      new Connection($_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'],
        fopen('php://output', 'w'), fopen('php://input', 'r')),
      $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'],
      $_SERVER['SERVER_PROTOCOL'], $headers);
    return $request;
  }

  public function send(Response $response) {
    foreach (apache_response_headers() as $name => $value) {
      header_remove($name);
    }
    header($response->statusLine);
    foreach ($response->headers as $header => $value) {
      header("{$header}: {$value}");
    }
    while ($data = fgets($response->body)) {
      $response->connection->write($data);
    }
    $response->trigger('sent');
  }
}
