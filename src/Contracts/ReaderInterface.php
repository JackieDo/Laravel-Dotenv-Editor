<?php

namespace Jackiedo\DotenvEditor\Contracts;

interface ReaderInterface
{
    /**
     * Load .env file
     *
     * @param string|null $filePath
     */
    public function load(?string $filePath);

    /**
     * Get content of .env file
     */
    public function content();

    /**
     * Get informations of all entries from file content
     *
     * @param boolean $withParsedData
     */
    public function entries(bool $withParsedData = false);

    /**
     * Get all key informations in .env file
     */
    public function keys();
}
