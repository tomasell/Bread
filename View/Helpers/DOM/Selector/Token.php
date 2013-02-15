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
 * Token represents a CSS Selector token.
 *
 * This component is a port of the Python lxml library,
 * which is copyright Infrae and distributed under the BSD license.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Token
{
    private $type;
    private $value;
    private $position;

    /**
     * Constructor.
     *
     * @param string  $type     The type of this token.
     * @param mixed   $value    The value of this token.
     * @param integer $position The order of this token.
     */
    public function __construct($type, $value, $position)
    {
        $this->type = $type;
        $this->value = $value;
        $this->position = $position;
    }

    /**
     * Gets a string representation of this token.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }

    /**
     * Answers whether this token's type equals to $type.
     *
     * @param string $type The type to test against this token's one.
     *
     * @return Boolean
     */
    public function isType($type)
    {
        return $this->type == $type;
    }

    /**
     * Gets the position of this token.
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }
}
