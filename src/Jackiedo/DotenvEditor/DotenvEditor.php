<?php namespace Jackiedo\DotenvEditor;

use Illuminate\Container\Container;
use Jackiedo\DotenvEditor\Contracts\DotenvFormatter as DotenvFormatterContract;
use Jackiedo\DotenvEditor\Exceptions\FileNotFoundException;
use Jackiedo\DotenvEditor\Exceptions\KeyNotFoundException;
use Jackiedo\DotenvEditor\Exceptions\NoBackupAvailableException;

/**
 * The DotenvEditor class.
 *
 * @package Jackiedo\DotenvEditor
 * @author Jackie Do <anhvudo@gmail.com>
 */
class DotenvEditor
{
    /**
     * The IoC Container
     *
     * @var \Illuminate\Container\Container
     */
    protected $app;

    /**
     * The formatter instance
     *
     * @var \Jackiedo\DotenvEditor\DotenvFormatter
     */
    protected $formatter;

    /**
     * The reader instance
     *
     * @var \Jackiedo\DotenvEditor\DotenvReader
     */
    protected $reader;

    /**
     * The writer instance
     *
     * @var \Jackiedo\DotenvEditor\DotenvWriter
     */
    protected $writer;

    /**
     * The .env file path
     *
     * @var string
     */
    protected $filePath;

    /**
     * The auto backup status
     *
     * @var bool
     */
    protected $autoBackup;

    /**
     * The .env file backup path
     *
     * @var string
     */
    protected $backupPath;

    /**
     * The backup filename prefix
     */
    const BACKUP_FILENAME_PREFIX = '.env.backup_';

    /**
     * The backup filename suffix, include file extension
     */
    const BACKUP_FILENAME_SUFFIX = '';

    /**
     * Create a new DotenvEditor instance
     *
     * @param \Illuminate\Container\Container					$app
     * @param \Jackiedo\DotenvEditor\Contracts\DotenvFormatter	$formatter
     */
    public function __construct(Container $app, DotenvFormatterContract $formatter) {
		$this->app       = $app;
		$this->formatter = $formatter;
		$this->reader    = new DotenvReader($this->formatter);
		$this->writer    = new DotenvWriter($this->formatter);

    	$backupPath = $this->app['config']->get('dotenv-editor.backupPath', base_path('storage/dotenv-editor/backups/'));
        if(!is_dir($backupPath)){
            mkdir($backupPath, 0777, true);
            copy(__DIR__ . '/../../stubs/gitignore.txt', $backupPath . '../.gitignore');
        }
        $this->backupPath = $backupPath;
        $this->autoBackup = $this->app['config']->get('dotenv-editor.autoBackup', true);
    }

	/**
	 * Load the .env file
	 *
	 * @param  string|null	$filepath		The .env file path
	 * @param  boolean		$forceRestore	Restore the .env file from other file if not exists
	 * @param  string|null	$restorePath	The file path you want to restore from
	 *
	 * @throws \Jackiedo\DotenvEditor\Exceptions\FileNotFoundException
	 *
	 * @return DotenvEditor
	 */
	public function load($filepath = null, $forceRestore = false, $restorePath = null) {
		$this->filePath = is_null($filepath) ? $this->app->environmentFilePath() : $filepath;
		$this->loadReader($this->filePath);

		if (file_exists($this->filePath)) {
			$this->setBuffer($this->getContent());
			return $this;
		} else {
			if (!$forceRestore) {
				throw new FileNotFoundException("File does not exist at path {$this->filePath}");
			}
			return $this->restore($restorePath);
		}
	}

	/*
    |--------------------------------------------------------------------------
    | Working with reading
    |--------------------------------------------------------------------------
    |
    | loadReader($filepath)
    | getContent($content)
    | getLines()
    | getKeys()
    | keyExists($key)
    | getValue($key)
    |
    */

	/**
	 * Load the .env file into reader
	 *
	 * @param  string $filepath
	 *
	 * @return DotenvEditor
	 */
	protected function loadReader($filepath) {
		$this->reader->load($filepath);
		return $this;
	}

	/**
	 * Get raw content of .env file
	 *
	 * @return string
	 */
	public function getContent() {
		return $this->reader->content();
	}

	/**
	 * Get all lines from .env file
	 *
	 * @return array
	 */
	public function getLines() {
		return $this->reader->lines();
	}

