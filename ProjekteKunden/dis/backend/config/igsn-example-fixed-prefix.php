<?php

// Example for IGSN generation with a fixed prefix for all records
// Warning: constants of \app\components\Igsn cannot be use in here
return [
    'class' => 'app\components\Igsn',
    'method' => 'fixed_prefix',
    'prefix' => 'AW',
    'variablePartLength' => 8
];
