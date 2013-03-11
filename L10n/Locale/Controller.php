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
use Bread\Configuration\Manager as CM;
use Bread\L10n\Message;
use Locale;

class Controller extends Bread\Controller {
  public static $default;
  public static $current;

  protected static $categories = array(
    LC_CTYPE => 'LC_CTYPE',
    LC_NUMERIC => 'LC_NUMERIC',
    LC_TIME => 'LC_TIME',
    LC_COLLATE => 'LC_COLLATE',
    LC_MONETARY => 'LC_MONETARY',
    LC_MESSAGES => 'LC_MESSAGES',
    LC_ALL => 'LC_ALL'
  );

  public function __construct($request, $response) {
    parent::__construct($request, $response);
    Model::fetch()->then(function ($locales) {
      $locales = array_map(function ($locale) {
        return $locale->lang;
      }, (array) $locales);
      $accept = Locale::acceptFromHttp($this->request->headers['Accept-Language']);
      $default = Locale::lookup($locales, $accept, false, static::get('default.lang'));
      Locale::setDefault($default);
    });
  }

  public static function configure($configuration = array()) {
    if (!isset(static::$default)) {
      Model::first(static::get('default'))->then(function ($default) {
        static::$default = $default;
      }, function () {
        static::$default = new Model(static::get('default'));
      });
    }
    static::set(static::$default);
  }

  public static function set($locale, $category = LC_ALL) {
    $categoryString = static::$categories[$category];
    putenv("{$categoryString}={$locale->lang}");
    setlocale($category, $locale->lang);
    static::$current = $locale;
  }
}

CM::defaults('Bread\L10n\Locale\Controller', array(
  'default' => array('lang' => 'en-us')
));
