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

namespace Bread\Routing;

use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Exception;
use Bread\Networking\HTTP\Message\Header;

class Dispatcher {
  public function dispatch(Request $request) {
    $router = new Router();
    try {
      list($callback, $arguments) = $router->route($request);
      $response = call_user_func_array($callback, $arguments);
    } catch (Exception $exception) {
      $response = new Response($request, $exception->getCode(),
        $exception->getMessage(),
        array(
          new Header('Content-Type', 'text/plain', array(
            'charset' => 'utf-8'
          )),
          new Header('Content-Length', strlen($exception->getMessage()))
        ));
    }
    return $response;
  }
}
