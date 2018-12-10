<?php  namespace Jackiedo\DotenvEditor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Jackiedo\DotenvEditor\Console\Traits\CreateCommandInstanceTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DotenvDeleteKeyCommand extends Command
{
    use ConfirmableTrait, CreateCommandInstanceTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dotenv:delete-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete one setter in the .env file';

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

        $this->line('Deleting key in your file...');

        $this->editor->load($this->filePath)->deleteKey($this->key)->save();

        $this->info("The key [{$this->key}] is deletted successfully.");
    }

    /**
     * Transfer inputs to properties of editing
     *
     * @return void
     */
    protected function transferInputsToProperties()
    {
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
            array('key', InputArgument::REQUIRED, 'Key name will be deleted.')
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
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.')
        ];
    }
}
