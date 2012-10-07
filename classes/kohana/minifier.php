<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Minifier
{
  private static $_data = array(
    'css' => array(),
    'js' => array(),
  );

  public static function add_css($css, $section = '*')
  {
    self::_add_data('css', $css, $section);
  }

  public static function add_js($js, $section = '*')
  {
    self::_add_data('js', $js, $section);
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

  public static function get_css($sections = NULL)
  {
    return self::_get_type('css', 'style', $sections);
  }

  public static function get_js($sections = NULL)
  {
    return self::_get_type('js', 'script', $sections);
  }

  private static function _get_type($type, $method, $sections = NULL)
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
        $url = $type.'/'.implode(',', self::$_data[$type][$section]);
        $output .= HTML::$method($url);
      }
    }

    return $output;
  }

  private static function get_cache_filename($type, $files)
  {
    $config = Kohana::$config->load('minifier');
    return $config->get($type.'_cache_path').DIRECTORY_SEPARATOR.md5(serialize($files)).'.cache';
  }

  private static function get_cache_files_filename($type, $files)
  {
    return self::get_cache_filename($type, $files).'-files';
  }

  public static function get_cache($type, $files)
  {
    // First just check if the cached file exists
    $cache_filename = self::get_cache_filename($type, $files);
    if ( ! is_readable($cache_filename))
    {
      return FALSE;
    }

    // The $files array contains files that exist and are readable so we can just check filemtime easily
    $cache_mtime = filemtime($cache_filename);
    foreach ($files as $file)
    {
      // No need to check existance, $files array must only contain files that exist.
      if (filemtime($file) > $cache_mtime)
      {
        return FALSE;
      }
    }

    // Check the cache files file too, this will contain any extra files used to generate the cache, eg via @import.
    $cache_files_filename = self::get_cache_files_filename($type, $files);
    if (is_readable($cache_files_filename))
    {
      // Below included file will populate $cache_files
      $cache_files = array();
      require_once $cache_files_filename;

      // We check existance here because if the file no longer exists the imported content will be different too.
      foreach ($cache_files as $cache_file)
      {
        if ( ! is_readable($cache_file) OR filemtime($cache_file) > $cache_mtime)
        {
          return FALSE;
        }
      }
    }

    // If we get to here, then the cache exists and nothing in it is newer
    return file_get_contents($cache_filename);
  }

  public static function set_cache($type, $files, $data, $extra_files = array())
  {
    $cache_filename = self::get_cache_filename($type, $files);

    // Will be set to false if we have issues so the cache write isn't attempted.
    $save_cache = TRUE;

    // Attempt to make sure the cache dir exists
    $cache_dir = dirname($cache_filename);
    if ( ! is_dir($cache_dir))
    {
      $save_cache = mkdir($cache_dir);
    }

    // Save the extra files used to generate the cache
    if ( ! empty($extra_files))
    {
      $cache_files_filename = self::get_cache_files_filename($type, $files);
      $save_cache = is_int(file_put_contents($cache_files_filename, '<?php $cache_files = '.var_export($extra_files, TRUE).';'));
    }

    // Actually save the cache
    return $save_cache AND is_int(file_put_contents($cache_filename, $data));
  }
}
