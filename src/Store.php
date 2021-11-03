<?php
/*
 * Created on Wed Nov 03 2021
 *
 * Copyright (c) 2021 Christian Backus (Chrissileinus)
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Chrissileinus\Config;

class Store implements \ArrayAccess, \Serializable, \JsonSerializable, \IteratorAggregate, \Traversable
{
  protected static $storage = [];

  /**
   * set
   *
   * @param  mixed $args
   * Array or Abject will be merged in storage with array_replace_recursive($storage, $arg).
   *
   * A path to a file or a glob will be get read and parsed by yaml_parse, json_decode or unserialize. The result will also be merged into storage.
   *
   * A with yaml_parse, json_decode or unserialize parsable string will also be merged into storage.
   *
   *
   * @return void
   */
  public static function set(...$args)
  {
    foreach ($args as $arg) {
      if (is_array($arg) || is_object($arg)) {
        self::importArray((array) $arg);
        continue;
      }

      if (is_file($arg) && $content = file_get_contents($arg)) {
        self::importContent($content);
        continue;
      }

      if ($files = glob($arg)) {
        foreach ($files as $file) {
          if ($content = file_get_contents($file)) {
            self::importContent($content);
          }
        }
        continue;
      }

      if (is_string($arg)) {
        self::importContent($arg);
        continue;
      }
    }
  }

  /**
   * clear
   *
   * @return void
   */
  public static function clear()
  {
    self::$storage = [];
  }

  /**
   * importArray
   *
   * @param  mixed $array
   * @return void
   */
  private static function importArray(array $array)
  {
    self::$storage = array_replace_recursive(self::$storage, $array);
  }

  /**
   * importContent
   *
   * @param  mixed $content
   * @return void
   */
  private static function importContent(string $content)
  {
    if ($value = yaml_parse($content, 0, $_, [
      '!php' => function ($value, $tag, $flags) {
        [$class, $const] = \explode('::', $value);
        return $class::getConstant($const);
      }
    ])) {
      self::importArray((array) $value);
      return;
    }

    if ($value = json_decode($content, false, 512, JSON_OBJECT_AS_ARRAY)) {
      self::importArray((array) $value);
      return;
    }

    if ($value = unserialize($content) && (is_array($value) || is_object($value))) {
      self::importArray((array) $value);
      return;
    }
  }

  public static function get()
  {
    return (object) self::$storage;
  }

  /** ArrayAccess */
  public function offsetExists($offset): bool
  {
    return isset(self::$storage[$offset]);
  }

  public function offsetSet($offset, $value)
  {
  }

  public function offsetGet($offset): mixed
  {
    return self::$storage[$offset];
  }

  public function offsetUnset($offset)
  {
  }

  /** Serializable  */
  public function serialize(): string
  {
    return serialize(self::$storage);
  }

  public function unserialize($data)
  {
    self::$storage = unserialize($data);
  }

  /** JsonSerializable */
  public function jsonSerialize()
  {
    return self::$storage;
  }

  /** */
  public function getIterator()
  {
    return new \ArrayIterator(self::$storage);
  }
}
