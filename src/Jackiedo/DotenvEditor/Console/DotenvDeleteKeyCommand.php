<?php  namespace Jackiedo\DotenvEditor\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Jackiedo\DotenvEditor\DotenvEditor;

class DotenvDeleteKeyCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dotenv:delete-key
                            {key         : Key name will be deleted}
                            {--filepath= : The file path should use to load for working. Do not use if you want to load file .env at root application folder}
                            {--force     : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete one setter in the .env file';

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
     * The key name use to add or update
     *
     * @var string
     */
    protected $key;

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

        $this->line('Deleting key in your file...');

        $this->editor->load($this->filePath)->deleteKey($this->key)->save();

        $this->info("The key [{$this->key}] is deletted successfully.");
    }

    /**
     * Transfer inputs to properties of editing
     *
     * @return void
     */
    protected function transferInputsToProperties() {
        $filePath = $this->stringToType($this->option('filepath'));

        $this->filePath = (is_string($filePath)) ? base_path($filePath) : null;
        $this->key      = $this->argument('key');
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
