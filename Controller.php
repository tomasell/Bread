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

namespace Bread;

use Bread\Configuration;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;

/**
 * Application controller class for organization of business logic.
 */
abstract class Controller {
  /**
   * The HTTP request to control
   *
   * @var Request $request
   */
  protected $request;

  /**
   * The HTTP response to generate
   *
   * @var Response $response
   */
  protected $response;
  protected $deferred;
  protected $resolver;
  protected $promise;
  protected $data;

  public function __construct(Request $request, Response $response) {
    $this->request = $request;
    $this->response = $response;
    $this->deferred = new Promise\Deferred();
    $this->resolver = $this->deferred->resolver();
    $this->promise = $this->deferred->promise();
    $this->data = new Promise\Deferred();
    $this->request->on('data', function($data) {
      $this->data->progress($data);
    })->on('end', function() {
      $this->resolver->resolve();
    })->body->on('end', function($data) {
      $this->data->resolve($this->request->body->contents());
    });
  }

  public static function get($key = null) {
    $class = get_called_class();
    return Configuration\Manager::get($class, $key);
  }
}
