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

namespace Bread\L10n\Locale;

use Bread;
use Bread\L10n\Message;

class Controller extends Bread\Controller {
  public static $default;
  public static $current;

  protected static $configuration = array(
    'default' => array(
      'code' => 'en_US.UTF-8'
    )
  );

  public static function configure($configuration = array()) {
    if (static::configured()) {
      return static::configuration();
    }
    parent::configure($configuration);
    if (!isset(static::$default)) {
      Model::first(static::get('default'))->then(function ($default) {
        static::$default = $default ? : new Model(static::get('default'));
      });
    }
    if (!isset(static::$current)) {
      static::$current = static::$default;
    }
    setlocale(LC_ALL, static::$current->code);
  }
}
