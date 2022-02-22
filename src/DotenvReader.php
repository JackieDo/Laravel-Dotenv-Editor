<?php

namespace Jackiedo\DotenvEditor;

use Jackiedo\DotenvEditor\Contracts\DotenvParser;
use Jackiedo\DotenvEditor\Contracts\DotenvReader as DotenvReaderContract;
use Jackiedo\DotenvEditor\Exceptions\UnableReadFileException;
use Jackiedo\DotenvEditor\Support\Parser;

/**
 * The DotenvReader class.
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
     * Instance of Parser
     *
     * @var Parser
     */
    protected $parser;

    /**
     * Create a new reader instance
     *
     * @param Parser $parser
     */
    public function __construct(DotenvParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Load file
     *
     * @param string $filePath
     *
     * @return DotenvReader
     */
    public function load($filePath)
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * Get content of file
     *
     * @return string
     */
    public function content()
    {
        $this->ensureFileIsReadable();

        return file_get_contents($this->filePath);
    }

    /**
     * Get informations of all entries from file content
     *
     * @param boolean $withParsedData
     *
     * @return array
     */
    public function entries($withParsedData = false)
    {
        $entries = $this->getEntriesFromFile();

        if (!(bool) $withParsedData) {
            return $entries;
        }

        return array_map(function ($info) {
            $info['parsed_data'] = $this->parser->parseEntry($info['raw_data']);

            return $info;
        }, $entries);
    }

    /**
     * Get informations of all keys from file content
     *
     * @return array
     */
    public function keys()
    {
        $entries = $this->getEntriesFromFile();

        return array_reduce($entries, function ($carry, $entry) {
            $data = $this->parser->parseEntry($entry['raw_data']);

            if ($data['type'] == 'setter') {
                $carry[$data['key']] = [
                    'line'    => $entry['line'],
                    'export'  => $data['export'],
                    'value'   => $data['value'],
                    'comment' => $data['comment']
                ];
            }

            return $carry;
        }, []);
    }

    /**
     * Read content into an array of lines with auto-detected line endings
     *
     * @return array
     */
    protected function getEntriesFromFile()
    {
        $this->ensureFileIsReadable();

        return $this->parser->parseFile($this->filePath);
    }

    /**
     * Ensures the given file is readable.
     *
     * @throws UnableReadFileException
     *
     * @return void
     */
    protected function ensureFileIsReadable()
    {
        if (!is_readable($this->filePath) || !is_file($this->filePath)) {
            throw new UnableReadFileException(sprintf('Unable to read the file at %s.', $this->filePath));
        }
    }
}
