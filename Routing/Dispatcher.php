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

use Bread\Networking\HTTP;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Client\Exceptions;
use Bread\Promise;
use Exception;

class Dispatcher {
  public function dispatch(Request $request, Response $response) {
    $router = new Router();
    $router->route($request, $response)->then(function ($result) use ($request, $response) {
      list($callback, $arguments) = $result;
      return call_user_func_array($callback, $arguments);
    })->then(function ($output) use ($response) {
      $response->end($output);
    }, function (Exception $exception) use ($response) {
      if ($exception instanceof HTTP\Exception) {
        $response->status($exception->getCode());
      }
      else {
        $response->status(500);
      }
      $response->end($exception->getMessage());
    });
  }
}
