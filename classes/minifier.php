<?php defined('SYSPATH') or die('No direct script access.');

class Minifier {
  private static $_data = array(
    'css' => array(),
    'js' => array(),
  );

  public static function add_css($css)
  {
    self::_add_data('css', $css);
  }

  public static function add_js($js)
  {
    self::_add_data('js', $js);
  }

  private static function _add_data($type, $data)
  {
    if ( ! array_key_exists($type, self::$_data))
      return FALSE;

    if ( ! is_array($data))
      $data = array($data);

    foreach ($data as $datum)
      self::$_data[$type][$datum] = $datum;

    return TRUE;
  }

  public static function get_css()
  {
    $url = self::_get_url('css');
    return empty($url) ? '' : HTML::style($url);
  }

  public static function get_js()
  {
    $url = self::_get_url('js');
    return empty($url) ? '' : HTML::script($url);
  }

  private static function _get_url($type)
  {
    if ( ! empty(self::$_data[$type]))
      return URL::site($type.'/'.implode(',', self::$_data[$type]), NULL, FALSE);
  }
}
