<?php

namespace Jackiedo\DotenvEditor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Jackiedo\DotenvEditor\Console\Traits\CreateCommandInstanceTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DotenvSetKeyCommand extends Command
{
    use ConfirmableTrait;
    use CreateCommandInstanceTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dotenv:set-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new or update one setter into the .env file';

    /**
     * The .env file path.
     *
     * @var null|string
     */
    protected $filePath;

    /**
     * Determine restoring the .env file if not exists.
     *
     * @var bool
     */
    protected $forceRestore = false;

    /**
     * The file path should use to restore.
     *
     * @var null|string
     */
    protected $retorePath;

    /**
     * The key name use to add or update.
     *
     * @var string
     */
    protected $key = 'NEW_ENV_KEY';

    /**
     * Value of key.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Comment for key.
     *
     * @var mixed
     */
    protected $comment;

    /**
     * Determine leading the key with 'export '.
     *
     * @var bool
     */
    protected $exportKey = false;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->transferInputsToProperties();

        if (!$this->confirmToProceed()) {
            return false;
        }

        $this->line('Setting key in your file...');

        $this->editor->load($this->filePath, $this->forceRestore, $this->restorePath);
        $this->editor->setKey($this->key, $this->value, $this->comment, $this->exportKey);
        $this->editor->save();

        $this->info("The key [{$this->key}] is setted successfully with value [{$this->value}].");
    }

    /**
     * Transfer inputs to properties of editing.
     *
     * @return void
     */
    protected function transferInputsToProperties()
    {
        $filePath       = $this->stringToType($this->option('filepath'));
        $this->filePath = (is_string($filePath)) ? base_path($filePath) : null;

        $this->forceRestore = $this->option('restore');

        $restorePath       = $this->stringToType($this->option('restore-path'));
        $this->restorePath = (is_string($restorePath)) ? base_path($restorePath) : null;

        $this->key       = $this->argument('key');
        $this->value     = $this->argument('value');
        $this->comment   = $this->stringToType($this->argument('comment'));
        $this->exportKey = $this->option('export-key');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['key', InputArgument::REQUIRED, 'Key name will be added or updated.'],
            ['value', InputArgument::OPTIONAL, 'Value want to set for this key.'],
            ['comment', InputArgument::OPTIONAL, 'Comment want to set for this key. Type "false" to clear comment for exists key.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['filepath', null, InputOption::VALUE_OPTIONAL, 'The file path should use to load for working. Do not use if you want to load file .env at root application folder.'],
            ['restore', 'r', InputOption::VALUE_NONE, 'Restore the loaded file from backup or special file if the loaded file is not found.'],
            ['restore-path', null, InputOption::VALUE_OPTIONAL, 'The special file path should use to restore from. Do not use if you want to restore from latest backup file.'],
            ['export-key', 'e', InputOption::VALUE_NONE, 'Leading before key name with "export " command.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }
}
