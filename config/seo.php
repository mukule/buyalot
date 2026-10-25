<?php

return [
    // Default home page title/description. Override per environment via .env.
    'home_title'       => env('SEO_HOME_TITLE'),
    'home_description' => env('SEO_HOME_DESCRIPTION'),

    // Social profile URLs used in Organization "sameAs". Leave blank to omit.
    'social' => [
        env('SEO_FACEBOOK_URL'),
        env('SEO_INSTAGRAM_URL'),
        env('SEO_TWITTER_URL'),
        env('SEO_TIKTOK_URL'),
        env('SEO_YOUTUBE_URL'),
    ],

    // Search engine site verification tokens (rendered as <meta> tags when set).
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),
    'bing_site_verification'   => env('BING_SITE_VERIFICATION'),
];
