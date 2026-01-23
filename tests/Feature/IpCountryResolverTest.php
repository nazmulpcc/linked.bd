<?php

use App\Services\IpCountryResolver;

test('ip country resolver returns null when database path is missing', function () {
    config()->set('services.ip_country_db_path', '/tmp/missing-ip-country.mmdb');

    $resolver = app(IpCountryResolver::class);

    expect($resolver->resolve('8.8.8.8'))->toBeNull();
});
