<?php

namespace Jackiedo\DotenvEditor\Contracts;

interface FormatterInterface
{
    /**
     * Formatting the key of setter to writing.
     *
     * @param string $key
     * @param bool   $export optional
     *
     * @return string
     */
    public function formatKey(string $key, bool $export = false);

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
    public function formatSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false);

    /**
     * Formatting the comment to writing.
     *
     * @param string $comment
     *
     * @return string
     */
    public function formatComment(?string $comment);
}
