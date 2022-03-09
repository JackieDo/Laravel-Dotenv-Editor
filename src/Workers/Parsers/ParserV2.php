<?php

namespace Jackiedo\DotenvEditor\Workers\Parsers;

use Jackiedo\DotenvEditor\Contracts\ParserInterface;
use Jackiedo\DotenvEditor\Exceptions\InvalidValueException;

/**
 * The reader parser V2 class.
 *
 * @package Jackiedo\DotenvEditor
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
class ParserV2 extends Parser implements ParserInterface
{
    public const INITIAL_STATE         = 0;
    public const UNQUOTED_STATE        = 1;
    public const SINGLE_QUOTED_STATE   = 2;
    public const DOUBLE_QUOTED_STATE   = 3;
    public const ESCAPE_SEQUENCE_STATE = 4;
    public const WHITESPACE_STATE      = 5;
    public const COMMENT_STATE         = 6;

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
                        return [$parseInfo[0] . stripcslashes('\\' . $char), $parseInfo[1], self::DOUBLE_QUOTED_STATE];
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
}
