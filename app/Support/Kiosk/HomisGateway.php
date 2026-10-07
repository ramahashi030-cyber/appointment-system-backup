<?php

namespace App\Support\Kiosk;

/**
 * Injectable seam over the HOMIS registration calls used by the kiosk.
 *
 * Controllers depend on this interface so feature tests can bind a fake and
 * assert the HOMIS interaction (success, failure, duplicate suppression)
 * without touching the SQL Server connection.
 */
interface HomisGateway
{
    /**
     * Look up a person in HOMIS by hospital number.
     *
     * status "ok" means HOMIS answered (found tells whether the hospital
     * number exists); any other status means HOMIS could not be reached and
     * the kiosk must not proceed.
     *
     * @return array{status: string, found: bool, person: array<string, string>|null, message: string}
     */
    public function lookupPerson(string $hospitalNumber): array;

    /**
     * Register an OPD encounter in HOMIS for the given person.
     *
     * Returns status "ok" with the enccode used; the implementation adopts an
     * encounter that already exists for that code, so retries are idempotent.
     *
     * @param  array<string, string>  $person  Person row from lookupPerson().
     * @return array{status: string, enccode: string, message: string}
     */
    public function registerOpd(array $person, string $dob, string $tscode, string $diagtxt, string $enccode): array;
}
