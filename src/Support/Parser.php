<?php

namespace Jackiedo\DotenvEditor\Support;

use Jackiedo\DotenvEditor\Contracts\DotenvParser;
use Jackiedo\DotenvEditor\Exceptions\InvalidValueException;

/**
 * The DotenPerser class.
 *
 * @package Jackiedo\DotenvEditor
 * @author Jackie Do <anhvudo@gmail.com>
 */
class Parser implements DotenvParser
{
    const INITIAL_STATE    = 0;
    const UNQUOTED_STATE   = 1;
    const QUOTED_STATE     = 2;
    const ESCAPE_STATE     = 3;
    const WHITESPACE_STATE = 4;
    const COMMENT_STATE    = 5;

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
    public function parseFile($filePath)
    {
        $lines = preg_split("/(\r\n|\n|\r)/", rtrim(@file_get_contents($filePath)));
        // $lines = file($filePath, FILE_IGNORE_NEW_LINES); // The older method

        $output          = [];
        $multiline       = false;
        $multilineBuffer = [];
        $lineNumber      = 0;

        foreach ($lines as $index => $line) {
            list($multiline, $line, $multilineBuffer) = self::multilineProcess($multiline, $line, $multilineBuffer);

            if (!$multiline) {
                $output[] = [
                    'line'     => ++$lineNumber,
                    'raw_data' => $line
                ];

                $lineNumber = ++$index;
            }
        }

        return $output;
    }

    /**
     * Used to make all multiline variable process.
     *
     * @param boolean  $multiline
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
        if (strpos($line, '="') === false) {
            return false;
        }

        return self::looksLikeMultilineStop($line, true) === false;
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
        if ($line === '"') {
            return true;
        }

        $seen = $started ? 0 : 1;

        foreach (self::getCharPairs(str_replace('\\\\', '', $line)) as $pair) {
            if ($pair[0] !== '\\' && $pair[1] === '"') {
                $seen++;
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
     * Parses an entry into an array of type, export allowed or not,
     * key, value, and comment information
     *
     * @param string $entry
     *
     * @return array
     */
    public function parseEntry($entry)
    {
        $output = [
            'type'    => 'unknown',
            'export'  => false,
            'key'     => '',
            'value'   => '',
            'comment' => '',
        ];

        if ($this->isEmpty($entry)) {
            $output['type'] = 'empty';

            return $output;
        }

        if ($this->isComment($entry)) {
            $output['type']    = 'comment';
            $output['comment'] = $this->normaliseComment($entry);

            return $output;
        }

        if ($this->looksLikeSetter($entry)) {
            return $this->parseSetter($entry);
        }

        return $output;
    }

    /**
     * Parses a setter into an array of type, export allowed or not,
     * key, value, and comment information
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
     * Parse setter data into array of value, comment information
     *
     * @param string $data
     * @throws InvalidValueException
     *
     * @return array
     */
    protected function parseSetterData($data)
    {
        if ($data === null || trim($data) === '') {
            return ['', ''];
        }

        $dataChars     = str_split($data);
        $parseInfoInit = ['', '', self::INITIAL_STATE]; // 1st element is value, 2nd element is comment, 3rd element is parsing state

        $result = array_reduce($dataChars, function ($parseInfo, $char) use ($data) {
            switch ($parseInfo[2]) {
                case self::INITIAL_STATE:
                    if ($char === '"' || $char === '\'') {
                        return [$parseInfo[0], $parseInfo[1], self::QUOTED_STATE];
                    } elseif ($char === '#') {
                        return [$parseInfo[0], $parseInfo[1], self::COMMENT_STATE];
                    } else {
                        return [$parseInfo[0].$char, $parseInfo[1], self::UNQUOTED_STATE];
                    }

                case self::UNQUOTED_STATE:
                    if ($char === '#') {
                        return [$parseInfo[0], $parseInfo[1], self::COMMENT_STATE];
                    } elseif (ctype_space($char)) {
                        return [$parseInfo[0], $parseInfo[1], self::WHITESPACE_STATE];
                    } else {
                        return [$parseInfo[0].$char, $parseInfo[1], self::UNQUOTED_STATE];
                    }

                case self::QUOTED_STATE:
                    if ($char === $data[0]) {
                        return [$parseInfo[0], $parseInfo[1], self::WHITESPACE_STATE];
                    } elseif ($char === '\\') {
                        return [$parseInfo[0], $parseInfo[1], self::ESCAPE_STATE];
                    } else {
                        return [$parseInfo[0].$char, $parseInfo[1], self::QUOTED_STATE];
                    }

                case self::ESCAPE_STATE:
                    if ($char === $data[0] || $char === '\\') {
                        return [$parseInfo[0].$char, $parseInfo[1], self::QUOTED_STATE];
                    } elseif (in_array($char, ['f', 'n', 'r', 't', 'v'], true)) {
                        return [$parseInfo[0].stripcslashes('\\'.$char), $parseInfo[1], self::QUOTED_STATE];
                    } else {
                        throw new InvalidValueException(self::getErrorMessage('an unexpected escape sequence', $data));
                    }

                case self::WHITESPACE_STATE:
                    if ($char === '#') {
                        return [$parseInfo[0], $parseInfo[1], self::COMMENT_STATE];
                    } elseif (!ctype_space($char)) {
                        throw new InvalidValueException(self::getErrorMessage('unexpected whitespace', $data));
                    } else {
                        return [$parseInfo[0], $parseInfo[1], self::WHITESPACE_STATE];
                    }

                case self::COMMENT_STATE:
                    return [$parseInfo[0], $parseInfo[1].$char, self::COMMENT_STATE];
            }
        }, $parseInfoInit);

        if ($result[2] === self::QUOTED_STATE || $result[2] === self::ESCAPE_STATE) {
            throw new InvalidValueException(self::getErrorMessage('a missing closing quote', $data));
        }

        return [$result[0], $this->normaliseComment($result[1])];
    }

    /**
     * Normalising the key of setter to output
     *
     * @param string $key
     *
     * @return string
     */
    protected function normaliseKey($key)
    {
        return trim(str_replace(['export ', '\'', '"'], '', $key));
    }

    /**
     * Normalising the comment to output
     *
     * @param string $comment
     *
     * @return string
     */
    protected function normaliseComment($comment)
    {
        return rtrim(ltrim((string) $comment, '# '), ' ');
    }

    /**
     * Determine if the entry in the file is empty line
     *
     * @param string $line
     *
     * @return bool
     */
    protected function isEmpty($line)
    {
        return trim($line) === '';
    }

    /**
     * Determine if the entry in the file is a comment line, e.g. begins with a #.
     *
     * @param string $line
     *
     * @return bool
     */
    protected function isComment($line)
    {
        $line = ltrim($line);

        return isset($line[0]) && $line[0] === '#';
    }

    /**
     * Determine if the given line looks like it's setting a key.
     *
     * @param string $line
     *
     * @return bool
     */
    protected function looksLikeSetter($line)
    {
        return strpos($line, '=') !== false && strpos($line, '=') !== 0;
    }

    /**
     * Determine if the given key begins with 'export '
     *
     * @param string $key
     *
     * @return bool
     */
    protected function isExportKey($key)
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
     * @param string $cause
     * @param string $subject
     *
     * @return string
     */
    protected function getErrorMessage($cause, $subject)
    {
        return sprintf(
            'Failed to parse dotenv setter value due to %s. Failed at [%s].',
            $cause,
            strtok($subject, "\n")
        );
    }
}