	/**
	 * Get all or exists given keys in .env file
	 *
	 * @param  array  $keys
	 *
	 * @return array
	 */
	public function getKeys($keys = []) {
		$allKeys = $this->reader->keys();

		return array_filter($allKeys, function($key) use ($keys) {
			if (!empty($keys)) {
				return in_array($key, $keys);
			}
			return true;
		}, ARRAY_FILTER_USE_KEY);
	}

	/**
	 * Checks, if a given key exists in your .env file.
	 *
	 * @param  string  $keys
	 *
	 * @return bool
	 */
	public function keyExists($key) {
	    $allKeys = $this->getKeys();
	    if (array_key_exists($key, $allKeys)) {
	    	return true;
	    }
	    return false;
	}

    /**
     * Returns the value matching to a given key.
     *
     * @param $key
     *
     * @throws \Jackiedo\DotenvEditor\Exceptions\KeyNotFoundException
     *
     * @return string
     */
	public function getValue($key) {
		$allKeys = $this->getKeys([$key]);
	    if (array_key_exists($key, $allKeys)) {
	    	return $allKeys[$key]['value'];
	    }
	    throw new KeyNotFoundException('Requested key not found in your file.');
	}

    /*
    |--------------------------------------------------------------------------
    | Working with writing
    |--------------------------------------------------------------------------
    |
    | getBuffer()
    | setBuffer($content)
    | addEmpty()
    | addComment($comment)
    | setKeys($data)
    | setKey($key, $value = null, $comment = null, $export = false)
    | deleteKeys($keys = [])
    | deleteKey($key)
    | save()
    |
    */

	/**
	 * Return content in buffer of the .env file content
	 *
	 * @return string
	 */
	public function getBuffer() {
		return $this->writer->getBuffer();
	}

	/**
	 * Replace buffer of the .env file content with special content
	 *
	 * @param string $content
	 *
	 * @return DotenvEditor
	 */
	public function setBuffer($content) {
		$this->writer->setBuffer($content);
		return $this;
	}

	/**
	 * Add empty line to buffer of the .env file content
	 *
	 * @return DotenvEditor
	 */
	public function addEmpty() {
		$this->writer->appendEmptyLine();
		return $this;
	}

	/**
	 * Add comment line to buffer of the .env file content
	 *
	 * @param object
	 */
	public function addComment($comment) {
		$this->writer->appendCommentLine($comment);
		return $this;
	}

	/**
	 * Set many keys to buffer of the .env file
	 *
	 * @param array $data
	 *
	 * @return DotenvEditor
	 */
	public function setKeys($data) {
		foreach ($data as $setter) {
			if (array_key_exists('key', $setter)) {
				$key     = $this->formatter->formatKey($setter['key']);
				$value   = array_key_exists('value', $setter) ? $setter['value'] : null;
				$comment = array_key_exists('comment', $setter) ? $setter['comment'] : null;
				$export  = array_key_exists('export', $setter) ? $setter['export'] : false;

				if (!$this->keyExists($key)) {
					$this->writer->appendSetter($key, $value, $comment, $export);
				} else {
					$oldInfo = $this->getKeys([$key]);
					$comment = is_null($comment) ? $oldInfo[$key]['comment'] : $comment;
					$this->writer->updateSetter($key, $value, $comment, $export);
				}
			}
		}

		return $this;
	}

	/**
	 * Set one key to buffer of the .env file content
	 *
	 * @param string		$key
	 * @param string|null	$value
	 * @param string|null	$comment
	 * @param boolean		$export
	 *
	 * @return DotenvEditor
	 */
	public function setKey($key, $value = null, $comment = null, $export = false) {
		$data = [compact('key', 'value', 'comment', 'export')];

		return $this->setKeys($data);
	}

	/**
	 * Delete many keys in buffer of the .env file content
	 *
	 * @param  array $keys
	 *
	 * @return DotenvEditor
	 */
	public function deleteKeys($keys = []) {
		foreach ($keys as $key) {
			$this->writer->deleteSetter($key);
		}

		return $this;
	}

	/**
	 * Delete on key in buffer of the .env file content
	 *
	 * @param  string $key
	 *
	 * @return DotenvEditor
	 */
	public function deleteKey($key) {
		$keys = [$key];

		return $this->deleteKeys($keys);
	}

