<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Minifier extends Controller {
	
	public function action_css()
	{
		$this->response->headers('Content-Type', 'text/css; charset=utf-8');

		// Fetch the data from the cache file
		$data = $this->get_data('css');
		if (empty($data))
		{
			return;
		}

		// Stale cache?
		if ($this->stale_cache('css', $data))
		{
			$output = '';
			$files = array_keys($data['files']);
			$data['extra_files'] = array(); // Reset to avoid stale files via @import etc
			foreach ($files as $file)
			{
				$extension = pathinfo($file, PATHINFO_EXTENSION);
				if (in_array($extension, array('sass', 'scss')))
				{
					require_once Kohana::find_file('vendor', 'phpsass/SassParser');
					$options = array(
						'style' => 'expanded',
						'cache' => FALSE,
						'syntax' => $extension,
						'debug' => FALSE,
						'callbacks' => array(),
					);
					$parser = new SassParser($options);
					try
					{
						$output .= $parser->toCss($file);
						$data['extra_files'] += $parser->getParsedFiles();
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
					try
					{
						$output .= $less->compileFile($file);

						// Get the parsed files and remove the file that was requested because we track that already.
						$less_files = $less->allParsedFiles();
						unset($less_files[$file]);

						$data['extra_files'] += $less_files;
					}
					catch (Exception $e)
					{
						Kohana::$log->add(Log::ERROR, Kohana_Exception::text($e))->write();
					}
				}
				else
				{
					$output .= file_get_contents($file);
				}
			}

			// Minifier the CSS
			require_once Kohana::find_file('vendor', 'cssmin/cssmin');
			$cssmin = new CSSmin;
			$output = $cssmin->run($output);

			// Cache this (new) version, update the actual files too!

			$data['cache'] = $output;
			$data['cache_gz'] = gzencode($output);

			Minifier::set_cache('css', $data);
		}

		// This will set the body appropriately - GZip'd if possible.
		$this->output_data($data);
	} // function action_css


	public function action_js()
	{
		$this->response->headers('Content-Type', 'text/javascript; charset=utf-8');

		$data = $this->get_data('js');
		if (empty($data))
		{
			return;
		}

		// Stale cache?
		if ($this->stale_cache('js', $data))
		{
			$output = '';
			$files = array_keys($data['files']);
			foreach ($files as $file)
			{
				// Add a ; so files end properly.
				$output .= ';'.file_get_contents($file);
			}

			require_once Kohana::find_file('vendor', 'jsmin/jsmin');
			$output = JSMin::minify($output);

			// Cache this (new) version

			$data['cache'] = $output;
			$data['cache_gz'] = gzencode($output);

			Minifier::set_cache('js', $data);
		}

		// This will set the body appropriately - GZip'd if possible.
		$this->output_data($data);
	} // function action_js
	

	protected function get_data($type)
	{
		$config = Kohana::$config->load('minifier');
		$directory = $config->get($type.'_cache_path');

		$file_key = $this->request->param('file_hash', '');
		if (empty($file_key) || ! is_readable($directory.DIRECTORY_SEPARATOR.$file_key))
		{
			return FALSE;
		}

		// echo Debug::vars($directory.DIRECTORY_SEPARATOR.$file_key);exit;
		$data = unserialize(file_get_contents($directory.DIRECTORY_SEPARATOR.$file_key));

		// Ensure the file_hash is present in the data as thats the cahe filename - incase the files list changes
		$data['file_hash'] = $this->request->param('file_hash', '');

		return $data;
	} // function get_data
	

	protected function files_modified(&$files)
	{
		$modified = FALSE;

		// Loop through all the files regardless as it allows
		// $files to be populated with up to date mtimes
		foreach ($files as $file => &$mtime)
		{
			$current_mtime = @filemtime($file);
			if (($current_mtime > $mtime) || ($current_mtime === FALSE))
			{
				$mtime = $current_mtime;
				$modified = TRUE;
			}
		}

		return $modified;
	} // function files_modified


	protected function stale_data(&$data)
	{
		$modified = FALSE;

		if ($this->files_modified($data['files'], 'base'))
		{
			$modified = TRUE;
		}

		if ( ! empty($data['extra_files']) && $this->files_modified($data['extra_files'], 'extra'))
		{
			$modified = TRUE;
		}

		return $modified;
	} // function stale_data


	protected function stale_cache($type, &$data)
	{
		$config = Kohana::$config->load('minifier');
		$mode = $config->get($type.'_cache_mode');

		switch ($mode)
		{
			// If no caching, don't bother checking the files
			case Minifier::CACHE_NONE:
			{
				return TRUE;
			}

			// If cache is auto, it depends on the files being modified (or stale data)
			case Minifier::CACHE_AUTO:
			{
				return $this->stale_data($data);
			}

			// If cache is perm, then we're only stale if the cache key doesn't exist
			case Minifier::CACHE_PERM:
			{
				return empty($data['cache']);
			}
		}
	} // function stale_cache


	protected function output_data($data)
	{
		// E-Tag header
		$etag_hash = md5($data['cache']);
		$this->response->headers('ETag', '"'.$etag_hash.'"');

		// Client sending us an ETag?
		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && ($_SERVER['HTTP_IF_NONE_MATCH'] == '"'.$etag_hash.'"'))
		{
			$this->response->status(304);
			$this->response->headers('Content-Length', '0');
		}
		else
		{
			// Default to not gzipping output
			$gzip = FALSE;

			// Last modified header
			$this->response->headers('Cache-Control', 'must-revalidate');
			$this->response->headers('Last-modified', gmdate('r', max($data['files'])));

			// Check for GZip support
			$accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
			if (strpos($accept_encoding, 'x-gzip') !== FALSE)
			{
				$gzip = 'x-gzip';
			}
			else if (strpos($accept_encoding, 'gzip') !== FALSE)
			{
				$gzip = 'gzip';
			}

			// Not gzipping?
			if ($gzip === FALSE)
			{
				$this->response->body($data['cache']);
			}
			else
			{
				$this->response->headers('Content-Encoding', $gzip);
				$this->response->headers('Content-Length', (string) strlen($data['cache_gz']));
				$this->response->body($data['cache_gz']);
			}
		}
	} // function output_data


} // class Controller_Minifier