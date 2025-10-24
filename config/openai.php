<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key and Organization
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API Key and organization. This will be
    | used to authenticate with the OpenAI API - you can find your API key
    | and organization on your OpenAI dashboard, at https://openai.com.
    */

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. By default, the client will time out after 30 seconds.
    */

    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),

    'translation' => [
        'model' => 'gpt-4o-mini',
        'unclearTerm' => '[not translatable]',
        'instructionProduct' => 'You will be provided with a product description for swimming pool products in language "%s". Translate it to language "%s". Maintain numbers and protected designations. If you are not able to translate, answer exactly with [not translatable].',
        'instructionProductName' => 'You will be provided with a product title for swimming pool products in language "%s". Translate it to language "%s". Maintain numbers and protected designations. If you are not able to translate, answer exactly with [not translatable].',
        'instructionColorHex' => 'Given a color name or a describing term in "%s" language and in some cases in english. Provide its hex code with a natural textile tone. If the colour name is a compound name, the first part should be used. Compound names are often separated by a "/" sign. If the term is like "Forest Green" or "Surfer Blue" try to detect the contained color. The color "transparent" equals #FFFFFF. If unavailable, respond exactly with "[not translatable]".',
        'instructionGeneral' => 'You will be provided with property describing swimming pool products in language "%s". The property may be a clothing size, color, arm length, cut, a gender term, wash care, professional cleaning or similar. Translate it to language "%s". Maintain numbers and protected designations. If you are not able to translate, answer exactly with [not translatable].',
        'temperature' => 0.3,
    ],

    'imageGetColor' => [
        'model' => 'gpt-4o-mini',
        'groupFilter' => 'XF_ColorFilter',
        'groupText' => 'XF_Color',
        'unclearTerm' => 'not in list',
        'instructionProduct' => 'This image shows the swimming pool products "%s". Which color has it? Choose color from this set (colors are in language "%s"): %s. use only one term. If color is not in the list, answer with the nearest color from the list.',
        'instructionColorHint' => 'Hint: the product description mentions the color "%s".',
    ],
];
