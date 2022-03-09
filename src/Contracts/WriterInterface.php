<?php

namespace Jackiedo\DotenvEditor\Contracts;

interface WriterInterface
{
    /**
     * Load current content into buffer.
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
     */
    public function appendComment(string $comment);

    /**
     * Append one setter to buffer.
     */
    public function appendSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false);

    /**
     * Update one setter in buffer.
     */
    public function updateSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false);

    /**
     * Delete one setter in buffer.
     */
    public function deleteSetter(string $key);

    /**
     * Save buffer to special file.
     */
    public function saveTo(string $filePath);
}
