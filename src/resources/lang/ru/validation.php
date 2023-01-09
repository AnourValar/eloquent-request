<?php

return [
    'scalar' => 'Фильтр по полю :attribute должен быть строкой.',
    'length' => 'Фильтр по полю :attribute содержит недопустимое количество символов.',
    'list' => 'Фильтр по полю :attribute должен содержать список.',

    'filter_not_supported' => 'Фильтр по полю :attribute недоступен.',
    'operation_not_exists' => 'Фильтр по полю :attribute содержит несуществующую операцию.',
    'operation_not_supported' => 'Фильтр по полю :attribute не поддерживает указанную операцию.',
    'relation_not_supported' => 'Фильтр по связи с :relation недоступен.',
    'relation_operation_not_supported' => 'Фильтр по связи с :relation содержит несуществующую операцию.',
    'relation_out_of_range' => 'Фильтр по связи с :relation должен быть в диапазоне от 0 до :max.',
    'scope_not_supported' => 'Фильтр по скоупу :scope недоступен.',

    'sort_not_supported' => 'Сортировка по полю :attribute недоступна.',
    'sort_not_exists' => 'Сортировка по полю :attribute содержит несуществующее направление.',

    'per_page' => 'Количество записей на странице должно быть натуральным числом.',
    'per_page_over_max' => 'Максимально допустимое количество записей на странице - :max.',
    'cursor_paginate_incorrect' => 'Некорректный курсор страницы.',
    'page' => 'Номер страницы должен быть натуральным числом.',
    'page_over_last_is_forbidden' => 'Несуществующая страница.',
    'page_over_max' => 'Максимально доступная страница - :max.',

    'ranges' => [
        'min' => 'Фильтр по полю :attribute ниже диапазона значений.',
        'max' => 'Фильтр по полю :attribute выше диапазона значений.',
    ],
];
