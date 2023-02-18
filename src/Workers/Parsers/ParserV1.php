<?php

namespace Jackiedo\DotenvEditor\Workers\Parsers;

use Jackiedo\DotenvEditor\Contracts\ParserInterface;
use Jackiedo\DotenvEditor\Exceptions\InvalidValueException;

/**
 * The reader parser V1 class.
 *
 * @package Jackiedo\DotenvEditor
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
class ParserV1 extends Parser implements ParserInterface
{
    public const INITIAL_STATE    = 0;
    public const UNQUOTED_STATE   = 1;
    public const QUOTED_STATE     = 2;
    public const ESCAPE_STATE     = 3;
    public const WHITESPACE_STATE = 4;
    public const COMMENT_STATE    = 5;

    /**
     * Parse setter data into array of value, comment information.
     *
     * @param null|string $data
     *
     * @return array
     *
     * @throws InvalidValueException
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
                    if ('"' === $char || '\'' === $char) {
                        return [$parseInfo[0], $parseInfo[1], self::QUOTED_STATE];
                    }

                    if ('#' === $char) {
                        return [$parseInfo[0], $parseInfo[1], self::COMMENT_STATE];
                    }

                    return [$parseInfo[0] . $char, $parseInfo[1], self::UNQUOTED_STATE];

                case self::UNQUOTED_STATE:
                    if ('#' === $char) {
                        return [$parseInfo[0], $parseInfo[1], self::COMMENT_STATE];
                    }

                    if (ctype_space($char)) {
                        return [$parseInfo[0], $parseInfo[1], self::WHITESPACE_STATE];
                    }

                    return [$parseInfo[0] . $char, $parseInfo[1], self::UNQUOTED_STATE];

                case self::QUOTED_STATE:
                    if ($char === $data[0]) {
                        return [$parseInfo[0], $parseInfo[1], self::WHITESPACE_STATE];
                    }

                    if ('\\' === $char) {
                        return [$parseInfo[0], $parseInfo[1], self::ESCAPE_STATE];
                    }

                    return [$parseInfo[0] . $char, $parseInfo[1], self::QUOTED_STATE];

                case self::ESCAPE_STATE:
                    if ($char === $data[0] || '\\' === $char) {
                        return [$parseInfo[0] . $char, $parseInfo[1], self::QUOTED_STATE];
                    }

                    if (in_array($char, ['f', 'n', 'r', 't', 'v'], true)) {
                        return [$parseInfo[0] . stripcslashes('\\' . $char), $parseInfo[1], self::QUOTED_STATE];
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
            self::QUOTED_STATE,
            self::ESCAPE_STATE,
        ], true)) {
            throw new InvalidValueException(self::getErrorMessage('a missing closing quote', $data));
        }

        return [$result[0], $this->normaliseComment($result[1])];
    }
}
