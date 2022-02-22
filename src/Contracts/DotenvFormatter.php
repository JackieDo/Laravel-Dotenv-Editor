<?php

namespace Jackiedo\DotenvEditor\Contracts;

interface DotenvFormatter
{
    /**
     * Formatting the key of setter to writing
     *
     * @param string  $key
     * @param boolean $export  optional
     */
    public function formatKey(string $key, $export = false);

    /**
     * Formatting the value of setter to writing
     *
     * @param string      $value
     * @param string|null $comment  optional
     */
    public function formatValue($value, $comment = null);

    /**
     * Formatting the comment to writing
     *
     * @param string $comment
     */
    public function formatComment($comment);

    /**
     * Build an setter from the individual components for writing
     *
     * @param string       $key
     * @param string|null  $value
     * @param string|null  $comment  optional
     * @param bool         $export   optional
     */
    public function formatSetter(string $key, $value = null, $comment = null, $export = false);
}
