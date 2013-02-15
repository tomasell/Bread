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

namespace Bread\View\Helpers\DOM\Selector\Node;

use Bread\View\Helpers\DOM\Selector\Interfaces;
use Bread\View\Helpers\DOM\Selector\XPathExpr;

/**
 * ElementNode represents a "namespace|element" node.
 *
 * This component is a port of the Python lxml library,
 * which is copyright Infrae and distributed under the BSD license.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ElementNode implements Interfaces\Node {
  protected $namespace;
  protected $element;

  /**
   * Constructor.
   *
   * @param string $namespace Namespace
   * @param string $element   Element
   */
  public function __construct($namespace, $element) {
    $this->namespace = $namespace;
    $this->element = $element;
  }

  /**
   * {@inheritDoc}
   */
  public function __toString() {
    return sprintf('%s[%s]', __CLASS__, $this->formatElement());
  }

  /**
   * Formats the element into a string.
   *
   * @return string Element as an XPath string
   */
  public function formatElement() {
    if ($this->namespace == '*') {
      return $this->element;
    }

    return sprintf('%s|%s', $this->namespace, $this->element);
  }

  /**
   * {@inheritDoc}
   */
  public function toXpath() {
    if ($this->namespace == '*') {
      $el = strtolower($this->element);
    }
    else {
      // FIXME: Should we lowercase here?
      $el = sprintf('%s:%s', $this->namespace, $this->element);
    }

    return new XPathExpr(null, null, $el);
  }
}
