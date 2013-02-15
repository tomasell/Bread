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
 * OrNode represents a "Or" node.
 *
 * This component is a port of the Python lxml library,
 * which is copyright Infrae and distributed under the BSD license.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class OrNode implements Interfaces\Node {
  /**
   * @var NodeInterface[]
   */
  protected $items;

  /**
   * Constructor.
   *
   * @param NodeInterface[] $items An array of NodeInterface objects
   */
  public function __construct($items) {
    $this->items = $items;
  }

  /**
   * {@inheritDoc}
   */
  public function __toString() {
    return sprintf('%s(%s)', __CLASS__, $this->items);
  }

  /**
   * {@inheritDoc}
   */
  public function toXpath() {
    $paths = array();
    foreach ($this->items as $item) {
      $paths[] = $item->toXpath();
    }

    return new XPathExprOr($paths);
  }
}
