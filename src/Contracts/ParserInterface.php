<?php

namespace Jackiedo\DotenvEditor\Contracts;

interface ParserInterface
{
    /**
     * Parse dotenv file content into separate entries.
     *
     * This will produce an array of entries, each entry
     * being an informational array of starting line and raw data.
     *
     * @param string $filePath The path to dotenv file
     *
     * @return array
     */
    public function parseFile(string $filePath);

    /**
     * Parses an entry data into an array of type, export allowed or not,
     * key, value, and comment information.
     *
     * @param string $data The entry data
     *
     * @return array
     */
    public function parseEntry(string $data);
}
