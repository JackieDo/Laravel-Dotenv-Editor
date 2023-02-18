<?php

namespace Jackiedo\DotenvEditor;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use Jackiedo\DotenvEditor\Exceptions\FileNotFoundException;
use Jackiedo\DotenvEditor\Exceptions\KeyNotFoundException;
use Jackiedo\DotenvEditor\Exceptions\NoBackupAvailableException;
use Jackiedo\DotenvEditor\Workers\Formatters\Formatter;
use Jackiedo\DotenvEditor\Workers\Parsers\ParserV1;
use Jackiedo\DotenvEditor\Workers\Parsers\ParserV2;
use Jackiedo\DotenvEditor\Workers\Parsers\ParserV3;
use Jackiedo\PathHelper\Path;

/**
 * The DotenvEditor class.
 *
 * @package Jackiedo\DotenvEditor
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
class DotenvEditor
{
    /**
     * The backup filename prefix.
     */
    public const BACKUP_FILENAME_PREFIX = '.env.backup_';

    /**
     * The backup filename suffix.
     */
    public const BACKUP_FILENAME_SUFFIX = '';

    /**
     * The IoC Container.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Store instance of Config Repository;.
     *
     * @var Config
     */
    protected $config;

    /**
     * Compatible parser map.
     *
     * This map allowed select the reader parser compatible with
     * the "vlucas/phpdotenv" package based on its version
     *
     * @var array
     */
    protected $combatibleParserMap = [
        '5.0.0' => ParserV3::class,  // Laravel 8.x|9.x using "vlucas/dotenv" ^v5.0|^5.4
        '4.0.0' => ParserV2::class,  // Laravel 7.x using "vlucas/dotenv" ^v4.0
        '3.3.0' => ParserV1::class,  // Laravel 5.8|6.x using "vlucas/dotenv" ^v3.3
    ];

    /**
     * The reader instance.
     *
     * @var DotenvReader
     */
    protected $reader;

    /**
     * The writer instance.
     *
     * @var DotenvWriter
     */
    protected $writer;

    /**
     * The dotenv file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * The auto backup status.
     *
     * @var bool
     */
    protected $autoBackup;

    /**
     * The backup path.
     *
     * @var string
     */
    protected $backupPath;

    /**
     * The changed state of buffer.
     *
     * @var bool
     */
    protected $hasChanged;

    /**
     * Create a new DotenvEditor instance.
     *
     * @param Container $app
     * @param Config    $config
     *
     * @return void
     */
    public function __construct(Container $app, Config $config)
    {
        $this->app    = $app;
        $this->config = $config;

        $parser       = $this->selectCompatibleParser();
        $this->reader = new DotenvReader(new $parser);
        $this->writer = new DotenvWriter(new Formatter);

        self::configBackuping();
        $this->load();
    }

    /**
     * Load file for working.
     *
     * @param null|string $filePath          The file path
     * @param bool        $restoreIfNotFound Restore this file from other file if it's not found
     * @param null|string $restorePath       The file path you want to restore from
     *
     * @return DotenvEditor
     */
    public function load(?string $filePath = null, bool $restoreIfNotFound = false, ?string $restorePath = null)
    {
        $this->init();

        $this->filePath = $this->standardizeFilePath($filePath);

        $this->reader->load($this->filePath);

        if (file_exists((string) $this->filePath)) {
            $this->buildBuffer();

            return $this;
        }

        if ($restoreIfNotFound) {
            return $this->restore($restorePath);
        }

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Working with reading
    |--------------------------------------------------------------------------
    |
    | getContent()
    | getEntries()
    | getKey()
    | getKeys()
    | keyExists()
    | getValue()
    |
    */

    /**
     * Get raw content of file.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->reader->content();
    }

    /**
     * Get all entries from file.
     *
     * @param bool $withParsedData Include parsed data for each entry in the result
     *
     * @return array
     */
    public function getEntries(bool $withParsedData = false)
    {
        return $this->reader->entries($withParsedData);
    }

    /**
     * Get all or existing given keys in file content.
     *
     * @param array $keys The setter key names
     *
     * @return array
     */
    public function getKeys(array $keys = [])
    {
        $allKeys = $this->reader->keys();

        if (empty($keys)) {
            return $allKeys;
        }

        return array_filter($allKeys, function ($key) use ($keys) {
            return in_array($key, $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Return information of entry matching to a given key in the file content.
     *
     * @param string $key The setter key name
     *
     * @return array
     *
     * @throws KeyNotFoundException
     */
    public function getKey(string $key)
    {
        $allKeys = $this->getKeys([$key]);

        if (array_key_exists($key, $allKeys)) {
            return $allKeys[$key];
        }

        throw new KeyNotFoundException('Requested key not found in your environment file.');
    }

    /**
     * Return the value matching to a given key in the file content.
     *
     * @param string $key The setter key name
     *
     * @return string
     */
    public function getValue(string $key)
    {
        return $this->getKey($key)['value'];
    }

    /**
     * Check, if a given key is exists in the file content.
     *
     * @param string $key The setter key name
     *
     * @return bool
     */
    public function keyExists(string $key)
    {
        $allKeys = $this->getKeys();

        return array_key_exists($key, $allKeys);
    }

    /*
    |--------------------------------------------------------------------------
    | Working with writing
    |--------------------------------------------------------------------------
    |
    | hasChange()
    | getBuffer()
    | addEmpty()
    | addComment()
    | setKeys()
    | setKey()
    | setSetterComment()
    | clearSetterComment()
    | setExportSetter()
    | deleteKeys()
    | deleteKey()
    | save()
    |
    */

    /**
     * Determine if the buffer has changed.
     *
     * @return bool
     */
    public function hasChanged()
    {
        return $this->hasChanged;
    }

    /**
     * Return buffer content.
     *
     * @param bool $asArray Use array format for the result
     *
     * @return array
     */
    public function getBuffer(bool $asArray = true)
    {
        return $this->writer->getBuffer($asArray);
    }

    /**
     * Add empty line to buffer.
     *
     * @return DotenvEditor
     */
    public function addEmpty()
    {
        $this->writer->appendEmpty();

        $this->hasChanged = true;

        return $this;
    }

    /**
     * Add comment line to buffer.
     *
     * @param string $comment Comment content
     *
     * @return DotenvEditor
     */
    public function addComment(string $comment)
    {
        $this->writer->appendComment($comment);

        $this->hasChanged = true;

        return $this;
    }

    /**
     * Append or update some setters in the buffer.
     *
     * @param array $data The setter data set
     *
     * @return DotenvEditor
     */
    public function setKeys(array $data)
    {
        foreach ($data as $index => $setter) {
            if (!is_array($setter)) {
                if (!is_string($index)) {
                    continue;
                }

                $setter = [
                    'key'   => $index,
                    'value' => $setter,
                ];
            }

            if (array_key_exists('key', $setter)) {
                $key     = (string) $setter['key'];
                $value   = (string) array_key_exists('value', $setter) ? $setter['value'] : null;
                $comment = array_key_exists('comment', $setter) ? $setter['comment'] : null;
                $export  = array_key_exists('export', $setter) ? $setter['export'] : null;

                if (!is_file($this->filePath) || !$this->keyExists($key)) {
                    $this->writer->appendSetter($key, $value, (string) $comment, (bool) $export);
                } else {
                    $oldInfo = $this->getKeys([$key]);
                    $comment = is_null($comment) ? $oldInfo[$key]['comment'] : (string) $comment;
                    $export  = is_null($export) ? $oldInfo[$key]['export'] : (bool) $export;

                    $this->writer->updateSetter($key, $value, $comment, $export);
                }

                $this->hasChanged = true;
            }
        }

        return $this;
    }

    /**
     * Append or update one setter in the buffer.
     *
     * @param string      $key     The setter key name
     * @param null|string $value   Value of setter
     * @param null|string $comment Comment of setter
     * @param null|bool   $export  Leading key name by "export "
     *
     * @return DotenvEditor
     */
    public function setKey(string $key, ?string $value = null, ?string $comment = null, $export = null)
    {
        $data = [compact('key', 'value', 'comment', 'export')];

        return $this->setKeys($data);
    }

    /**
     * Set the comment for setter.
     *
     * @param string      $key     The setter key name
     * @param null|string $comment The comment content
     *
     * @return DotenvEditor
     */
    public function setSetterComment(string $key, ?string $comment = null)
    {
        $this->writer->updateSetterComment($key, $comment);

        $this->hasChanged = true;

        return $this;
    }

    /**
     * Clear the comment for setter.
     *
     * @param string $key The setter key name
     *
     * @return DotenvEditor
     */
    public function clearSetterComment(string $key)
    {
        return $this->setSetterComment($key, null);
    }

    /**
     * Set the export status for setter.
     *
     * @param string $key   The setter key name
     * @param bool   $state Leading key name by "export "
     *
     * @return DotenvEditor
     */
    public function setExportSetter(string $key, bool $state = true)
    {
        $this->writer->updateSetterExport($key, $state);

        $this->hasChanged = true;

        return $this;
    }

    /**
     * Delete some setters in buffer.
     *
     * @param array $keys The setter key names
     *
     * @return DotenvEditor
     */
    public function deleteKeys(array $keys = [])
    {
        foreach ($keys as $key) {
            $this->writer->deleteSetter($key);
        }

        $this->hasChanged = true;

        return $this;
    }

    /**
     * Delete one setter in buffer.
     *
     * @param string $key The setter key name
     *
     * @return DotenvEditor
     */
    public function deleteKey(string $key)
    {
        $keys = [$key];

        return $this->deleteKeys($keys);
    }

    /**
     * Save buffer to file.
     *
     * @param bool $rebuildBuffer Rebuild buffer from content of dotenv file
     *
     * @return DotenvEditor
     */
    public function save(bool $rebuildBuffer = true)
    {
        if (is_file($this->filePath) && $this->autoBackup) {
            $this->backup();
        }

        $this->writer->saveTo($this->filePath);

        if ($rebuildBuffer && $this->hasChanged()) {
            $this->buildBuffer();
        }

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Working with backups
    |--------------------------------------------------------------------------
    |
    | autoBackup()
    | backup()
    | getBackups()
    | getLatestBackup()
    | restore()
    | deleteBackups()
    | deleteBackup()
    |
    */

    /**
     * Turn automatic backup on or off.
     *
     * @param bool $on The state to set
     *
     * @return DotenvEditor
     */
    public function autoBackup(bool $on = true)
    {
        $this->autoBackup = $on;

        return $this;
    }

    /**
     * Create one backup of loaded file.
     *
     * @return DotenvEditor
     *
     * @throws FileNotFoundException
     */
    public function backup()
    {
        if (!is_file($this->filePath)) {
            throw new FileNotFoundException("File does not exist at path {$this->filePath}");

            return false;
        }

        // Make sure the backup directory exists
        $this->createBackupFolder();

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
        $output = [];

        if (!is_dir($this->backupPath)) {
            return $output;
        }

        $filenameRegex = '/^' . preg_quote(self::BACKUP_FILENAME_PREFIX, '/') . '(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})(\d{2})' . preg_quote(self::BACKUP_FILENAME_SUFFIX, '/') . '$/';
        $backups       = array_filter(array_diff(scandir($this->backupPath), ['..', '.']), function ($backup) use ($filenameRegex) {
            return preg_match($filenameRegex, $backup);
        });

        foreach ($backups as $backup) {
            $output[] = [
                'filename'   => $backup,
                'filepath'   => Path::osStyle($this->backupPath . $backup),
                'created_at' => preg_replace($filenameRegex, '$1-$2-$3 $4:$5:$6', $backup),
            ];
        }

        return $output;
    }

    /**
     * Return the information of the latest backup file.
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

        foreach ($backups as $backup) {
            $timestamp = strtotime($backup['created_at']);

            if ($timestamp > $latestBackup) {
                $latestBackup = $timestamp;
            }
        }

        $fileName  = self::BACKUP_FILENAME_PREFIX . date('Y_m_d_His', $latestBackup) . self::BACKUP_FILENAME_SUFFIX;
        $filePath  = Path::osStyle($this->backupPath . $fileName);
        $createdAt = date('Y-m-d H:i:s', $latestBackup);

        return [
            'filename'   => $fileName,
            'filepath'   => $filePath,
            'created_at' => $createdAt,
        ];
    }

    /**
     * Restore the loaded file from latest backup file or from special file.
     *
     * @param null|string $filePath The file use to restore
     *
     * @return DotenvEditor
     *
     * @throws NoBackupAvailableException
     * @throws FileNotFoundException
     */
    public function restore(?string $filePath = null)
    {
        if (is_null($filePath)) {
            $latestBackup = $this->getLatestBackup();

            if (is_null($latestBackup)) {
                throw new NoBackupAvailableException('There are no available backups!');
            }

            $filePath = $latestBackup['filepath'];
        }

        if (!is_file($filePath)) {
            throw new FileNotFoundException("File does not exist at path {$filePath}");
        }

        copy($filePath, $this->filePath);
        $this->buildBuffer();

        return $this;
    }

    /**
     * Delete all or the given backup files.
     *
     * @param array $filePaths The set of backup files to delete
     *
     * @return DotenvEditor
     */
    public function deleteBackups(array $filePaths = [])
    {
        if (empty($filePaths)) {
            $allBackups = $this->getBackups();

            foreach ($allBackups as $backup) {
                $filePaths[] = $backup['filepath'];
            }
        }

        foreach ($filePaths as $filePath) {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }

        return $this;
    }

    /**
     * Delete the given backup file.
     *
     * @param string $filePath The backup file to delete
     *
     * @return DotenvEditor
     */
    public function deleteBackup(string $filePath)
    {
        return $this->deleteBackups([$filePath]);
    }

    /**
     * Initialize content for editor.
     *
     * @return void
     */
    protected function init()
    {
        $this->hasChanged = false;
        $this->filePath = null;

        $this->reader->load(null);
        $this->writer->setBuffer([]);
    }

    /**
     * Standardize the file path.
     *
     * @param null|string $filePath The file path
     *
     * @return string
     */
    protected function standardizeFilePath(?string $filePath = null)
    {
        if (is_null($filePath)) {
            if (method_exists($this->app, 'environmentPath') && method_exists($this->app, 'environmentFile')) {
                $filePath = Path::osStyle($this->app->environmentPath() . '/' . $this->app->environmentFile());
            } else {
                $filePath = Path::osStyle($this->app->basePath() . '/.env');
            }
        }

        return $filePath;
    }

    /**
     * Build buffer for writer.
     *
     * @return void
     */
    protected function buildBuffer()
    {
        $entries = $this->getEntries(true);

        $buffer = array_map(function ($entry) {
            $data = [
                'line' => $entry['line'],
            ];

            return array_merge($data, $entry['parsed_data']);
        }, $entries);

        $this->writer->setBuffer($buffer);

        $this->hasChanged = false;
    }

    /**
     * Create backup folder if not exists.
     *
     * @return void
     */
    protected function createBackupFolder()
    {
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0777, true);
        }
    }

    /**
     * Config settings for backuping.
     *
     * @return void
     */
    protected function configBackuping()
    {
        $this->autoBackup = $this->config->get('dotenv-editor.autoBackup', true);
        $this->backupPath = $this->config->get('dotenv-editor.backupPath');

        if (is_null($this->backupPath)) {
            if (method_exists($this->app, 'storagePath')) {
                $this->backupPath = ($this->app->storagePath() . '/dotenv-editor/backups');
            } else {
                $this->backupPath = $this->app->basePath() . '/storage/dotenv-editor/backups';
            }
        }

        $this->backupPath = Path::osStyle(rtrim($this->backupPath, '\\/') . '/');

        if ($this->config->get('dotenv-editor.alwaysCreateBackupFolder', false)) {
            $this->createBackupFolder();
        }
    }

    /**
     * Select the parser compatible with the "vlucas/phpdotenv" package.
     *
     * @return string
     */
    protected function selectCompatibleParser()
    {
        $installedDotenvVersion = $this->getDotenvPackageVersion();

        uksort($this->combatibleParserMap, function ($front, $behind) {
            return version_compare($behind, $front);
        });

        foreach ($this->combatibleParserMap as $minRequiredVersion => $compatibleParser) {
            if (version_compare($installedDotenvVersion, $minRequiredVersion) >= 0) {
                return $compatibleParser;
            }
        }

        return ParserV1::class;
    }

    /**
     * Catch version of the "vlucas/phpdotenv" package.
     *
     * @return string
     */
    protected function getDotenvPackageVersion()
    {
        $composerLock  = $this->app->basePath() . DIRECTORY_SEPARATOR . 'composer.lock';
        $arrayContent  = json_decode(file_get_contents($composerLock), true);
        $dotenvPackage = array_values(array_filter($arrayContent['packages'], function ($packageInfo, $index) {
            return 'vlucas/phpdotenv' === $packageInfo['name'];
        }, ARRAY_FILTER_USE_BOTH))[0];

        return preg_replace('/[a-zA-Z]/', '', $dotenvPackage['version']);
    }
}
