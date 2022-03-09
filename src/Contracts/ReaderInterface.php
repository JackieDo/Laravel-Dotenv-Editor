<?php

namespace Jackiedo\DotenvEditor\Contracts;

interface ReaderInterface
{
    /**
     * Load .env file.
     *
     * @param string $filePath The path to dotenv file
     */
    public function load(?string $filePath);

    /**
     * Get content of .env file.
     */
    public function content();

    /**
     * Get informations of all entries from file content.
     *
     * @param bool $withParsedData Includes the parsed data in the result
     */
    public function entries(bool $withParsedData = false);

    /**
     * Get all key informations in .env file.
     */
    public function keys();
}
