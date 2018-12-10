<?php  namespace Jackiedo\DotenvEditor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Jackiedo\DotenvEditor\Console\Traits\CreateCommandInstanceTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DotenvRestoreCommand extends Command
{
    use ConfirmableTrait, CreateCommandInstanceTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'dotenv:restore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore the .env file from backup or special file';

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
            array('filepath', null, InputOption::VALUE_OPTIONAL, 'The .env file path will be restored. Do not use if you want to restore file .env at root application folder.'),
            array('restore-path', null, InputOption::VALUE_OPTIONAL, 'The special file path should use to restore. Do not use if you want to restore from latest backup file.'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.')
        ];
    }
}
