<?php  namespace Jackiedo\DotenvEditor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Jackiedo\DotenvEditor\DotenvEditor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DotenvSetKeyCommand extends Command
{
    use ConfirmableTrait;

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
     * The .env file editor instance
     *
     * @var \Jackiedo\DotenvEditor\DotenvEditor
     */
    protected $editor;

    /**
     * The .env file path
     *
     * @var string|null
     */
    protected $filePath = null;

    /**
     * Determine restoring the .env file if not exists
     *
     * @var boolean
     */
    protected $forceRestore = false;

    /**
     * The file path should use to restore
     *
     * @var string|null
     */
    protected $retorePath = null;

    /**
     * The key name use to add or update
     *
     * @var string
     */
    protected $key = 'NEW_ENV_KEY';

    /**
     * Value of key
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * Comment for key
     *
     * @var mixed
     */
    protected $comment = null;

    /**
     * Determine leading the key with 'export '
     *
     * @var boolean
     */
    protected $exportKey = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(DotenvEditor $editor)
    {
        parent::__construct();

        $this->editor = $editor;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->transferInputsToProperties();

        if (! $this->confirmToProceed()) {
            return false;
        }

        $this->line('Setting key in your file...');

        $this->editor->load($this->filePath, $this->forceRestore, $this->restorePath);
        $this->editor->setKey($this->key, $this->value, $this->comment, $this->exportKey);
        $this->editor->save();

        $this->info("The key [{$this->key}] is setted successfully with value [{$this->value}].");
    }

    /**
     * Transfer inputs to properties of editing
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
        $this->value     = $this->stringToType($this->argument('value'));
        $this->comment   = $this->stringToType($this->argument('comment'));
        $this->exportKey = $this->option('export-key');
    }

    /**
     * Convert string to corresponding type
     *
     * @param  string $string
     *
     * @return mixed
     */
    protected function stringToType($string)
    {
        if (is_string($string)) {
            switch (true) {
                case ($string == 'null' || $string == 'NULL'):
                    $string = null;
                    break;

                case ($string == 'true' || $string == 'TRUE'):
                    $string = true;
                    break;

                case ($string == 'false' || $string == 'FALSE'):
                    $string = false;
                    break;

                default:
                    break;
            }
        }

        return $string;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            array('key', InputArgument::REQUIRED, 'Key name will be added or updated.'),
            array('value', InputArgument::OPTIONAL, 'Value want to set for this key.'),
            array('comment', InputArgument::OPTIONAL, 'Comment want to set for this key. Type "false" to clear comment for exists key.')
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
            array('filepath', null, InputOption::VALUE_OPTIONAL, 'The file path should use to load for working. Do not use if you want to load file .env at root application folder.'),
            array('restore', 'r', InputOption::VALUE_NONE, 'Restore the loaded file from backup or special file if the loaded file is not found.'),
            array('restore-path', null, InputOption::VALUE_OPTIONAL, 'The special file path should use to restore from. Do not use if you want to restore from latest backup file.'),
            array('export-key', 'e', InputOption::VALUE_NONE, 'Leading before key name with "export " command.'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.')
        ];
    }
}
