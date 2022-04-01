<?php
/*
 * Created on Fri Apr 01 2022
 *
 * Copyright (c) 2022 Christian Backus (Chrissileinus)
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Chrissileinus\Config;

trait arrayAccessReadOnly
{
  protected static $storage = [];

  public function offsetExists($offset): bool
  {
    return isset(self::$storage[$offset]);
  }

  public function offsetSet($offset, $value): void
  {
  }

  public function offsetGet($offset): mixed
  {
    return self::$storage[$offset];
  }

  public function offsetUnset($offset): void
  {
  }
}
