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

namespace Bread\L10n\Message;

use Bread;
use Bread\L10n\Locale\Controller as Locale;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;

class Controller extends Bread\Controller {
  const DEFAULT_DOMAIN = 'default';
  const DEFAULT_PLURAL = 'nplurals=2;plural=n!=1;';

  protected $domain;

  public function __construct(Request $request, Response $response,
    $domain = self::DEFAULT_DOMAIN) {
    parent::__construct($request, $response);
    $this->domain = $domain;
    Locale::configure();
  }

  public function __invoke($msgid) {
    $arguments = func_get_args();
    $msgid = array_shift($arguments);
    array_unshift($arguments, $this->domain, $msgid, null, 1);
    return call_user_func_array(array(
        $this, 'localize'
      ), $arguments);
  }

  public function localize($domain, $msgid, $msgid_plural = null, $n = 1) {
    $arguments = func_get_args();
    $domain = array_shift($arguments);
    $msgid = array_shift($arguments);
    $msgid_plural = array_shift($arguments);
    $n = array_shift($arguments);
    $search = array(
      'locale' => Locale::$current,
      'category' => LC_MESSAGES,
      'domain' => $domain,
      'msgid' => $msgid,
      'msgid_plural' => $msgid_plural,
      'n' => $n
    );
    return Model::first($search)
      ->then(
        function ($message) use ($arguments, $n) {
          $plural = $this->plural(Locale::$current->plural, $n);
          return vsprintf($message->msgstr[$plural], $arguments);
        },
        function () use ($search, $arguments) {
          return vsprintf($search['msgid'], $arguments);
        });
  }

  public function t($msgid) {
    $arguments = func_get_args();
    $msgid = array_shift($arguments);
    array_unshift($arguments, $this->domain, $msgid, null, 1);
    return call_user_func_array(array(
        $this, 'localize'
      ), $arguments);
  }

  public function tp($singular, $plural, $n = 1) {
    $arguments = func_get_args();
    array_unshift($arguments, $this->domain);
    return call_user_func_array(array(
        $this, 'localize'
      ), $arguments);
  }

  public function dt($domain, $msgid) {
    $arguments = func_get_args();
    $domain = array_shift($arguments);
    $msgid = array_shift($arguments);
    array_unshift($arguments, $domain, $msgid, null, 1);
    return call_user_func_array(array(
        $this, 'localize'
      ), $arguments);
  }

  public function dtp($domain, $singular, $plural, $n = 1) {
    return call_user_func_array(array(
        $this, 'localize'
      ), func_get_args());
  }

  protected function plural($plural, $n) {
    return 0;
  }
}
