<?php

namespace App\Support\Kiosk;

use App\Support\Homis;

/**
 * Production HOMIS gateway: delegates to the ODBC helpers in App\Support\Homis.
 */
class OdbcHomisGateway implements HomisGateway
{
    public function lookupPerson(string $hospitalNumber): array
    {
        return Homis::lookupPerson($hospitalNumber);
    }

    public function registerOpd(array $person, string $dob, string $tscode, string $diagtxt, string $enccode): array
    {
        return Homis::registerOpd($person, $dob, $tscode, $diagtxt, $enccode);
    }
}
