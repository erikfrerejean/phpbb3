<?php
/**
*
* @package phpBB3
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* The class loader resolves class names to file system paths and loads them if
* necessary.
*
* Classes have to be of the form phpbb_(dir_)*(classpart_)*, so directory names
* must never contain underscores. Example: phpbb_dir_subdir_class_name is a
* valid class name, while phpbb_dir_sub_dir_class_name is not.
*
* If every part of the class name is a directory, the last directory name is
* also used as the filename, e.g. phpbb_dir would resolve to dir/dir.php.
*
* @package phpBB3
*/
class phpbb_class_loader
{
	private $load_paths = array();
	private $php_ext;
	private $cache;
	private $cached_paths = array();
	private $class_match = '';

	/**
	* Creates a new phpbb_class_loader, which loads files with the given
	* file extension from the given phpbb root path.
	*
	* @param string $include_path The directory where will be looked for files to include
	* @param string $php_ext      The file extension for PHP files
	* @param string $class_prefix The required prefix for classes in this directory
	*/
	public function __construct($include_path, $php_ext = '.php', $cache = null, $class_prefix = 'phpbb')
	{
		$this->php_ext = $php_ext;

		$this->add_loader($include_path, $class_prefix);
		$this->set_cache($cache);
	}

	/**
	 * Add an additional class loader
	 * @param  string $include_path The directory where will be looked for files to include
	 * @param  string $class_prefix The required prefix for classes in this directory
	 * @return void
	 */
	public function add_loader($include_path, $class_prefix)
	{
		if (!empty($this->load_paths[$class_prefix]))
		{
			return;
		}

		$include_path = (substr($include_path, -1) == '/') ? $include_path : $include_path . '/';
		$this->load_paths[$class_prefix] = $include_path;

		// Update the regex
		$this->class_match = '/(' . implode('|', array_keys($this->load_paths)) . ')_[a-zA-Z0-9_]+/';
	}

	/**
	* Provide the class loader with a cache to store paths. If set to null, the
	* the class loader will resolve paths by checking for the existance of every
	* directory in the class name every time.
	*
	* @param acm $cache An implementation of the phpBB cache interface.
	*/
	public function set_cache($cache = null)
	{
		if ($cache)
		{
			$this->cached_paths = $cache->get('class_loader');

			if ($this->cached_paths === false)
			{
				$this->cached_paths = array();
			}
		}

		$this->cache = $cache;
	}

	/**
	* Registers the class loader as an autoloader using SPL.
	*/
	public function register()
	{
		spl_autoload_register(array($this, 'load_class'));
	}

	/**
	* Removes the class loader from the SPL autoloader stack.
	*/
	public function unregister()
	{
		spl_autoload_unregister(array($this, 'load_class'));
	}

	/**
	* Resolves a class name to a relative path which can be included.
	*
	* @param string       $class  The class name to resolve
	* @param string       $prefix The class prefix
	* @return string|bool         A relative path to the file containing the
	*                             class or false if looking it up failed.
	*/
	public function resolve_path($class, $prefix)
	{
		$path_prefix = $this->load_paths[$prefix];

		if (isset($this->cached_paths[$class]))
		{
			return $path_prefix . $this->cached_paths[$class] . $this->php_ext;
		}

		if (!preg_match($this->class_match, $class))
		{
			return false;
		}

		$parts = explode('_', $class);

		$dirs = '';

		for ($i = 1, $n = sizeof($parts); $i < $n && is_dir($path_prefix . $dirs . $parts[$i]); $i++)
		{
			$dirs .= $parts[$i] . '/';
		}

		// no file name left => use last dir name as file name
		if ($i == sizeof($parts))
		{
			$parts[] = $parts[$i - 1];
		}

		$relative_path = $dirs . implode(array_slice($parts, $i, sizeof($parts) - $i), '_');

		if (!file_exists($path_prefix . $relative_path . $this->php_ext))
		{
			return false;
		}

		if ($this->cache)
		{
			$this->cached_paths[$class] = $relative_path;
			$this->cache->put('class_loader', $this->cached_paths);
		}

		return $path_prefix . $relative_path . $this->php_ext;
	}

	/**
	* Resolves a class name to a path and then includes it.
	*
	* @param string $class The class name which is being loaded.
	*/
	public function load_class($class)
	{
		// Check whether this class can be auto loaded
		$prefix = substr($class, 0, strpos($class, '_'));
		if (!empty($this->load_paths[$prefix]))
		{
			$path = $this->resolve_path($class, $prefix);

			if ($path)
			{
				require $path;
			}
		}
	}
}
