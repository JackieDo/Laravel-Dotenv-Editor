<?php

namespace Jackiedo\DotenvEditor\Workers\Parsers;

use Jackiedo\DotenvEditor\Contracts\ParserInterface;
use Jackiedo\DotenvEditor\Exceptions\InvalidValueException;

/**
 * The reader parser V3 class.
 *
 * @package Jackiedo\DotenvEditor
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
class ParserV3 extends Parser implements ParserInterface
{
    private const INITIAL_STATE         = 0;
    private const UNQUOTED_STATE        = 1;
    private const SINGLE_QUOTED_STATE   = 2;
    private const DOUBLE_QUOTED_STATE   = 3;
    private const ESCAPE_SEQUENCE_STATE = 4;
    private const WHITESPACE_STATE      = 5;
    private const COMMENT_STATE         = 6;

    /**
     * Parse setter data into array of value, comment information.
     *
     * @param string $data
     *
     * @throws InvalidValueException
     *
     * @return array
     */
    protected function parseSetterData($data)
    {
        if (null === $data || '' === trim($data)) {
            return ['', ''];
        }

        $dataChars     = str_split($data);
        $parseInfoInit = ['', '', self::INITIAL_STATE]; // 1st element is value, 2nd element is comment, 3rd element is parsing state

        $result = array_reduce($dataChars, function ($parseInfo, $char) use ($data) {
            switch ($parseInfo[2]) {
                case self::INITIAL_STATE:
                    if ('\'' === $char) {
                        return [$parseInfo[0], $parseInfo[1], self::SINGLE_QUOTED_STATE];
                    }

                    if ('"' === $char) {
                        return [$parseInfo[0], $parseInfo[1], self::DOUBLE_QUOTED_STATE];
                    }

                    if ('#' === $char) {
                        return [$parseInfo[0], $parseInfo[1], self::COMMENT_STATE];
                    }

                    if ('$' === $char) {
                        return [$parseInfo[0] . $char, $parseInfo[1], self::UNQUOTED_STATE];
                    }

                        return [$parseInfo[0] . $char, $parseInfo[1], self::UNQUOTED_STATE];

                case self::UNQUOTED_STATE:
                    if ('#' === $char) {
                        return [$parseInfo[0], $parseInfo[1], self::COMMENT_STATE];
                    }

                    if (ctype_space($char)) {
                        return [$parseInfo[0], $parseInfo[1], self::WHITESPACE_STATE];
                    }

                    if ('$' === $char) {
                        return [$parseInfo[0] . $char, $parseInfo[1], self::UNQUOTED_STATE];
                    }

                        return [$parseInfo[0] . $char, $parseInfo[1], self::UNQUOTED_STATE];

                case self::SINGLE_QUOTED_STATE:
                    if ('\'' === $char) {
                        return [$parseInfo[0], $parseInfo[1], self::WHITESPACE_STATE];
                    }

                        return [$parseInfo[0] . $char, $parseInfo[1], self::SINGLE_QUOTED_STATE];

                case self::DOUBLE_QUOTED_STATE:
                    if ('"' === $char) {
                        return [$parseInfo[0], $parseInfo[1], self::WHITESPACE_STATE];
                    }

                    if ('\\' === $char) {
                        return [$parseInfo[0], $parseInfo[1], self::ESCAPE_SEQUENCE_STATE];
                    }

                    if ('$' === $char) {
                        return [$parseInfo[0] . $char, $parseInfo[1], self::DOUBLE_QUOTED_STATE];
                    }

                        return [$parseInfo[0] . $char, $parseInfo[1], self::DOUBLE_QUOTED_STATE];

                case self::ESCAPE_SEQUENCE_STATE:
                    if ('"' === $char || '\\' === $char) {
                        return [$parseInfo[0] . $char, $parseInfo[1], self::DOUBLE_QUOTED_STATE];
                    }

                    if ('$' === $char) {
                        return [$parseInfo[0] . $char, $parseInfo[1], self::DOUBLE_QUOTED_STATE];
                    }

                    if (in_array($char, ['f', 'n', 'r', 't', 'v'], true)) {
                        $first = $this->UTF8Substr($char, 0, 1);

                        return [$parseInfo[0] . stripcslashes('\\' . $first) . $this->UTF8Substr($char, 1), $parseInfo[1], self::DOUBLE_QUOTED_STATE];
                    }

                        throw new InvalidValueException(self::getErrorMessage('an unexpected escape sequence', $data));

                case self::WHITESPACE_STATE:
                    if ('#' === $char) {
                        return [$parseInfo[0], $parseInfo[1], self::COMMENT_STATE];
                    }

                    if (!ctype_space($char)) {
                        throw new InvalidValueException(self::getErrorMessage('unexpected whitespace', $data));
                    }

                        return [$parseInfo[0], $parseInfo[1], self::WHITESPACE_STATE];

                case self::COMMENT_STATE:
                    return [$parseInfo[0], $parseInfo[1] . $char, self::COMMENT_STATE];
            }
        }, $parseInfoInit);

        if (in_array($result[2], [
            self::SINGLE_QUOTED_STATE,
            self::DOUBLE_QUOTED_STATE,
            self::ESCAPE_SEQUENCE_STATE,
        ], true)) {
            throw new InvalidValueException(self::getErrorMessage('a missing closing quote', $data));
        }

        return [$result[0], $this->normaliseComment($result[1])];
    }

    /**
     * Grab the specified substring of the input.
     *
     * @return string
     */
    protected function UTF8Substr(string $input, int $start, int $length = null)
    {
        return mb_substr($input, $start, $length, 'UTF-8');
    }
}
