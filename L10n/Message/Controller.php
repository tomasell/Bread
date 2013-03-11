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
use Bread\L10n\Gettext\PluralForms;

class Controller extends Bread\Controller {
  const DEFAULT_DOMAIN = 'default';

  protected $domain;

  public function __construct(Request $request, Response $response,
    $domain = self::DEFAULT_DOMAIN) {
    parent::__construct($request, $response);
    $this->domain = $domain;
    if (Model::get('engine.gettext')) {
      bindtextdomain($this->domain, BREAD_ROOT . DS . 'locale');
    }
  }

  public function __invoke() {
    return call_user_func_array(array($this, 't'), func_get_args());
  }

  public function t($msgid) {
    $arguments = func_get_args();
    $msgid = array_shift($arguments);
    array_unshift($arguments, $msgid, null, 1, '');
    return call_user_func_array(array($this, 'localize'), $arguments);
  }

  public function p($msgid, $msgid_plural, $n) {
    $arguments = array_slice(func_get_args(), 3);
    array_unshift($arguments, $msgid, $msgid_plural, $n, '');
    return call_user_func_array(array($this, 'localize'), $arguments);
  }

  public function localize($msgid, $msgid_plural = null, $n = 1, $msgctxt = '') {
    $arguments = array_slice(func_get_args(), 4);
    switch (Model::get('engine')) {
    case 'gettext':
      if ($msgctxt) {
        $msgctxt = "{$msgctxt}\004{$msgid}";
        $msgstr = dcngettext($this->domain, $msgctxt, $msgid_plural, $n, LC_MESSAGES);
        if ($msgstr == $msgctxt || $msgstr == $msgid_plural) {
          $msgstr = ($n == 1 ? $msgid : $msgid_plural);
        }
      }
      else {
        $msgstr = dcngettext($this->domain, $msgid, $msgid_plural, $n, LC_MESSAGES);
      }
      return Bread\Promise\When::resolve(vsprintf($msgstr, $arguments));
    case 'database':
    default:
      $plural = $this->plural(Locale::$current->plural, $n);
      $search = array(
        'locale' => Locale::$current,
        'domain' => $this->domain,
        'msgctxt' => $msgctxt,
        'msgid' => $msgid,
        'msgid_plural' => $msgid_plural
      );
      return Model::first($search)->then(null, function () use ($search) {
        $message = new Model(array(
          'locale' => Locale::$default,
          'domain' => $search['domain'],
          'msgctxt' => $search['msgctxt'],
          'msgid' => $search['msgid'],
          'msgid_plural' => $search['msgid_plural'],
          'msgstr' => array($search['msgid'], $search['msgid_plural'])
        ));
        return $message->store();
      })->then(function ($message) use ($arguments, $plural) {
        return vsprintf($message->msgstr[$plural], $arguments);
      });
    }
  }

  protected function plural($plural, $n) {
    $parser = new PluralForms();
    $callback = $parser->compile($plural);
    return $callback($n);
  }
}
