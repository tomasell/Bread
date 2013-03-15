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

use Bread\L10n\Locale;
use Bread\View\Helpers\HTML;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;

abstract class View {
  protected $request;
  protected $response;

  public function __construct(Request $request, Response $response) {
    $this->request = $request;
    $this->response = $response;
  }

  public function compose(HTML\Page $page) {
    foreach ($page('[data-bread-block]') as $block) {
    }
    foreach ($this->response->messages as $severity => $messages) {
      foreach ($messages as $message) {
        switch ($severity) {
        case LOG_INFO:
          $class = 'alert.alert-info';
          break;
        case LOG_NOTICE:
          $class = 'alert.alert-success';
          break;
        case LOG_WARNING:
          $class = 'alert';
          break;
        case LOG_ERR:
          $class = 'alert.alert-error';
          break;
        }
        $page('[data-bread-messages]')->append("div.$class", $message);
      }
    }
    return $page;
  }

}
