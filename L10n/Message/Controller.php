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

class Controller extends Bread\Controller {
  const DEFAULT_DOMAIN = 'default';

  public function localize($domain, $message) {
    $arguments = func_get_args();
    $domain = array_shift($arguments);
    $argument = array_shift($args);
    if (is_string($argument)) {
      $attributes = array(
        'msgid' => $argument, 'locale' => Locale::$current
      );
      if (!$message = Model::first($attributes)) {
        $message = new Model($attributes);
        $message->msgstr = $argument;
        $message->save();
      }
      return vsprintf($message->msgstr, $arguments);
    }
  }

  public function t($message) {
    $arguments = func_get_args();
    array_unshift($arguments, self::DEFAULT_DOMAIN);
    return call_user_func_array(array(
      $this, 'localize'
    ), $arguments);
  }

  public function dt($domain, $message) {
    return call_user_func_array(array(
      $this, 'localize'
    ), func_get_args());
  }
}
