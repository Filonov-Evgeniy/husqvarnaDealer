<?php

if (!function_exists('s_url_code')) {

    function s_url_code(string $name): string
    {
        return CUtil::translit(
            $name,
            'en',
            array(
                'max_len' => 100,
                'change_case' => 'L',
                'replace_space' => '-',
                'replace_other' => '-',
                'delete_repeat_replace' => true
            )
        );
    }
}