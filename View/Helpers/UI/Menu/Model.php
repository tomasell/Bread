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

namespace Bread\View\Helpers\UI\Menu;

use Bread;
use Bread\Configuration\Manager as CM;

class Model extends Bread\Model {
  protected $name;
  protected $position;
  protected $title;
  protected $description;
  protected $href;
}

CM::defaults('Bread\View\Helpers\UI\Menu\Model', array(
  'keys' => array('name', 'position')
));
