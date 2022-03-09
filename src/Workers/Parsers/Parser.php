<?php

namespace Jackiedo\DotenvEditor\Workers\Parsers;

use Jackiedo\DotenvEditor\Exceptions\InvalidValueException;

/**
 * The Parser abstract.
 *
 * @package Jackiedo\DotenvEditor
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
abstract class Parser
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
    public function parseFile(string $filePath)
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES); // The older method
        // $lines = preg_split("/(\r\n|\n|\r)/", rtrim(@file_get_contents($filePath))); // The newer method

        $output          = [];
        $multiline       = false;
        $multilineBuffer = [];
        $lineNumber      = 0;

        foreach ($lines as $index => $line) {
            list($multiline, $line, $multilineBuffer) = self::multilineProcess($multiline, $line, $multilineBuffer);

            if (!$multiline) {
                $output[] = [
                    'line'     => ++$lineNumber,
                    'raw_data' => $line,
                ];

                $lineNumber = ++$index;
            }
        }

        return $output;
    }

    /**
     * Parses an entry data into an array of type, export allowed or not,
     * key, value, and comment information.
     *
     * @param string $data The entry data
     *
     * @return array
     */
    public function parseEntry(string $data)
    {
        $output = [
            'type'    => 'unknown',
            'export'  => false,
            'key'     => '',
            'value'   => '',
            'comment' => '',
        ];

        if ($this->isEmpty($data)) {
            $output['type'] = 'empty';

            return $output;
        }

        if ($this->isComment($data)) {
            $output['type']    = 'comment';
            $output['comment'] = $this->normaliseComment($data);

            return $output;
        }

        if ($this->looksLikeSetter($data)) {
            return $this->parseSetter($data);
        }

        return $output;
    }

    /**
     * Used to make all multiline variable process.
     *
     * @param bool     $multiline
     * @param string   $line
     * @param string[] $buffer
     *
     * @return array
     */
    protected function multilineProcess($multiline, $line, array $buffer)
    {
        // check if $line can be multiline variable
        if ($started = self::looksLikeMultilineStart($line)) {
            $multiline = true;
        }

        if ($multiline) {
            array_push($buffer, $line);

            if (self::looksLikeMultilineStop($line, $started)) {
                $multiline = false;
                $line      = implode(PHP_EOL, $buffer);
                $buffer    = [];
            }
        }

        return [$multiline, $line, $buffer];
    }

    /**
     * Determine if the given line can be the start of a multiline variable.
     *
     * @param string $line
     *
     * @return bool
     */
    protected function looksLikeMultilineStart($line)
    {
        if (false === strpos($line, '="')) {
            return false;
        }

        return false === self::looksLikeMultilineStop($line, true);
    }

    /**
     * Determine if the given line can be the start of a multiline variable.
     *
     * @param string $line
     * @param bool   $started
     *
     * @return bool
     */
    protected function looksLikeMultilineStop($line, $started)
    {
        if ('"' === $line) {
            return true;
        }

        $seen = $started ? 0 : 1;

        foreach (self::getCharPairs(str_replace('\\\\', '', $line)) as $pair) {
            if ('\\' !== $pair[0] && '"' === $pair[1]) {
                ++$seen;
            }
        }

        return $seen > 1;
    }

    /**
     * Get all pairs of adjacent characters within the line.
     *
     * @param string $line
     *
     * @return array
     */
    protected function getCharPairs($line)
    {
        $chars = str_split($line);

        return array_map(null, $chars, array_slice($chars, 1));
    }

    /**
     * Parses a setter into an array of type, export allowed or not,
     * key, value, and comment information.
     *
     * @param string $setter
     *
     * @return array
     */
    protected function parseSetter($setter)
    {
        list($key, $data) = array_map('trim', explode('=', $setter, 2));

        $output = [
            'type'    => 'setter',
            'export'  => $this->isExportKey($key),
            'key'     => $this->normaliseKey($key),
            'value'   => '',
            'comment' => '',
        ];

        list($output['value'], $output['comment']) = $this->parseSetterData($data);

        return $output;
    }

    /**
     * Normalising the key of setter to output.
     *
     * @return string
     */
    protected function normaliseKey(string $key)
    {
        return trim(str_replace(['export ', '\'', '"'], '', $key));
    }

    /**
     * Normalising the comment to output.
     *
     * @return string
     */
    protected function normaliseComment(string $comment)
    {
        return rtrim(ltrim($comment, '# '), ' ');
    }

    /**
     * Determine if the entry in the file is empty line.
     *
     * @return bool
     */
    protected function isEmpty(string $data)
    {
        return '' === trim($data);
    }

    /**
     * Determine if the entry in the file is a comment line, e.g. begins with a #.
     *
     * @return bool
     */
    protected function isComment(string $data)
    {
        $data = ltrim($data);

        return isset($data[0]) && '#' === $data[0];
    }

    /**
     * Determine if the given entry looks like it's setting a key.
     *
     * @return bool
     */
    protected function looksLikeSetter(string $data)
    {
        return false !== strpos($data, '=') && 0 !== strpos($data, '=');
    }

    /**
     * Determine if the given key begins with 'export '.
     *
     * @return bool
     */
    protected function isExportKey(string $key)
    {
        $pattern = '/^export\h.+$/';

        if (preg_match($pattern, trim($key))) {
            return true;
        }

        return false;
    }

    /**
     * Generate a friendly error message.
     *
     * @return string
     */
    protected function getErrorMessage(string $cause, string $subject)
    {
        return sprintf(
            'Failed to parse dotenv setter value due to %s. Failed at [%s].',
            $cause,
            strtok($subject, "\n")
        );
    }

    /**
     * Parse setter data into array of value, comment information.
     *
     * @param string $data
     *
     * @throws InvalidValueException
     *
     * @return array
     */
    abstract protected function parseSetterData($data);
}
