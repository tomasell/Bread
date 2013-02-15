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
 * ClassNode represents a "selector.className" node.
 *
 * This component is a port of the Python lxml library,
 * which is copyright Infrae and distributed under the BSD license.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ClassNode implements Interfaces\Node {
  protected $selector;
  protected $className;

  /**
   * The constructor.
   *
   * @param NodeInterface $selector  The XPath Selector
   * @param string        $className The class name
   */
  public function __construct($selector, $className) {
    $this->selector = $selector;
    $this->className = $className;
  }

  /**
   * {@inheritDoc}
   */
  public function __toString() {
    return sprintf('%s[%s.%s]', __CLASS__, $this->selector, $this->className);
  }

  /**
   * {@inheritDoc}
   */
  public function toXpath() {
    $selXpath = $this->selector->toXpath();
    $selXpath->addCondition(sprintf("contains(concat(' ', normalize-space(@class), ' '), %s)", XPathExpr::xpathLiteral(' '
      . $this->className . ' ')));

    return $selXpath;
  }
}
