<?php

namespace Jackiedo\DotenvEditor\Workers\Formatters;

use Jackiedo\DotenvEditor\Contracts\FormatterInterface;
use Jackiedo\DotenvEditor\Exceptions\InvalidKeyException;

/**
 * The .env formatter.
 *
 * @package Jackiedo\DotenvEditor
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
class Formatter implements FormatterInterface
{
    /**
     * Formatting the key of setter to writing.
     *
     * @param string $key
     * @param bool   $export optional
     *
     * @return string
     */
    public function formatKey(string $key, bool $export = false)
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
     * Formatting the comment to writing.
     *
     * @param ?string $comment
     *
     * @return string
     */
    public function formatComment(?string $comment)
    {
        $comment = rtrim(ltrim((string) $comment, '# '), ' ');
        $comment = preg_replace('/(\r\n|\n|\r)/', ' ', $comment);

        return (strlen($comment) > 0) ? "# {$comment}" : '';
    }

    /**
     * Build an setter from the individual components for writing.
     *
     * @param string      $key
     * @param null|string $value   optional
     * @param null|string $comment optional
     * @param bool        $export  optional
     *
     * @return string
     */
    public function formatSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false)
    {
        $key   = $this->formatKey($key, $export);
        $value = $this->formatValue($value, $this->formatComment($comment));

        return "{$key}={$value}";
    }

    /**
     * Formatting the value of setter to writing.
     *
     * @param string      $value   optional
     * @param null|string $comment optional
     *
     * @return string
     */
    protected function formatValue(?string $value, ?string $comment = null)
    {
        $value       = (string) $value;
        $comment     = (string) $comment;
        $hasComment  = strlen($comment) > 0;
        $forceQuotes = $hasComment && (0 == strlen($value));

        if ($forceQuotes || 1 === preg_match('/[#\s"\'\\\\]|\$\{[a-zA-Z0-9_.]+\}|\\\\n/', $value)) {
            $value = str_replace('\\', '\\\\', $value);
            $value = str_replace('"', '\"', $value);
            $value = "\"{$value}\"";
        }

        return $value . ($hasComment ? " {$comment}" : '');
    }

    /**
     * Determine if the input string is valid key.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function isValidKey(string $key)
    {
        return 1 === preg_match('/\A[a-zA-Z0-9_.]+\z/', $key);
    }
}
