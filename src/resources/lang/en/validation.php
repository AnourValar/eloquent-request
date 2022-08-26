<?php

return [
    'scalar' => 'Filter for :attribute must be a string.',
    'like' => 'Filter for :attribute contains invalid length.',
    'list' => 'Filter for :attribute must be a list.',

    'filter_not_supported' => 'Filter for :attribute is not available.',
    'operation_not_exists' => 'Filter for :attribute contains a non-existent operation.',
    'operation_not_supported' => 'Filter for :attribute does not support the specified operation.',
    'relation_not_supported' => 'Filter for relation :relation is not available.',
    'relation_operation_not_supported' => 'Filter for relation :relation contains a non-existent operation.',
    'relation_out_of_range' => 'Filter for relation :relation must be between 0 and :max.',
    'scope_not_supported' => 'Filter for scope :scope not available.',

    'sort_not_supported' => 'Sorting for the :attribute is not available.',
    'sort_not_exists' => 'Sorting for the :attribute is incorrect.',

    'per_page' => 'The number of entries per page must be a natural number.',
    'per_page_over_max' => 'Maximum number of entries per page is :max.',
    'cursor_paginate_incorrect' => 'Incorrect page cursor.',
    'page' => 'The page number must be a natural number.',
    'page_over_last_is_forbidden' => 'The Page does not exist.',
    'page_over_max' => 'Maximum available page is :max.',

    'ranges' => [
        'min' => 'Filter for :attribute below range.',
        'max' => 'Filter for :attribute above range.',
    ],
];
