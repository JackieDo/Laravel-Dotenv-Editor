<?php

namespace Jackiedo\DotenvEditor\Contracts;

interface WriterInterface
{
    /**
     * Load current content into buffer
     *
     * @param array $content
     */
    public function setBuffer(array $content);

    /**
     * Return content in buffer
     */
    public function getBuffer();

    /**
     * Append empty line to buffer
     */
    public function appendEmptyLine();

    /**
     * Append comment line to buffer
     *
     * @param  string $comment
     */
    public function appendCommentLine(string $comment);

    /**
     * Append one setter to buffer
     *
     * @param  string		$key
     * @param  string|null	$value
     * @param  string|null	$comment
     * @param  boolean		$export
     */
    public function appendSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false);

    /**
     * Update one setter in buffer
     *
     * @param  string		$key
     * @param  string|null	$value
     * @param  string|null	$comment
     * @param  boolean		$export
     */
    public function updateSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false);

    /**
     * Delete one setter in buffer
     *
     * @param  string $key
     *
     * @return object
     */
    public function deleteSetter(string $key);

    /**
     * Save buffer to special file path
     *
     * @param  string $filePath
     */
    public function save(string $filePath);
}
