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

namespace Bread\Stream\Interfaces;

interface Wrapper {
  public function stream_cast($as);

  public function stream_close();

  public function stream_eof();

  public function stream_flush();

  public function stream_lock($operation);

  public function stream_metadata($path, $option, $var);

  public function stream_open($path, $mode, $options, &$opened_path);

  public function stream_read($count);

  public function stream_seek($offset, $whence = SEEK_SET);

  public function stream_set_option($option, $arg1, $arg2);

  public function stream_stat();

  public function stream_tell();

  public function stream_truncate($new_size);

  public function stream_write($data);
}
