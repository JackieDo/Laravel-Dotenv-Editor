<?php  namespace Jackiedo\DotenvEditor\Console\Commands;

use Illuminate\Console\Command;
use Jackiedo\DotenvEditor\Console\Traits\CreateCommandInstanceTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DotenvGetKeysCommand extends Command
{
    use CreateCommandInstanceTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dotenv:get-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all setter in the .env file';

    /**
     * The .env file path
     *
     * @var string|null
     */
    protected $filePath = null;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $filePath       = $this->stringToType($this->option('filepath'));
        $this->filePath = (is_string($filePath)) ? base_path($filePath) : null;

        $allKeys = $this->editor->load($this->filePath)->getKeys();
        $output = [];
        foreach ($allKeys as $key => $info) {
            $data = [
                'key'     => $key,
                'export'  => ($info['export']) ? 'true' : 'false',
                'value'   => $info['value'],
                'comment' => $info['comment'],
                'line'    => $info['line']
            ];
            $output[] = $data;
        }

        $total   = count($output);

        $headers = ['Key', 'Use export', 'Value', 'Comment', 'In line'];

        $this->line('Loading keys in your file...');
        $this->line('');
        $this->table($headers, $output);
        $this->line('');
        $this->info("You have total {$total} keys in your file");
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
            array('filepath', null, InputOption::VALUE_OPTIONAL, 'The file path should use to load for working. Do not use if you want to load file .env at root application folder.')
        ];
    }
}
