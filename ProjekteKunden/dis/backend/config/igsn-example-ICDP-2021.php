<?php

// Example for IGSN generation with the new (2021) ICDP method including site and hole.
// The length of IGSN columns in the models has to be increased!
//
// Warning: constants of \app\components\Igsn cannot be use in here
return [
    'class' => 'app\components\Igsn',
    'method' => 'ICDP_2021',
    'saveToDatabase' => true,
    'variablePartLength' => 6,
    'defaultRepPrefix' => 'E', // or 'R'
    // 'defaultProgramPrefix'  => '',
    // 'defaultExpeditionPrefix' => '',
    // 'defaultSitePrefix' => '',
    // 'defaultHolePrefix' => '',
];
