<?php

namespace Jackiedo\DotenvEditor\Contracts;

interface WriterInterface
{
    /**
     * Load current content into buffer.
     *
     * @param array $content
     */
    public function setBuffer(array $content);

    /**
     * Return content in buffer.
     *
     * @param bool $asArray Use array format for the result
     *
     * @return array|string
     */
    public function getBuffer(bool $asArray = true);

    /**
     * Append empty line to buffer.
     */
    public function appendEmpty();

    /**
     * Append comment line to buffer.
     *
     * @param string $comment
     */
    public function appendComment(string $comment);

    /**
     * Append one setter to buffer.
     *
     * @param string      $key
     * @param null|string $value
     * @param null|string $comment
     * @param bool        $export
     */
    public function appendSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false);

    /**
     * Update one setter in buffer.
     *
     * @param string      $key
     * @param null|string $value
     * @param null|string $comment
     * @param bool        $export
     */
    public function updateSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false);

    /**
     * Delete one setter in buffer.
     *
     * @param string $key
     */
    public function deleteSetter(string $key);

    /**
     * Save buffer to special file.
     *
     * @param string $filePath
     */
    public function saveTo(string $filePath);
}
