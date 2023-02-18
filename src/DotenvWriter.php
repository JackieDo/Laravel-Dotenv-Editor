<?php

namespace Jackiedo\DotenvEditor;

use Jackiedo\DotenvEditor\Contracts\FormatterInterface;
use Jackiedo\DotenvEditor\Contracts\WriterInterface;
use Jackiedo\DotenvEditor\Exceptions\UnableWriteToFileException;

/**
 * The DotenvWriter writer.
 *
 * @package Jackiedo\DotenvEditor
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
class DotenvWriter implements WriterInterface
{
    /**
     * The content buffer.
     *
     * @var array
     */
    protected $buffer;

    /**
     * The instance of Formatter.
     *
     * @var \Jackiedo\DotenvEditor\Workers\Formatters\Formatter
     */
    protected $formatter;

    /**
     * New entry template.
     *
     * @var array
     */
    protected $entryTemplate = [
        'line'    => null,
        'type'    => 'empty',
        'export'  => false,
        'key'     => '',
        'value'   => '',
        'comment' => '',
    ];

    /**
     * Create a new writer instance.
     *
     * @param FormatterInterface $formatter
     */
    public function __construct(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Set buffer with content.
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
     * Return content in buffer.
     *
     * @param bool $asArray Use array format for the result
     *
     * @return array|string
     */
    public function getBuffer(bool $asArray = true)
    {
        if ($asArray) {
            return $this->buffer;
        }

        return $this->buildTextContent();
    }

    /**
     * Append empty line to buffer.
     *
     * @return DotenvWriter
     */
    public function appendEmpty()
    {
        return $this->appendEntry([]);
    }

    /**
     * Append comment line to buffer.
     *
     * @param string $comment
     *
     * @return DotenvWriter
     */
    public function appendComment(string $comment)
    {
        return $this->appendEntry([
            'type'    => 'comment',
            'comment' => (string) $comment,
        ]);
    }

    /**
     * Append one setter to buffer.
     *
     * @param string      $key
     * @param null|string $value
     * @param null|string $comment
     * @param bool        $export
     *
     * @return DotenvWriter
     */
    public function appendSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false)
    {
        return $this->appendEntry([
            'type'    => 'setter',
            'export'  => $export,
            'key'     => (string) $key,
            'value'   => (string) $value,
            'comment' => (string) $comment,
        ]);
    }

    /**
     * Update the setter data in buffer.
     *
     * @param string      $key
     * @param null|string $value
     * @param null|string $comment
     * @param bool        $export
     *
     * @return DotenvWriter
     */
    public function updateSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false)
    {
        $data = [
            'export'  => $export,
            'value'   => (string) $value,
            'comment' => (string) $comment,
        ];

        array_walk($this->buffer, function (&$entry, $index) use ($key, $data) {
            if ('setter' == $entry['type'] && $entry['key'] == $key) {
                $entry = array_merge($entry, $data);
            }
        });

        return $this;
    }

    /**
     * Update comment for the setter in buffer.
     *
     * @param string      $key
     * @param null|string $comment
     *
     * @return DotenvWriter
     */
    public function updateSetterComment(string $key, ?string $comment = null)
    {
        $data = [
            'comment' => (string) $comment,
        ];

        array_walk($this->buffer, function (&$entry, $index) use ($key, $data) {
            if ('setter' == $entry['type'] && $entry['key'] == $key) {
                $entry = array_merge($entry, $data);
            }
        });

        return $this;
    }

    /**
     * Update export status for the setter in buffer.
     *
     * @param string $key
     * @param bool   $state
     *
     * @return DotenvWriter
     */
    public function updateSetterExport(string $key, bool $state)
    {
        $data = [
            'export' => $state,
        ];

        array_walk($this->buffer, function (&$entry, $index) use ($key, $data) {
            if ('setter' == $entry['type'] && $entry['key'] == $key) {
                $entry = array_merge($entry, $data);
            }
        });

        return $this;
    }

    /**
     * Delete one setter in buffer.
     *
     * @param string $key
     *
     * @return DotenvWriter
     */
    public function deleteSetter(string $key)
    {
        $this->buffer = array_values(array_filter($this->buffer, function ($entry, $index) use ($key) {
            return 'setter' != $entry['type'] || $entry['key'] != $key;
        }, ARRAY_FILTER_USE_BOTH));

        return $this;
    }

    /**
     * Save buffer to special file.
     *
     * @param string $filePath
     *
     * @return DotenvWriter
     */
    public function saveTo(string $filePath)
    {
        $this->ensureFileIsWritable($filePath);
        file_put_contents($filePath, $this->buildTextContent());

        return $this;
    }

    /**
     * Append new line to buffer.
     *
     * @param array $data
     *
     * @return DotenvWriter
     */
    protected function appendEntry(array $data = [])
    {
        $this->buffer[] = array_merge($this->entryTemplate, $data);

        return $this;
    }

    /**
     * Tests file for writability. If the file doesn't exist, check
     * the parent directory for writability so the file can be created.
     *
     * @param mixed $filePath
     *
     * @return void
     *
     * @throws UnableWriteToFileException
     */
    protected function ensureFileIsWritable($filePath)
    {
        if ((is_file($filePath) && !is_writable($filePath)) || (!is_file($filePath) && !is_writable(dirname($filePath)))) {
            throw new UnableWriteToFileException(sprintf('Unable to write to the file at %s.', $filePath));
        }
    }

    /**
     * Build plain text content from buffer.
     *
     * @return string
     */
    protected function buildTextContent()
    {
        $data = array_map(function ($entry) {
            if ('setter' == $entry['type']) {
                return $this->formatter->formatSetter($entry['key'], $entry['value'], $entry['comment'], $entry['export']);
            }

            if ('comment' == $entry['type']) {
                return $this->formatter->formatComment($entry['comment']);
            }

            return '';
        }, $this->buffer);

        return implode(PHP_EOL, $data) . PHP_EOL;
    }
}
