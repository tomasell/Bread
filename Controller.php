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

use Bread\Core;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;

/**
 * Application controller class for organization of business logic.
 */
abstract class Controller extends Core\Dough {
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

  public function __construct(Request $request, Response $response) {
    parent::__construct();
    $this->request = $request;
    $this->response = $response;
  }
}
