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
use Bread\L10n\Localized;

class Model extends Bread\Model {
  protected $locale;
  protected $domain;
  protected $msgctxt;
  protected $msgid;
  protected $msgid_plural;
  protected $msgstr = array();

  public static $key = array(
    'locale', 'domain', 'msgctxt', 'msgid'
  );
  
  protected static $attributes = array(
    'locale' => array(
      'type' => 'Bread\L10n\Locale\Model'
    ),
    'msgstr' => array(
      'multiple' => true
    )
  );
  
  public function __toString() {
    return (string) current($this->msgstr);
  }
}
