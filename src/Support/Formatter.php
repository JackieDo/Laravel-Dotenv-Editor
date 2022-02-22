<?php

namespace Jackiedo\DotenvEditor\Support;

use Jackiedo\DotenvEditor\Contracts\DotenvFormatter;
use Jackiedo\DotenvEditor\Exceptions\InvalidKeyException;

/**
 * The .env formatter.
 *
 * @package Jackiedo\DotenvEditor
 * @author Jackie Do <anhvudo@gmail.com>
 */
class Formatter implements DotenvFormatter
{
    /**
     * Formatting the key of setter to writing
     *
     * @param string  $key
     * @param boolean $export  optional
     *
     * @return string
     */
    public function formatKey(string $key, $export = false)
    {
        $key = trim(str_replace(['export ', '\'', '"'], '', (string) $key));

        if (!self::isValidKey($key)) {
            throw new InvalidKeyException(sprintf('There is an invalid setter key. Caught at [%s].', $key));
        }

        if ($export) {
            $key = 'export ' . $key;
        }

        return $key;
    }

    /**
     * Formatting the value of setter to writing
     *
     * @param string      $value
     * @param string|null $comment  optional
     *
     * @return string
     */
    public function formatValue($value, $comment = null)
    {
        $value       = (string) $value;
        $comment     = (string) $comment;
        $hasComment  = strlen($comment) > 0;
        $forceQuotes = $hasComment && (strlen($value) == 0);

        if ($forceQuotes || preg_match('/[#\s"\'\\\\]|\\\\n/', $value) === 1) {
            $value = str_replace('\\', '\\\\', $value);
            $value = str_replace('"', '\"', $value);
            $value = "\"{$value}\"";
        }

        $value = $value . ($hasComment ? " {$comment}" : "");

        return $value;
    }

    /**
     * Formatting the comment to writing
     *
     * @param string $comment
     *
     * @return string
     */
    public function formatComment($comment)
    {
        $comment = rtrim(ltrim((string) $comment, '# '), ' ');

        return (strlen($comment) > 0) ? "# {$comment}" : "";
    }

    /**
     * Build an setter from the individual components for writing
     *
     * @param string       $key
     * @param string|null  $value
     * @param string|null  $comment  optional
     * @param bool         $export   optional
     *
     * @return string
     */
    public function formatSetter(string $key, $value = null, $comment = null, $export = false)
    {
        $key   = $this->formatKey($key, $export);
        $value = $this->formatValue($value, $this->formatComment($comment));

        return "{$key}={$value}";
    }

    /**
     * Determine if the input string is valid key
     *
     * @param string $key
     *
     * @return boolean
     */
    protected function isValidKey(string $key)
    {
        return preg_match('/\A[a-zA-Z0-9_.]+\z/', $key) === 1;
    }
}
