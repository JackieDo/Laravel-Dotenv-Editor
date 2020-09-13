# Laravel Dotenv Editor
![laravel-dotenv-editor](https://cloud.githubusercontent.com/assets/9862115/25982836/029612b2-370a-11e7-82c5-d9146dc914a1.png)

[![Latest Stable Version](https://poser.pugx.org/jackiedo/dotenv-editor/v/stable)](https://packagist.org/packages/jackiedo/dotenv-editor)
[![Total Downloads](https://poser.pugx.org/jackiedo/dotenv-editor/downloads)](https://packagist.org/packages/jackiedo/dotenv-editor)
[![Latest Unstable Version](https://poser.pugx.org/jackiedo/dotenv-editor/v/unstable)](https://packagist.org/packages/jackiedo/dotenv-editor)
[![License](https://poser.pugx.org/jackiedo/dotenv-editor/license)](https://packagist.org/packages/jackiedo/dotenv-editor)

Laravel Dotenv Editor is the .env file editor (or files with same structure and syntax) for Laravel 5.8+. Now you can easily edit .env files with the following features:

- Read raw content of file.
- Read lines of file content.
- Read setters (key-value-pair) of file content.
- Determine one key name of existing setter.
- Append empty lines to file.
- Append comment lines to file.
- Append new or update exists setter lines to file.
- Delete existing setter line in file.
- Backup and restore file.
- Manage backup files.

# Versions and compatibility
Laravel Dotenv Editor is compatible with Laravel 5+ and above. Since the release of `1.2.0` onwards, this package only supports Laravel 5.8 and later. Previous versions of Laravel will no longer be supported.

# Note for the release `1.2.0` and later
Starting with the release `1.2.0`, the .gitignore file in the folder containing the backup file will no longer be created automatically. Developers will have to create this file manually if deemed necessary.

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
    - [Writing content into file](#writing-content-into-file)
    - [Backing up and restoring file](#backing-up-and-restoring-file)
    - [Method chaining](#method-chaining)
    - [Working with Artisan CLI](#working-with-artisan-cli)
    - [Exceptions](#exceptions)

## Installation
You can install this package through [Composer](https://getcomposer.org) with the following steps:

#### Step 1 - Require package
At the root of your application directory, run the following command (in any terminal client):

```shell
$ composer require jackiedo/dotenv-editor
```

**Note:** Since Laravel 5.5, [service providers and aliases are automatically registered](https://laravel.com/docs/5.5/packages#package-discovery), so you can safely skip the following two steps:

#### Step 2 - Register service provider
Open `config/app.php`, and add a new line to the providers section:

```php
Jackiedo\DotenvEditor\DotenvEditorServiceProvider::class,
```

#### Step 3 - Register facade
Add the following line to the aliases section in file `config/app.php`:

```php
'DotenvEditor' => Jackiedo\DotenvEditor\Facades\DotenvEditor::class,
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
        DotenvEditor::doSomething();
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
        $editor = $this->editor->doSomething();
    }
}
```

### Loading file for working
By default, Laravel Dotenv Editor will load the `.env` file in the root of your project. Example:

```php
$content = DotenvEditor::getContent(); // Get raw content of file .env in root folder
```

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
// Working with file .env in root folder
$file = DotenvEditor::load();

// Working with file .env.example in root folder
$file = DotenvEditor::load('.env.example');

// Working with file .env.backup in folder storage/dotenv-editor/backups/
$file = DotenvEditor::load(storage_path('dotenv-editor/backups/.env.backup'));
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

#### Reading content by lines.
**Method syntax:**

```php
/**
 * Get all lines from file
 *
 * @return array
 */
public function getLines();
```

**Example:**

```php
$lines = DotenvEditor::getLines();
```

**Note:** This will return an array. Each element in the array consists of the following items:

- Number of the line.
- Raw content of the line.
- Parsed content of the line, including: type of line (empty, comment, setter...), key name of setter, value of setter, comment of setter...

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
 * @throws \Jackiedo\DotenvEditor\Exceptions\KeyNotFoundException
 *
 * @return string
 */
public function getValue($key);
```

**Example:**

```php
$value = DotenvEditor::getValue('APP_URL');
```

### Writing content into a file
To edit file content, you have two jobs:

- First is writing content into the buffer
- Second is saving the buffer into the file

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
$file = DotenvEditor::addEmpty();
```

#### Add a comment line into buffer
**Method syntax:**

```php
/**
 * Add comment line to buffer
 *
 * @param object
 *
 * @return DotenvEditor
 */
public function addComment($comment);
```

**Example:**

```php
$file = DotenvEditor::addComment('This is a comment line');
```

#### Add or update a setter into buffer
**Method syntax:**

```php
/**
 * Set one key to buffer
 *
 * @param string       $key      Key name of setter
 * @param string|null  $value    Value of setter
 * @param string|null  $comment  Comment of setter
 * @param boolean      $export   Leading key name by "export "
 *
 * @return DotenvEditor
 */
public function setKey($key, $value = null, $comment = null, $export = false);
```

**Example:**

```php
// Set key ENV_KEY with empty value
$file = DotenvEditor::setKey('ENV_KEY');

// Set key ENV_KEY with none empty value
$file = DotenvEditor::setKey('ENV_KEY', 'anything-you-want');

// Set key ENV_KEY with a value and comment
$file = DotenvEditor::setKey('ENV_KEY', 'anything-you-want', 'your-comment');

// Update key ENV_KEY with a new value and keep earlier comment
$file = DotenvEditor::setKey('ENV_KEY', 'new-value-1');

// Update key ENV_KEY with a new value, keep earlier comment and use 'export ' before key name
$file = DotenvEditor::setKey('ENV_KEY', 'new-value', null, true);

// Update key ENV_KEY with a new value and clear comment
$file = DotenvEditor::setKey('ENV_KEY', 'new-value-2', '', false);
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
$file = DotenvEditor::setKeys([
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
$file = DotenvEditor::setKeys([
    'ENV_KEY_1' => 'your-value-1',
    'ENV_KEY_2' => 'your-value-2',
    'ENV_KEY_3' => 'your-value-3',
]);
```

#### Delete a setter line in buffer
**Method syntax:**

```php
/**
 * Delete on key in buffer
 *
 * @param  string  $key
 *
 * @return DotenvEditor
 */
public function deleteKey($key);
```

**Example:**

```php
$file = DotenvEditor::deleteKey('ENV_KEY');
```

#### Delete multi setter lines in buffer
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
$file = DotenvEditor::deleteKeys(['ENV_KEY_1', 'ENV_KEY_2']);
```

#### Save buffer into file
**Method syntax:**

```php
/**
 * Save buffer to file
 *
 * @return DotenvEditor
 */
public function save();
```

**Example:**

```php
$file = DotenvEditor::save();
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
$file = DotenvEditor::backup();
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
$file = DotenvEditor::restore();

// Restore from other file
$file = DotenvEditor::restore(storage_path('dotenv-editor/backups/.env.backup_2017_04_10_152709'));
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
$file = DotenvEditor::deleteBackup(storage_path('dotenv-editor/backups/.env.backup_2017_04_10_152709'));
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
$file = DotenvEditor::deleteBackups([
    storage_path('dotenv-editor/backups/.env.backup_2017_04_10_152709'),
    storage_path('dotenv-editor/backups/.env.backup_2017_04_11_091552')
]);

// Delete all backup
$file = DotenvEditor::deleteBackups();
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
$file = DotenvEditor::autoBackup(true);

// Disable auto backup
$file = DotenvEditor::autoBackup(false);
```

### Method chaining
Some functions of loading, writing, backing up, restoring support method chaining. So these functions can be called chained together in a single statement. Example:

```php
$file = DotenvEditor::load('.env.example')->backup()->setKey('APP_URL', 'http://example.com')->save();

return $file->getKeys();
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
| *InvalidValueException*      | When the value of setter is invalid.           |
| *KeyNotFoundException*       | When the requested key does not exist in file. |
| *NoBackupAvailableException* | When no backup file exists.                    |
| *UnableReadFileException*    | When unable to read the file.                  |
| *UnableWriteToFileException* | When unable to write to the file.              |

# Contributors
This project exists thanks to all its [contributors](https://github.com/JackieDo/Laravel-Dotenv-Editor/graphs/contributors).

# License
[MIT](LICENSE) © Jackie Do
