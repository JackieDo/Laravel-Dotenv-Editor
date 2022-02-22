<?php

namespace Jackiedo\DotenvEditor\Contracts;

interface DotenvReader
{
    /**
     * Load .env file
     *
     * @param  string $filePath
     */
    public function load($filePath);

    /**
     * Get content of .env file
     */
    public function content();

    /**
     * Get informations of all entries from file content
     *
     * @param boolean $withParsedData
     */
    public function entries($withParsedData = false);

    /**
     * Get all key informations in .env file
     */
    public function keys();
}
