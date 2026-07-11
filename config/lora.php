<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public product website hosts
    |--------------------------------------------------------------------------
    |
    | These hosts render the Lora PMS marketing website instead of a tenant's
    | public booking website. Hotel domains continue through WebsiteController.
    |
    */
    'marketing_hosts' => array_values(array_filter(array_map(
        static fn (string $host): string => strtolower(trim($host)),
        explode(',', (string) env(
            'LORA_MARKETING_HOSTS',
            'lorapms.com,www.lorapms.com,staging.lorapms.com',
        )),
    ))),
];
