<?php

return [
    'flat' => [
        'shadow' => false,
    ],

    'typo' => [
        'ru' => [
            'first' => [
                'й', 'ц', 'у', 'к', 'е', 'н', 'г', 'ш', 'щ', 'з',
                'ф', 'ы', 'в', 'а', 'п', 'р', 'о', 'л', 'д',
                'я', 'ч', 'с', 'м', 'и', 'т', 'ь',

                'Й', 'Ц', 'У', 'К', 'Е', 'Н', 'Г', 'Ш', 'Щ', 'З',
                'Ф', 'Ы', 'В', 'А', 'П', 'Р', 'О', 'Л', 'Д',
                'Я', 'Ч', 'С', 'М', 'И', 'Т', 'Ь',
            ],
            'second' => [
                'е', 'Е',
                'х', 'ъ', 'Х', 'Ъ',
                'ж', 'э', 'Ж', 'Э',
                'б', 'ю', 'Б', 'Ю',
            ],
        ],

        'en' => [
            'first' => [
                'q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p',
                'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l',
                'z', 'x', 'c', 'v', 'b', 'n', 'm',

                'Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P',
                'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L',
                'Z', 'X', 'C', 'V', 'B', 'N', 'M',
            ],
            'second' => [
                '`', '~',
                '[', ']', '{', '}',
                ';', '\'', ':', '"',
                ',', '.', '<', '>',
            ],
        ],
    ],

    'similar' => [
        'ru' => [
            'first' => [
                'У', 'К', 'Е', 'Н', 'Х', 'В', 'А', 'Р', 'О', 'С', 'М', 'Т',
                'у', 'к', 'е', 'н', 'х', 'в', 'а', 'р', 'о', 'с', 'м', 'т',
            ],
            'second' => [],
        ],
        'en' => [
            'first' => [
                'Y', 'K', 'E', 'H', 'X', 'B', 'A', 'P', 'O', 'C', 'M', 'T',
                'y', 'k', 'e', 'h', 'x', 'b', 'a', 'p', 'o', 'c', 'm', 't',
            ],
            'second' => [],
        ],
    ],

    'replacers' => [
        'Ё' => 'Е',
        'ё' => 'е',
    ],
];
