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

namespace Bread\L10n;

use Bread;
use Bread\Configuration\Manager as CM;
use Bread\L10n\Locale\Controller as Locale;

abstract class Localized extends Bread\Model {
  public function __set($attribute, $value) {
    $tags = explode(';', $attribute);
    $attribute = array_shift($tags);
    if ($tags) {
      foreach ($tags as $tag) {
        list($k, $v) = explode('-', $tag, 1);
        switch ($k) {
          case 'lang':

        }
      }
    }
  }

  public function __get($attribute) {
  }
}
