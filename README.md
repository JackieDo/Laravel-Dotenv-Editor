# Laravel Dotenv Editor
![laravel-dotenv-editor](https://cloud.githubusercontent.com/assets/9862115/25982836/029612b2-370a-11e7-82c5-d9146dc914a1.png)

[![Latest Stable Version](https://poser.pugx.org/jackiedo/dotenv-editor/v/stable)](https://packagist.org/packages/jackiedo/dotenv-editor)
[![Total Downloads](https://poser.pugx.org/jackiedo/dotenv-editor/downloads)](https://packagist.org/packages/jackiedo/dotenv-editor)
[![Latest Unstable Version](https://poser.pugx.org/jackiedo/dotenv-editor/v/unstable)](https://packagist.org/packages/jackiedo/dotenv-editor)
[![License](https://poser.pugx.org/jackiedo/dotenv-editor/license)](https://packagist.org/packages/jackiedo/dotenv-editor)

Laravel Dotenv Editor is the .env file editor (or files with same structure and syntax) for Laravel 5.8+. Now you can easily edit .env files with the following features:

- Read raw content of file.
- Read entries of file content.
- Read setters (key-value-pair) in file content.
- Check for existence of setter.
- Append empty lines to file content.
- Append comment lines to file content.
- Append new or update an existing setter entry.
- Update comment of an existing setter entry.
- Update export status of an existing setter entry.
- Delete existing setter entry in file content.
- Backup and restore file content.
- Manage backuped files.

# Versions and compatibility
Laravel Dotenv Editor is compatible with Laravel 5.8 and later.

# Important note for the version `2.x`
After the release of `1.2.1`, version 1.x will be discontinued in favor of a new version (version `2.x`) with some changes to be compatible with the parsing method of `vlucas/phpdotenv` package. Version `2.x` has changed quite a lot compared to the previous version. If you have used earlier versions of this package, please re-read the instructions carefully.

# Documentation
Look at one of the following topics to learn more about Laravel Dotenv Editor:

- [Installation](#installation)
- [Configuration](#configuration)
    - [Auto backup mode](#auto-backup-mode)
    - [Backup location](#backup-location)
    - [Always create backup folder](#always-create-backup-folder)
- [Usage](#usage)
    - [Working with facade](#working-with-facade)
    - [Using dependency injection](#using-dependency-injection)
    - [Loading file for working](#loading-file-for-working)
    - [Reading file content](#reading-file-content)
    - [Edit file content](#edit-file-content)
    - [Backing up and restoring file](#backing-up-and-restoring-file)
    - [Method chaining](#method-chaining)
    - [Working with Artisan CLI](#working-with-artisan-cli)
    - [Exceptions](#exceptions)

## Installation
You can install this package through [Composer](https://getcomposer.org). At the root of your application directory, run the following command (in any terminal client):

```shell
$ composer require jackiedo/dotenv-editor
```

## Configuration
To start using the package, you should publish the configuration file so that you can configure the package as needed. To do that, run the following command (in any terminal client) at the root of your application:

```shell
$ php artisan vendor:publish --provider="Jackiedo\DotenvEditor\DotenvEditorServiceProvider" --tag="config"
```

This will create a `config/dotenv-editor.php` file in your app that you can modify to set your configuration. Also, make sure you check for changes to the original config file in this package between releases. Currently there are the following settings:

#### Auto backup mode
The `autoBackup` setting allows your original file to be backed up automatically before saving. Set it to `true` to agree.

#### Backup location
The `backupPath` setting is used to specify where your file is backed up. This value is a sub path (sub-folder) from the root folder of the project application.

#### Always create backup folder
The `alwaysCreateBackupFolder` setting is used to request that the backup folder always be created, whether or not the backup is performed.

## Usage
### Working with facade
Laravel Dotenv Editor has a facade with the name `Jackiedo\DotenvEditor\Facades\DotenvEditor`. You can perform all operations through this facade.

**Example:**

```php
<?php namespace Your\Namespace;

// ...

use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class YourClass
{
    public function yourMethod()
    {
        $return = DotenvEditor::doSomething();
    }
}
```

### Using dependency injection
This package also supports dependency injection. You can easily inject an instance of the `Jackiedo\DotenvEditor\DotenvEditor` class into your controller or other classes.

**Example:**

```php
<?php namespace App\Http\Controllers;

// ...

use Jackiedo\DotenvEditor\DotenvEditor;

class TestDotenvEditorController extends Controller
{
    protected $editor;

    public function __construct(DotenvEditor $editor)
    {
        $this->editor = $editor;
    }

    public function doSomething()
    {
        $return = $this->editor->doSomething();
    }
}
```

### Loading file for working
By default, the Laravel Dotenv Editor will load the dotenv file that Laravel is reading from in your project. That is, if your Laravel is using the `.env.local` file to store the configuration values, the Laravel Dotenv Editor also loads the content from that file by default.

However, if you want to explicitly specify the files you are going to work with, you should use the `load()` method.

**Method syntax:**

```php
/**
 * Load file for working
 *
 * @param  string|null  $filePath           The file path
 * @param  boolean      $restoreIfNotFound  Restore this file from other file if it's not found
 * @param  string|null  $restorePath        The file path you want to restore from
 *
 * @return DotenvEditor
 */
public function load($filePath = null, $restoreIfNotFound = false, $restorePath = null);
```

**Example:**

```php
// Working with the dotenv file that Laravel is using
$editor = DotenvEditor::load();

// Working with file .env.example in root folder of project
$editor = DotenvEditor::load(base_path('.env.example'));

// Working with file .env.backup in folder storage/dotenv-editor/backups/
$editor = DotenvEditor::load(storage_path('dotenv-editor/backups/.env.backup'));
```

**Note:** The `load()` method has three parameters:

- **`$filePath`**: The path to the file you want to work with. Set `null` to work with the file `.env` in the root folder.
- **`$restoreIfNotFound`**: Allows to restore your file if it is not found.
- **`$restorePath`**: The path to the file used to restoring. Set `null` to restore from an older backup file.

### Reading file content
#### Reading raw content.
**Method syntax:**

```php
/**
 * Get raw content of file
 *
 * @return string
 */
public function getContent();
```

**Example:**

```php
$rawContent = DotenvEditor::getContent();
```

#### Reading content by entries.
**Method syntax:**

```php
/**
 * Get all entries from file
 *
 * @return array
 */
public function getEntries(bool $withParsedData = false);
```

**Example:**

```php
$lines = DotenvEditor::getEntries(true);
```

**Note:** This will return an array. Each element in the array consists of the following items:

- Starting line number of entry.
- Raw content of the entry.
- Parsed content of the entry (if the `$withParsedData` is set to `true`), including: type of entry (empty, comment, setter...), key name of setter, value of setter, comment of setter...

#### Reading content by keys
**Method syntax:**

```php
/**
 * Get all or exists given keys in file content
 *
 * @param  array  $keys
 *
 * @return array
 */
public function getKeys($keys = []);
```

**Example:**

```php
// Get all keys
$keys = DotenvEditor::getKeys();

// Only get two given keys if exists
$keys = DotenvEditor::getKeys(['APP_DEBUG', 'APP_URL']);
```

**Note:** This will return an array. Each element in the array consists of the following items:

- Number of the line.
- Key name of the setter.
- Value of the setter.
- Comment of the setter.
- If this key is used for the "export" command or not.

#### Reading data of the specific key
**Method syntax:**

```php
/**
 * Return information of entry matching to a given key in the file content.
 *
 * @throws KeyNotFoundException
 *
 * @return array
 */
public function getKey($key);
```

**Example:**

```php
// Get all keys
$keys = DotenvEditor::getKey('EXAMPLE_KEY');
```

#### Determine if a key exists
**Method syntax:**

```php
/**
 * Check, if a given key is exists in the file content
 *
 * @param  string  $keys
 *
 * @return bool
 */
public function keyExists($key);
```

**Example:**

```php
$keyExists = DotenvEditor::keyExists('APP_URL');
```

#### Get value of a key
**Method syntax:**

```php
/**
 * Return the value matching to a given key in the file content
 *
 * @param  $key
 *
 * @throws KeyNotFoundException
 *
 * @return string
 */
public function getValue($key);
```

**Example:**

```php
$value = DotenvEditor::getValue('APP_URL');
```

### Edit file content
To edit file content, you have two jobs:

- First is writing content into the buffer.
- Second is saving the buffer into the file.

> Always keep in mind that the contents of the buffer and the dotenv file will not be the same unless you have saved the contents.

#### Add an empty line into buffer
**Method syntax:**

```php
/**
 * Add empty line to buffer
 *
 * @return DotenvEditor
 */
public function addEmpty();
```

**Example:**

```php
$editor = DotenvEditor::addEmpty();
```

#### Add a comment line into buffer
**Method syntax:**

```php
/**
 * Add comment line to buffer
 *
 * @param string $comment
 *
 * @return DotenvEditor
 */
public function addComment(string $comment);
```

**Example:**

```php
$editor = DotenvEditor::addComment('This is a comment line');
```

#### Add or update a setter into buffer
**Method syntax:**

```php
/**
 * Set one key to|in the buffer.
 *
 * @param string      $key     Key name of setter
 * @param null|string $value   Value of setter
 * @param null|string $comment Comment of setter
 * @param null|bool   $export  Leading key name by "export "
 *
 * @return DotenvEditor
 */
public function setKey(string $key, ?string $value = null, ?string $comment = null, $export = null);
```

**Example:**

```php
// Set key ENV_KEY with empty value
$editor = DotenvEditor::setKey('ENV_KEY');

// Set key ENV_KEY with none empty value
$editor = DotenvEditor::setKey('ENV_KEY', 'anything you want');

// Set key ENV_KEY with a value and comment
$editor = DotenvEditor::setKey('ENV_KEY', 'anything you want', 'your comment');

// Update key ENV_KEY with a new value and keep earlier comment
$editor = DotenvEditor::setKey('ENV_KEY', 'new value 1');

// Update key ENV_KEY with a new value, keep previous comment and use the 'export' keyword before key name
$editor = DotenvEditor::setKey('ENV_KEY', 'new value', null, true);

// Update key ENV_KEY with a new value, remove comment and keep previous export status
$editor = DotenvEditor::setKey('ENV_KEY', 'new-value-2', '');

// Update key ENV_KEY with a new value, remove comment and export keyword
$editor = DotenvEditor::setKey('ENV_KEY', 'new-value-2', '', false);
```

#### Add or update multi setter into buffer
**Method syntax:**

```php
/**
 * Set many keys to buffer
 *
 * @param  array  $data
 *
 * @return DotenvEditor
 */
public function setKeys($data);
```

**Example:**

```php
$editor = DotenvEditor::setKeys([
    [
        'key'     => 'ENV_KEY_1',
        'value'   => 'your-value-1',
        'comment' => 'your-comment-1',
        'export'  => true
    ],
    [
        'key'     => 'ENV_KEY_2',
        'value'   => 'your-value-2',
        'export'  => true
    ],
    [
        'key'     => 'ENV_KEY_3',
        'value'   => 'your-value-3',
    ]
]);
```

Alternatively, you can also provide an associative array of keys and values:

```php
$editor = DotenvEditor::setKeys([
    'ENV_KEY_1' => 'your-value-1',
    'ENV_KEY_2' => 'your-value-2',
    'ENV_KEY_3' => 'your-value-3',
]);
```

#### Set comment for an existing setter
**Method syntax:**

```php
/**
 * Set the comment for setter.
 *
 * @param string      $key     Key name of setter
 * @param null|string $comment The comment content
 *
 * @return DotenvEditor
 */
public function setSetterComment(string $key, ?string $comment = null);
```

**Example:**

```php
$editor = DotenvEditor::setSetterComment('ENV_KEY', 'new comment');
```

#### Set export status for an existing setter
**Method syntax:**

```php
/**
 * Set the export status for setter.
 *
 * @param string $key   Key name of setter
 * @param bool   $state Leading key name by "export "
 *
 * @return DotenvEditor
 */
public function setExportSetter(string $key, bool $state = true);
```

**Example:**

```php
$editor = DotenvEditor::setExportSetter('ENV_KEY', false);
```

#### Delete a setter entry in buffer
**Method syntax:**

```php
/**
 * Delete on key in buffer
 *
 * @param string $key Key name of setter
 *
 * @return DotenvEditor
 */
public function deleteKey($key);
```

**Example:**

```php
$editor = DotenvEditor::deleteKey('ENV_KEY');
```

#### Delete multi setter entries in buffer
**Method syntax:**

```php
/**
 * Delete many keys in buffer
 *
 * @param  array $keys
 *
 * @return DotenvEditor
 */
public function deleteKeys($keys = []);
```

**Example:**

```php
// Delete two keys
$editor = DotenvEditor::deleteKeys(['ENV_KEY_1', 'ENV_KEY_2']);
```

#### Check if the buffer has changed from dotenv file content
**Method syntax:**

```php
/**
 * Determine if the buffer has changed.
 *
 * @return bool
 */
public function hasChanged();
```

#### Save buffer into file
**Method syntax:**

```php
/**
 * Save buffer to file.
 *
 * @param bool $rebuildBuffer Rebuild buffer from content of dotenv file
 *
 * @return DotenvEditor
 */
public function save(bool $rebuildBuffer = true);
```

**Example:**

```php
$editor = DotenvEditor::save();
```

### Backing up and restoring file
#### Backup your file
**Method syntax:**

```php
/**
 * Create one backup of loaded file
 *
 * @return DotenvEditor
 */
public function backup();
```

**Example:**

```php
$editor = DotenvEditor::backup();
```

#### Get all backup versions
**Method syntax:**

```php
/**
 * Return an array with all available backups
 *
 * @return array
 */
public function getBackups();
```

**Example:**

```php
$backups = DotenvEditor::getBackups();
```

#### Get latest backup version
**Method syntax:**

```php
/**
 * Return the information of the latest backup file
 *
 * @return array
 */
public function getLatestBackup();
```

**Example:**

```php
$latestBackup = DotenvEditor::getLatestBackup();
```

#### Restore your file from latest backup or other file
**Method syntax:**

```php
/**
 * Restore the loaded file from latest backup file or from special file.
 *
 * @param  string|null  $filePath
 *
 * @return DotenvEditor
 */
public function restore($filePath = null);
```

**Example:**

```php
// Restore from latest backup
$editor = DotenvEditor::restore();

// Restore from other file
$editor = DotenvEditor::restore(storage_path('dotenv-editor/backups/.env.backup_2017_04_10_152709'));
```

#### Delete one backup file
**Method syntax:**

```php
/**
 * Delete the given backup file
 *
 * @param  string  $filePath
 *
 * @return DotenvEditor
 */
public function deleteBackup($filePath);
```

**Example:**

```php
$editor = DotenvEditor::deleteBackup(storage_path('dotenv-editor/backups/.env.backup_2017_04_10_152709'));
```

#### Delete multi backup files
**Method syntax:**

```php
/**
 * Delete all or the given backup files
 *
 * @param  array  $filePaths
 *
 * @return DotenvEditor
 */
public function deleteBackups($filePaths = []);
```

**Example:**

```php
// Delete two backup file
$editor = DotenvEditor::deleteBackups([
    storage_path('dotenv-editor/backups/.env.backup_2017_04_10_152709'),
    storage_path('dotenv-editor/backups/.env.backup_2017_04_11_091552')
]);

// Delete all backup
$editor = DotenvEditor::deleteBackups();
```

#### Change auto backup mode
**Method syntax:**

```php
/**
 * Switching of the auto backup mode
 *
 * @param  boolean  $on
 *
 * @return DotenvEditor
 */
public function autoBackup($on = true);
```

**Example:**

```php
// Enable auto backup
$editor = DotenvEditor::autoBackup(true);

// Disable auto backup
$editor = DotenvEditor::autoBackup(false);
```

### Method chaining
Some functions of loading, writing, backing up, restoring support method chaining. So these functions can be called chained together in a single statement. Example:

```php
$editor = DotenvEditor::load('.env.example')->backup()->setKey('APP_URL', 'http://example.com')->save();

return $editor->getKeys();
```

### Working with Artisan CLI
Now, Laravel Dotenv Editor has 6 commands which can be used easily with the Artisan CLI. These are:

- `php artisan dotenv:backup`
- `php artisan dotenv:get-backups`
- `php artisan dotenv:restore`
- `php artisan dotenv:get-keys`
- `php artisan dotenv:set-key`
- `php artisan dotenv:delete-key`

Please use each of the commands with the `--help` option to leanr more about there usage.

**Example:**

```shell
$ php artisan dotenv:get-backups --help
```

### Exceptions
This package will throw exceptions if something goes wrong. This way it's easier to debug your code using this package or to handle the error based on the type of exceptions.

| Exception                    | Reason                                         |
| ---------------------------- | ---------------------------------------------- |
| *FileNotFoundException*      | When the file was not found.                   |
| *InvalidKeyException*        | When the key of setter is invalid.             |
| *InvalidValueException*      | When the value of setter is invalid.           |
| *KeyNotFoundException*       | When the requested key does not exist in file. |
| *NoBackupAvailableException* | When no backup file exists.                    |
| *UnableReadFileException*    | When unable to read the file.                  |
| *UnableWriteToFileException* | When unable to write to the file.              |

# Contributors
This project exists thanks to all its [contributors](https://github.com/JackieDo/Laravel-Dotenv-Editor/graphs/contributors).

# License
[MIT](LICENSE) © Jackie Do
