<?php  namespace Jackiedo\DotenvEditor\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Jackiedo\DotenvEditor\DotenvEditor;

class DotenvSetKeyCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dotenv:set-key
                            {key             : Key name want to add new one or update exists one}
                            {value?          : Value want to set for this key}
                            {comment?        : Comment want to set for this key. Type "false" to clear comment for exists key}
                            {--e|export-key  : Leading before this key name with "export " command}
                            {--filepath=     : The .env file-path should use to edit. Do not use if you want to edit file .env at root application folder}
                            {--r|restore     : Force restore the .env from backup or special file}
                            {--restore-path= : The special file-path should use to restore. Do not use if you want to restore from a backup}
                            {--force         : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new or update one setter in .env file';

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
    public function handle()
    {
        $this->transferInputsToProperties();

        if (! $this->confirmToProceed()) {
            return false;
        }

        $this->line('Updating key in your file...');

        $this->editor->load($this->filePath, $this->forceRestore, $this->restorePath);
        $this->editor->setKey($this->key, $this->value, $this->comment, $this->exportKey);
        $this->editor->save();

        $this->info("The key [{$this->key}] is set successfully with value [{$this->value}].");
    }

    /**
     * Transfer inputs to properties of editing
     *
     * @return void
     */
    protected function transferInputsToProperties() {
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
    protected function stringToType($string) {
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
}
