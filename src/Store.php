<?php

namespace Chrissileinus\Config;

class Store implements \ArrayAccess, \Serializable, \JsonSerializable, \IteratorAggregate, \Traversable
{
  protected static $current = [];

  /**
   * set
   *
   * @param  mixed $args
   * @return void
   */
  public static function set(...$args)
  {
    foreach ($args as $arg) {
      if (is_array($arg) || is_object($arg)) {
        self::$current = array_replace_recursive(self::$current, (array) $arg);
        continue;
      }

      if ($files = glob($arg)) {
        foreach ($files as $file) {
          if ($content = file_get_contents($file)) {
            if ($value = \yaml_parse($content, 0, null, [
              '!php' => function ($value, $tag, $flags) {
                [$class, $const] = explode('::', $value);
                return $class::getConstant($const);
              }
            ])) {
              self::$current = array_replace_recursive(self::$current, (array) $value);
              continue;
            }
          }

          if ($value = json_decode($content, false, 512, JSON_OBJECT_AS_ARRAY)) {
            self::$current = array_replace_recursive(self::$current, (array) $value);
            continue;
          }

          if ($value = unserialize($content, false) && (is_array($value) || is_object($value))) {
            self::$current = array_replace_recursive(self::$current, (array) $value);
            continue;
          }
        }
      }
    }
  }


  public static function get()
  {
    return (object) self::$current;
  }

  /** ArrayAccess */
  public function offsetExists($offset): bool
  {
    return isset(self::$current[$offset]);
  }

  public function offsetSet($offset, $value)
  {
  }

  public function offsetGet($offset): mixed
  {
    return self::$current[$offset];
  }

  public function offsetUnset($offset)
  {
  }

  /** Serializable  */
  public function serialize(): string
  {
    return serialize(self::$current);
  }

  public function unserialize($data)
  {
    self::$current = unserialize($data);
  }

  /** JsonSerializable */
  public function jsonSerialize()
  {
    return self::$current;
  }

  /** */
  public function getIterator()
  {
    return new \ArrayIterator(self::$current);
  }
}
