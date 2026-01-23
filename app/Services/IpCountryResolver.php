<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Throwable;

class IpCountryResolver
{
    public function resolve(string $ip): ?string
    {
        $databasePath = config('services.ip_country_db_path');

        if (! is_string($databasePath) || $databasePath === '' || ! is_file($databasePath)) {
            return null;
        }

        try {
            $reader = new Reader($databasePath);
            $country = $reader->country($ip);
            $code = $country->country->isoCode;

            if (is_string($code) && $code !== '') {
                return strtoupper($code);
            }
        } catch (AddressNotFoundException) {
            return null;
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
