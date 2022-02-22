<?php

namespace Jackiedo\DotenvEditor\Contracts;

interface DotenvParser
{
    /**
     * Parse dotenv file content into separate entries.
     *
     * This will produce an array of entries, each entry
     * being an informational array of starting line and raw data.
     *
     * @param string $filePath
     *
     * @return array
     */
    public function parseFile($filePath);

    /**
     * Parses an entry into an array of type, export allowed or not,
     * key, value, and comment information
     *
     * @param string $entry
     *
     * @return array
     */
    public function parseEntry($entry);
}
