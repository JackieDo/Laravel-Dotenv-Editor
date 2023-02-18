<?php

namespace Jackiedo\DotenvEditor;

use Jackiedo\DotenvEditor\Contracts\ParserInterface;
use Jackiedo\DotenvEditor\Contracts\ReaderInterface;
use Jackiedo\DotenvEditor\Exceptions\UnableReadFileException;

/**
 * The DotenvReader class.
 *
 * @package Jackiedo\DotenvEditor
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
class DotenvReader implements ReaderInterface
{
    /**
     * The file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * The instance of Parser.
     *
     * @var \Jackiedo\DotenvEditor\Workers\Parsers\Parser
     */
    protected $parser;

    /**
     * Create a new reader instance.
     *
     * @param ParserInterface $parser
     */
    public function __construct(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Load file.
     *
     * @param string $filePath The path to dotenv file
     *
     * @return DotenvReader
     */
    public function load(?string $filePath)
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * Get content of file.
     *
     * @return string
     */
    public function content()
    {
        $this->ensureFileIsReadable();

        return file_get_contents($this->filePath);
    }

    /**
     * Get informations of all entries from file content.
     *
     * @param bool $withParsedData Includes the parsed data in the result
     *
     * @return array
     */
    public function entries(bool $withParsedData = false)
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
     * Get informations of all keys from file content.
     *
     * @return array
     */
    public function keys()
    {
        $entries = $this->getEntriesFromFile();

        return array_reduce($entries, function ($carry, $entry) {
            $data = $this->parser->parseEntry($entry['raw_data']);

            if ('setter' == $data['type']) {
                $carry[$data['key']] = [
                    'line'    => $entry['line'],
                    'export'  => $data['export'],
                    'value'   => $data['value'],
                    'comment' => $data['comment'],
                ];
            }

            return $carry;
        }, []);
    }

    /**
     * Read content into an array of lines with auto-detected line endings.
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
     * @return void
     *
     * @throws UnableReadFileException
     */
    protected function ensureFileIsReadable()
    {
        if (!is_readable($this->filePath) || !is_file($this->filePath)) {
            throw new UnableReadFileException(sprintf('Unable to read the file at %s.', $this->filePath));
        }
    }
}
