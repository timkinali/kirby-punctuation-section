<?php

return [
    'punctuation' => array_replace_recursive(
        include \Kirby\Cms\Section::$types['info'],
        [
            'props' => [
                'fieldsets' => fn ($value = []) => $value,
                'clipboard' => fn ($value = false) => $value,
                'open' => fn ($value = false) => $value,
                /* 'instructions' => function ($value = 'hej') {
                    return $value;
                } */
            ]
        ]
    )
];
