<?php

if (!function_exists('restyle_path')) {
    /**
     * Standardize the paths to the current OS style.
     *
     * @param string $path the input path
     *
     * @return string
     */
    function restyle_path($path)
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}
