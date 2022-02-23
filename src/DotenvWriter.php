<?php

namespace Jackiedo\DotenvEditor;

use Jackiedo\DotenvEditor\Contracts\FormatterInterface;
use Jackiedo\DotenvEditor\Contracts\WriterInterface;
use Jackiedo\DotenvEditor\Exceptions\UnableWriteToFileException;

/**
 * The DotenvWriter writer.
 *
 * @package Jackiedo\DotenvEditor
 * @author Jackie Do <anhvudo@gmail.com>
 */
class DotenvWriter implements WriterInterface
{
    /**
     * The content buffer
     *
     * @var array
     */
    protected $buffer;

    /**
     * The instance of Formatter
     *
     * @var \Jackiedo\DotenvEditor\Workers\Formatters\Formatter
     */
    protected $formatter;

    /**
     * Create a new writer instance
     *
     * @param FormatterInterface $formatter
     */
    public function __construct(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Set buffer with content
     *
     * @param array $content
     *
     * @return DotenvWriter
     */
    public function setBuffer(array $content = [])
    {
        $this->buffer = $content;

        return $this;
    }

    /**
     * Return content in buffer
     *
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Append new line to buffer
     *
     * @param string|null $content
     *
     * @return DotenvWriter
     */
    protected function appendLine(?string $content = null)
    {
        $this->buffer[] = [
            'line'     => null,
            'raw_data' => $content
        ];

        return $this;
    }

    /**
     * Append empty line to buffer
     *
     * @return DotenvWriter
     */
    public function appendEmptyLine()
    {
        return $this->appendLine();
    }

    /**
     * Append comment line to buffer
     *
     * @param  string $comment
     *
     * @return DotenvWriter
     */
    public function appendCommentLine(string $comment)
    {
        $content = $this->formatter->formatComment($comment);

        return $this->appendLine($content);
    }

    /**
     * Append one setter to buffer
     *
     * @param  string       $key
     * @param  string|null  $value
     * @param  string|null  $comment
     * @param  boolean      $export
     *
     * @return DotenvWriter
     */
    public function appendSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false)
    {
        $content = $this->formatter->formatSetter($key, $value, $comment, $export);

        return $this->appendLine($content);
    }

    /**
     * Update one setter in buffer
     *
     * @param  string       $key
     * @param  string|null  $value
     * @param  string|null  $comment
     * @param  boolean      $export
     *
     * @return DotenvWriter
     */
    public function updateSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false)
    {
        $content = $this->formatter->formatSetter($key, $value, $comment, $export);
        $pattern = "/^(export\h)?\h*{$key}\h*=.*/";

        array_walk($this->buffer, function (&$entry, $index) use ($pattern, $content) {
            if (preg_match($pattern, $entry['raw_data']) === 1) {
                $entry['raw_data'] = $content;
            }
        });

        return $this;
    }

    /**
     * Delete one setter in buffer
     *
     * @param  string $key
     *
     * @return DotenvWriter
     */
    public function deleteSetter(string $key)
    {
        $pattern = "/^(export\h)?\h*{$key}\h*=.*/";

        $this->buffer = array_values(array_filter($this->buffer, function ($entry, $index) use ($pattern) {
                return preg_match($pattern, $entry['raw_data']) === 0;
            }, ARRAY_FILTER_USE_BOTH));

        return $this;
    }

    /**
     * Save buffer to special file path
     *
     * @param  string $filePath
     *
     * @return DotenvWriter
     */
    public function save(string $filePath)
    {
        $this->ensureFileIsWritable($filePath);

        $data = array_map(function ($entry) {
            return $entry['raw_data'];
        }, $this->buffer);

        $data = implode(PHP_EOL, $data) . PHP_EOL;

        file_put_contents($filePath, $data);

        return $this;
    }

    /**
     * Tests file for writability. If the file doesn't exist, check
     * the parent directory for writability so the file can be created.
     *
     * @throws UnableWriteToFileException
     *
     * @return void
     */
    protected function ensureFileIsWritable($filePath)
    {
        if ((is_file($filePath) && !is_writable($filePath)) || (!is_file($filePath) && !is_writable(dirname($filePath)))) {
            throw new UnableWriteToFileException(sprintf('Unable to write to the file at %s.', $filePath));
        }
    }
}
