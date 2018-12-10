<?php  namespace Jackiedo\DotenvEditor\Console\Commands;

use Illuminate\Console\Command;
use Jackiedo\DotenvEditor\Console\Traits\CreateCommandInstanceTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DotenvGetBackupsCommand extends Command
{
    use CreateCommandInstanceTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dotenv:get-backups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all the .env file backup versions';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $headers = ['File name', 'File path', 'Created at'];
        $backups = ($this->option('latest')) ? [$this->editor->getLatestBackup()] : $this->editor->getBackups();

        if ($this->option('latest')) {
            $latest = $this->editor->getLatestBackup();
            if (! is_null($latest)) {
                $backups = [$latest];
                $total = 1;
            } else {
                $total = 0;
            }
        } else {
            $backups = $this->editor->getBackups();
            $total   = count($backups);
        }

        $this->line('Loading backup files...');
        $this->line('');

        if ($total == 0) {
            $this->info("You have not any backup file");
        } elseif ($total == 1) {
            $this->table($headers, $backups);
            $this->line('');
            $this->info("There is 1 backup file found from your request");
        } else {
            $this->table($headers, $backups);
            $this->line('');
            $this->info("There are {$total} backup files found from your request");
        }
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
            array('latest', 'l', InputOption::VALUE_NONE, 'Only get latest version from backup files.')
        ];
    }
}
