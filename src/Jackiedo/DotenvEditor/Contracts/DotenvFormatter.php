<?php namespace Jackiedo\DotenvEditor\Contracts;

interface DotenvFormatter
{
    /**
     * Formatting the key of setter to writing
     *
     * @param 	string	$key
     */
    public function formatKey($key);

    /**
     * Formatting the value of setter to writing
     *
     * @param 	string	$value
     * @param 	bool	$forceQuotes
     */
    public function formatValue($value, $forceQuotes = false);

    /**
     * Formatting the comment to writing
     *
     * @param  string $comment
     */
    public function formatComment($comment);

    /**
     * Build an setter line from the individual components for writing
     *
     * @param string		$key
     * @param string|null	$value
     * @param string|null	$comment optional
     * @param bool			$export optional
     */
    public function formatSetterLine($key, $value = null, $comment = null, $export = false);

    /**
     * Normalising the key of setter to reading
     *
     * @param  string $key
     */
    public function normaliseKey($key);

    /**
     * Normalising the value of setter to reading
     *
     * @param  string $value
     * @param  string $quote
     */
    public function normaliseValue($value, $quote = '');

    /**
     * Normalising the comment to reading
     *
     * @param  string $comment
     */
    public function normaliseComment($comment);

    /**
     * Parse a line into an array of type, export, key, value and comment
     *
     * @param  string $line
     */
    public function parseLine($line);
}
