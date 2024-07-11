<?php

set_time_limit(0);

use Illuminate\Support\Str;

if (!function_exists('convertStringToUtf8')) {
    function convertStringToUtf8($string)
    {
        return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('getSlug')) {
    function getSlug($string, $partern = '-')
    {
        $string = convertStringToUtf8($string);
        $string = Str::slug($string, $partern);
        $string = str_replace(['_', '+', ' ', '|'], $partern, $string);

        return $string;
    }
}

if (!function_exists('removeTagsInHtml')) {
    function removeTagsInHtml($tagsName, $html)
    {
        if (is_array($tagsName)) {
            foreach ($tagsName as $tag) {
                $pattern = '/\<[\/]{0,1}' . $tag . '[^\>]*\>/i';
                $html = preg_replace($pattern, '', $html);
            }
        } else {
            $pattern = '/\<[\/]{0,1}' . $tagsName . '[^\>]*\>/i';
            $html = preg_replace($pattern, '', $html);
        }

        return $html;
    }
}
