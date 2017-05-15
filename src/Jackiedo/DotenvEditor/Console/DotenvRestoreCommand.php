<?php  namespace Jackiedo\DotenvEditor\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Jackiedo\DotenvEditor\DotenvEditor;

class DotenvRestoreCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dotenv:restore
                            {--filepath=     : The .env file path will be restored. Do not use if you want to restore file .env at root application folder}
                            {--restore-path= : The special file path should use to restore. Do not use if you want to restore from latest backup file}
                            {--force         : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore the .env file from backup or special file';

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
     * The file path should use to restore
     *
     * @var string|null
     */
    protected $retorePath = null;

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

        $this->line('Restoring your file...');

        $this->editor->load($this->filePath)->restore($this->restorePath);

        $this->info("Your file is restored successfully");
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

        $restorePath       = $this->stringToType($this->option('restore-path'));
        $this->restorePath = (is_string($restorePath)) ? base_path($restorePath) : null;
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
}
