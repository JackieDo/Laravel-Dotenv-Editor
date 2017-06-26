<?php  namespace Jackiedo\DotenvEditor\Console\Commands;

use Illuminate\Console\Command;
use Jackiedo\DotenvEditor\DotenvEditor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DotenvBackupCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dotenv:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the .env file';

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
        $filePath       = $this->stringToType($this->option('filepath'));
        $this->filePath = (is_string($filePath)) ? base_path($filePath) : null;

        $this->line('Backing up your file...');

        $backup = $this->editor->load($this->filePath)->backup()->getLatestBackup();

        $this->info("Your file was backed up successfully at path [{$backup['filepath']}].");
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
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            array('filepath', null, InputOption::VALUE_OPTIONAL, 'The file path will be backed up. Do not use if you want to backup file .env at root application folder.')
        ];
    }
}
