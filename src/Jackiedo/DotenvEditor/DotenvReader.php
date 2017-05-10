<?php namespace Jackiedo\DotenvEditor;

use Jackiedo\DotenvEditor\Contracts\DotenvFormatter as DotenvFormatterContract;
use Jackiedo\DotenvEditor\Contracts\DotenvReader as DotenvReaderContract;
use Jackiedo\DotenvEditor\Exceptions\UnableReadFileException;

/**
 * The .env reader.
 *
 * @package Jackiedo\DotenvEditor
 * @author Jackie Do <anhvudo@gmail.com>
 */
class DotenvReader implements DotenvReaderContract
{
    /**
     * The file path
     *
     * @var string
     */
    protected $filePath;

    /**
     * Instance of Jackiedo\DotenvEditor\DotenvFormatter
     *
     * @var object
     */
    protected $formatter;

    /**
     * Create a new reader instance
     *
     * @param \Jackiedo\DotenvEditor\Contracts\DotenvFormatter $formatter
     * @param string|null                                      $filePath
     */
    public function __construct(DotenvFormatterContract $formatter, $filePath = null) {
    	$this->formatter = $formatter;

        if (!is_null($filePath)) {
            $this->load($filePath);
        }
    }

    /**
     * Load .env file
     *
     * @param  string $filePath
     *
     * @return DotenvReader
     */
    public function load($filePath) {
        $this->filePath = $filePath;
        return $this;
    }

    /**
     * Ensures the given filePath is readable.
     *
     * @throws \Jackiedo\DotenvEditor\Exceptions\UnableReadFileException
     *
     * @return void
     */
    protected function ensureFileIsReadable() {
        if (!is_readable($this->filePath) || !is_file($this->filePath)) {
            throw new UnableReadFileException(sprintf('Unable to read the file at %s.', $this->filePath));
        }
    }

    /**
     * Get content of .env file
     *
     * @return string
     */
    public function content() {
        $this->ensureFileIsReadable();

        return file_get_contents($this->filePath);
    }

    /**
     * Get all lines informations from content of .env file
     *
     * @return array
     */
    public function lines() {
        $content = [];
        $lines   = $this->readLinesFromFile();

        foreach($lines as $row => $line){
            $data = [
                'line'        => $row+1,
                'raw_data'    => $line,
                'parsed_data' => $this->formatter->parseLine($line)
            ];

            $content[] = $data;
        }

        return $content;
    }

    /**
     * Get all key informations in .env file
     *
     * @return array
     */
    public function keys() {
        $content = [];
        $lines   = $this->readLinesFromFile();

    	foreach($lines as $row => $line){
            $data = $this->formatter->parseLine($line);
            if ($data['type'] == 'setter') {
                $content[$data['key']] = [
                    'line'    => $row+1,
                    'export'  => $data['export'],
                    'value'   => $data['value'],
                    'comment' => $data['comment']
                ];
            }
    	}

    	return $content;
    }

    /**
     * Read content into an array of lines with auto-detected line endings
     *
     * @return array
     */
    protected function readLinesFromFile() {
        $this->ensureFileIsReadable();

        $autodetect = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
        $lines = file($this->filePath, FILE_IGNORE_NEW_LINES);
        ini_set('auto_detect_line_endings', $autodetect);

        return $lines;
    }
}
