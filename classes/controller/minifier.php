<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Minifier extends Controller
{
  public function action_css()
  {
    $this->response->headers('Content-Type', 'text/css; charset=utf-8');

    $files = $this->get_file_list('css');

    // get_cache will check if the cache is stale or not.
    $cached = Minifier::get_cache('css', $files);
    if ( ! empty($cached))
    {
      $this->response->body($cached);
      return;
    }

    $output = '';
    $sass_parser = NULL;
    foreach ($files as $file)
    {
      $extension = pathinfo($file, PATHINFO_EXTENSION);
      if (in_array($extension, array('sass', 'scss')))
      {
        if ($sass_parser === NULL)
        {
          require_once dirname(__FILE__).'/../../vendor/phpsass/SassParser.php';
          $options = array(
            'style' => 'expanded',
            'cache' => FALSE,
            'syntax' => $extension,
            'debug' => FALSE,
            'callbacks' => array(),
          );

          $parser = new SassParser($options);
        }

        try
        {
          $output .= $parser->toCss($file);
        }
        catch (Exception $e)
        {
          Kohana::$log->add(Log::ERROR, Kohana_Exception::text($e))->write();
        }
      }
      else if ($extension == 'less')
      {
        require_once Kohana::find_file('vendor', 'lessphp/lessc.inc');
        $less = new lessc;
        $output .= $less->compileFile($file);
      }
      else
      {
        $output .= file_get_contents($file);
      }
    }

    require_once dirname(__FILE__).'/../../vendor/cssmin/cssmin.php';
    $cssmin = new CSSmin();
    $output = $cssmin->run($output);

    // Cache this (new) version
    Minifier::set_cache('css', $files, $output);

    $this->response->body($output);
  }

  public function action_js()
  {
    $this->response->headers('Content-Type', 'text/javascript; charset=utf-8');

    $files = $this->get_file_list('js');

    // get_cache will check if the cache is stale or not.
    $cached = Minifier::get_cache('js', $files);
    if ( ! empty($cached))
    {
      $this->response->body($cached);
      return;
    }

    $output = '';
    foreach ($files as $file)
      $output .= file_get_contents($file);

    require_once dirname(__FILE__).'/../../vendor/jsmin/jsmin.php';
    $output = JSMin::minify($output);

    // Cache this (new) version
    Minifier::set_cache('js', $files, $output);

    $this->response->body($output);
  }

  private function get_file_list($type)
  {
    $base_path = $this->get_base_path($type);
    $base_path_len = strlen($base_path);
    $raw_files = preg_split('/,/', $this->request->param('files', ''), -1, PREG_SPLIT_NO_EMPTY);

    $files = array();
    foreach ($raw_files as $file)
    {
      if (substr($file, 0, 1) != '/')
        $file = realpath($base_path . $file);
      else
        $file = realpath($file);

      // Make sure file is in the base path and is readable
      if ( ! strncmp($file, $base_path, $base_path_len) AND is_readable($file))
        $files[] = $file;
    }

    return $files;
  }

  private function get_base_path($type)
  {
    $config = Kohana::$config->load('minifier');
    $base_path = $config->get($type.'_base_path');

    if (substr($base_path, -1) != DIRECTORY_SEPARATOR)
      $base_path .= DIRECTORY_SEPARATOR;

    return $base_path;
  }
}
