<?php  namespace Jackiedo\DotenvEditor\Console;

use Illuminate\Console\Command;
use Jackiedo\DotenvEditor\DotenvEditor;

class DotenvGetBackupsCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dotenv:get-backups
                            {--l|latest : Only get latest version from backup files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all the .env file backup versions';

    /**
     * The .env file editor instance
     *
     * @var \Jackiedo\DotenvEditor\DotenvEditor
     */
    protected $editor;

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

        $headers = ['File name', 'File path', 'Created at'];
        $backups = ($this->option('latest')) ? [$this->editor->getLatestBackup()] : $this->editor->getBackups();
        $total   = count($backups);

        $this->line('Loading backup files...');
        $this->line('');
        $this->table($headers, $backups);
        $this->line('');
        $this->info("You have total {$total} backup files");
    }
}
