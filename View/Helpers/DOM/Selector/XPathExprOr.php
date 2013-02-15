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

namespace Bread\View\Helpers\DOM\Selector;

/**
 * XPathExprOr represents XPath |'d expressions.
 *
 * Note that unfortunately it isn't the union, it's the sum, so duplicate elements will appear.
 *
 * This component is a port of the Python lxml library,
 * which is copyright Infrae and distributed under the BSD license.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class XPathExprOr extends XPathExpr {
  /**
   * Constructor.
   *
   * @param array  $items  The items in the expression.
   * @param string $prefix Optional prefix for the expression.
   */
  public function __construct($items, $prefix = null) {
    $this->items = $items;
    $this->prefix = $prefix;
  }

  /**
   * Gets a string representation of this |'d expression.
   *
   * @return string
   */
  public function __toString() {
    $prefix = $this->getPrefix();

    $tmp = array();
    foreach ($this->items as $i) {
      $tmp[] = sprintf('%s%s', $prefix, $i);
    }

    return implode($tmp, ' | ');
  }
}
