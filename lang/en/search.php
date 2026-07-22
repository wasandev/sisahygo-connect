<?php

return [
    'label' => 'Universal search',
    'placeholder' => 'Tracking / client reference / batch reference',
    'submit' => 'Search',
    'submitting' => 'Searching...',
    'found' => 'Found by :type',
    'not_found' => 'No result found for :query',
    'types' => [
        'tracking' => 'tracking number',
        'client_reference' => 'client reference',
        'batch_reference' => 'batch reference',
    ],
    'validation' => [
        'required' => 'Please enter a search value.',
    ],
    'errors' => [
        'no_credential' => 'Sisahygo connection details are not available yet.',
        'unavailable' => 'Search is unavailable right now. Please try again.',
    ],
];
