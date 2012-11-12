<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Minifier {

  /* Nothing is cached, files will be regenerated all the time */
  const CACHE_NONE = 0;

  /* Auto caching, useful for dev env - files will be regenerated when needed */
  const CACHE_AUTO = 1;

  /* Permanent caching - for production - files will never be regenerated */
  const CACHE_PERM = 2;

  private static $_data = array(
    'css' => array('*' => array()),
    'js' => array('*' => array()),
  );

  public static function add_css($css, $section = '*')
  {
    self::_add_data('css', $css, $section);
  }

  public static function add_js($js, $section = '*')
  {
    self::_add_data('js', $js, $section);
  }

  public static function get_css($sections = NULL, $query = array(), $attributes = array(), $clear = TRUE)
  {
    return self::_get_type('css', 'style', $sections, $query, $attributes, $clear);
  }

  public static function get_js($sections = NULL, $query = array(), $attributes = array(), $clear = TRUE)
  {
    return self::_get_type('js', 'script', $sections, $query, $attributes, $clear);
  }

  private static function _add_data($type, $data, $section = '*')
  {
    if ( ! array_key_exists($type, self::$_data))
    {
      return FALSE;
    }

    // Ensure the section exists
    if ( ! array_key_exists($section, self::$_data[$type]))
    {
      self::$_data[$type][$section] = array();
    }

    if ( ! is_array($data))
    {
      $data = array($data);
    }

    foreach ($data as $datum)
    {
      self::$_data[$type][$section][$datum] = $datum;
    }

    return TRUE;
  }

  private static function _get_type($type, $method, $sections = NULL, $query = array(), $attributes = array(), $clear = TRUE)
  {
    if ($sections === NULL)
    {
      $sections = array_keys(self::$_data[$type]);
    }
    else if (is_string($sections))
    {
      $sections = array($sections);
    }

    $output = '';
    foreach ($sections as $section)
    {
      if ( ! empty(self::$_data[$type][$section]))
      {
        $file_list = self::get_file_list($type, $section);
        $cache_file = self::get_cache_filename($type, $file_list);

        // New cache file? Prime the filemtime values with 0s to force rebuild
        if ( ! file_exists($cache_file))
        {
          $fake_mtimes = array_fill_keys($file_list, 0);
          $cache_data = serialize(array('files' => $fake_mtimes));
          file_put_contents($cache_file, $cache_data);
        }

        // Add the URL to output
        $url = $type.'/'.basename($cache_file).'.'.$type;

        // Query string?
        if ( ! empty($query))
        {
          $url .= '?'.http_build_query($query);
        }

        $output .= HTML::$method($url, $attributes);
      }
    }

    // Clear the requested sections to allow for get_css() / get_js() to catch any remaining sections
    if ($clear === TRUE)
    {
      self::$_data[$type] = array_diff_key(self::$_data[$type], array_fill_keys($sections, TRUE));
    }

    return $output;
  }

  private static function get_cache_location($type)
  {
    $config = Kohana::$config->load('minifier');
    $directory = $config->get($type.'_cache_path');
    if ( ! is_dir($directory))
    {
      mkdir($directory);
    }

    return $directory;
  }

  private static function get_cache_filename($type, $files)
  {
    $directory = self::get_cache_location($type);
    return $directory.DIRECTORY_SEPARATOR.md5(implode(',', $files));
  }

  public static function set_cache($type, $data)
  {
    if (empty($data['file_hash']))
    {
      $cache_filename = self::get_cache_filename($type, array_keys($data['files']));
    }
    else
    {
      $cache_filename = self::get_cache_location($type).DIRECTORY_SEPARATOR.$data['file_hash'];
    }
    $cache_data = serialize($data);

    $bytes = file_put_contents($cache_filename, $cache_data);

    return is_int($bytes);
  }

  private static function get_file_list($type, $section)
  {
    if ( ! isset(self::$_data[$type][$section]))
    {
      return array();
    }

    $raw_files = self::$_data[$type][$section];
    $files = array();
    foreach ($raw_files as $file)
    {
      $base_path = self::get_base_path($type, $file);
      $base_path_len = strlen($base_path);

      if (substr($file, 0, 1) != '/')
      {
        $file = realpath($base_path . $file);
      }
      else
      {
        $file = realpath($file);
      }

      // Make sure file is in the base path and is readable
      if ( ! strncmp($file, $base_path, $base_path_len) AND is_readable($file))
      {
        $files[] = $file;
      }
    }

    return $files;
  }

  private static function get_base_path($type, $file)
  {
    $config = Kohana::$config->load('minifier');

    // We allow the extension to determine the base path too!
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    if (($base_path = $config->get($extension.'_base_path')) === NULL)
    {
      $base_path = $config->get($type.'_base_path');
    }

    if (substr($base_path, -1) != DIRECTORY_SEPARATOR)
    {
      $base_path .= DIRECTORY_SEPARATOR;
    }

    return $base_path;
  }
}
