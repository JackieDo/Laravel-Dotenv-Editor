<?php

namespace Jackiedo\DotenvEditor\Console\Commands;

use Illuminate\Console\Command;
use Jackiedo\DotenvEditor\Console\Traits\CreateCommandInstanceTrait;
use Symfony\Component\Console\Input\InputOption;

class DotenvBackupCommand extends Command
{
    use CreateCommandInstanceTrait;

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
     * The .env file path.
     *
     * @var null|string
     */
    protected $filePath;

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
     * Convert string to corresponding type.
     *
     * @param string $string
     *
     * @return mixed
     */
    protected function stringToType($string)
    {
        if (is_string($string)) {
            switch (true) {
                case 'null' == $string || 'NULL' == $string:
                    $string = null;
                    break;

                case 'true' == $string || 'TRUE' == $string:
                    $string = true;
                    break;

                case 'false' == $string || 'FALSE' == $string:
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
            ['filepath', null, InputOption::VALUE_OPTIONAL, 'The file path will be backed up. Do not use if you want to backup file .env at root application folder.'],
        ];
    }
}
