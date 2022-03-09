<?php

namespace Jackiedo\DotenvEditor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * The DotenvEditor facade.
 *
 * @package Jackiedo\DotenvEditor\Facades
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
class DotenvEditor extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'dotenv-editor';
    }
}
