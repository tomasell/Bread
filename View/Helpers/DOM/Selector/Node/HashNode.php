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
 * HashNode represents a "selector#id" node.
 *
 * This component is a port of the Python lxml library,
 * which is copyright Infrae and distributed under the BSD license.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class HashNode implements Interfaces\Node {
  protected $selector;
  protected $id;

  /**
   * Constructor.
   *
   * @param NodeInterface $selector The NodeInterface object
   * @param string        $id       The ID
   */
  public function __construct($selector, $id) {
    $this->selector = $selector;
    $this->id = $id;
  }

  /**
   * {@inheritDoc}
   */
  public function __toString() {
    return sprintf('%s[%s#%s]', __CLASS__, $this->selector, $this->id);
  }

  /**
   * {@inheritDoc}
   */
  public function toXpath() {
    $path = $this->selector->toXpath();
    $path->addCondition(sprintf('@id = %s', XPathExpr::xpathLiteral($this->id)));

    return $path;
  }
}