    /**
     * Save buffer of the .env file content to file
     *
     * @return DotenvEditor
     */
    public function save() {
    	if ($this->autoBackup) {
    		$this->backup();
    	}

    	$this->writer->save($this->filePath);
    	return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Working with backups
    |--------------------------------------------------------------------------
    |
    | autoBackup($on)
    | backup()
    | getBackups()
    | getLatestBackup()
    | restore($filepath = null)
    | deleteBackups($filepaths = [])
    | deleteBackup($filepath)
    |
    */

    /**
     * Switching of the auto backup mode
     *
     * @param  boolean $on
     *
     * @return DotenvEditor
     */
    public function autoBackup($on = true) {
    	$this->autoBackup = $on;
    	return $this;
    }

    /**
     * Create one backup of the current .env file
     *
     * @return DotenvEditor
     */
    public function backup() {
        copy(
            $this->filePath,
            $this->backupPath . self::BACKUP_FILENAME_PREFIX . date('Y_m_d_His') . self::BACKUP_FILENAME_SUFFIX
        );

        return $this;
    }

    /**
     * Return an array with all available backups.
     *
     * @return array
     */
    public function getBackups()
    {
        $backups = array_diff(scandir($this->backupPath), array('..', '.'));
        $output = [];

        foreach ($backups as $backup) {
			$filenamePrefix = preg_quote(self::BACKUP_FILENAME_PREFIX, '/');
			$filenameSuffix = preg_quote(self::BACKUP_FILENAME_SUFFIX, '/');
			$filenameRegex  = '/^' .$filenamePrefix. '(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})(\d{2})' .$filenameSuffix. '$/';

        	$datetime = preg_replace($filenameRegex, '$1-$2-$3 $4:$5:$6', $backup);

        	$data = [
				'filename'   => $backup,
				'filepath'   => $this->backupPath . $backup,
				'created_at' => $datetime,
        	];

        	$output[] = $data;
        }

        return $output;
    }

    /**
     * Return the filepath of the latest backup.
     *
     * @return array
     */
    public function getLatestBackup()
    {
        $backups = $this->getBackups();

        if (empty($backups)) {
        	return null;
        }

        $latestBackup = 0;
        foreach($backups as $backup){
            $timestamp = strtotime($backup['created_at']);
            if($timestamp > $latestBackup) {
                $latestBackup = $timestamp;
            }
        }

		$filename  = self::BACKUP_FILENAME_PREFIX . date("Y_m_d_His", $latestBackup) . self::BACKUP_FILENAME_SUFFIX;
		$filepath  = $this->backupPath . $filename;
		$createdAt = date("Y-m-d H:i:s", $latestBackup);

        $output = [
			'filename'   => $filename,
			'filepath'   => $filepath,
			'created_at' => $createdAt
        ];

        return $output;
    }

    /**
     * Restores the latest backup or a backup with special filepath.
     *
     * @param string|null	$filepath
     *
     * @return DotenvEditor
     */
    public function restore($filepath = null) {
        if (is_null($filepath)) {
        	$latestBackup = $this->getLatestBackup();
        	if (is_null($latestBackup)) {
        		throw new NoBackupAvailableException("There are no available backups!");
        	}
        	$filepath = $latestBackup['filepath'];
        }

    	if (!file_exists($filepath)) {
    		throw new FileNotFoundException("File does not exist at path {$filepath}");
    	}

    	copy($filepath, $this->filePath);
    	$this->setBuffer($this->getContent());

        return $this;
    }

    /**
     * Delete all or the given backup files
     *
     * @param array	$filepaths
     *
     * @return DotenvEditor
     */
    public function deleteBackups($filepaths = []) {
    	if (empty($filepaths)) {
    		$allBackups = $this->getBackups();
    		foreach ($allBackups as $backup) {
    			$filepaths[] = $backup['filepath'];
    		}
    	}

    	foreach ($filepaths as $filepath) {
    		if(file_exists($filepath)){
	            unlink($filepath);
	        }
    	}
    	return $this;
    }

    /**
     * Delete the given backup-file
     *
     * @param string	$filepath
     *
     * @return DotenvEditor
     */
    public function deleteBackup($filepath) {
        return $this->deleteBackups([$filepath]);
    }
}
