<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Access to the hospital information system (HOMIS).
 *
 * HOMIS is a SQL Server database reached through the operating system ODBC
 * DSN. The legacy QALINGA1 pages used the same connection for hperson, hrxo,
 * and hdocord. Patient portal identifiers are mapped through the local
 * patients.hospital_number value (hpercode in HOMIS).
 *
 * Reads serve the patient portal; lookupPerson() and registerOpd() serve the
 * OPD kiosk check-in and are the only HOMIS writes in the application.
 */
class Homis
{
    /**
     * Does this hospital number (hpercode) belong to a person with the given
     * birthdate? The birthdate is the MMDDYYYY string patients type in.
     *
     * @return bool|null true = match, false = no match, null = HOMIS unreachable
     */
    public static function verifyBirthdate(string $hpercode, string $mmdYyyy): ?bool
    {
        $hpercode = trim($hpercode);
        $mmdYyyy = trim($mmdYyyy);

        if ($hpercode === '' || $mmdYyyy === '') {
            return false;
        }

        $person = static::person($hpercode);

        if ($person === null) {
            return null;
        }

        $stored = trim((string) ($person['patbdate'] ?? ''));

        if ($stored === '') {
            return false;
        }

        if ($stored === $mmdYyyy) {
            return true;
        }

        $timestamp = strtotime($stored);

        return $timestamp !== false && date('mdY', $timestamp) === $mmdYyyy;
    }

    /**
     * Personal info for a hospital number (hperson row).
     *
     * @return array{hpercode?:string,patfirst?:string,patmiddle?:string,patlast?:string,patbdate?:string,patsex?:string,pattelno?:string}|null
     */
    public static function person(string $hpercode): ?array
    {
        $hpercode = trim($hpercode);

        if ($hpercode === '') {
            return null;
        }

        $connection = self::connection();

        if ($connection === false) {
            self::close($connection);

            return null;
        }

        try {
            $rows = self::fetchAll(
                $connection,
                'SELECT hpercode, patfirst, patmiddle, patlast, patbdate, patsex, pattelno
                 FROM hperson
                 WHERE hpercode = ?',
                [$hpercode],
            );

            if ($rows === null || $rows === []) {
                return null;
            }

            $row = $rows[0];
            $person = [];

            foreach (['hpercode', 'patfirst', 'patmiddle', 'patlast', 'patbdate', 'patsex', 'pattelno'] as $column) {
                $person[$column] = (string) ($row[$column] ?? '');
            }

            return $person;
        } finally {
            self::close($connection);
        }
    }

    /**
     * Personal info + postal address, used to prefill the registration form.
     *
     * @return array{person:array<string,string>,address:string}|null
     */
    public static function profile(string $hpercode): ?array
    {
        $person = static::person($hpercode);

        if ($person === null) {
            return null;
        }

        return ['person' => $person, 'address' => static::address($hpercode)];
    }

    /**
     * Street + barangay / city / province text for a hospital number.
     */
    public static function address(string $hpercode): string
    {
        $hpercode = trim($hpercode);

        if ($hpercode === '') {
            return '';
        }

        $connection = self::connection();

        if ($connection === false) {
            self::close($connection);

            return '';
        }

        try {
            $rows = self::fetchAll(
                $connection,
                "SELECT a.patstr,
                        ISNULL(bgyname, ''),
                        ISNULL(ctyname, ''),
                        ISNULL(provname, ''),
                        a.patzip
                 FROM haddr a
                 LEFT JOIN hbrgy b ON a.brg = b.bgycode
                 LEFT JOIN hcity c ON a.ctycode = c.ctycode
                 LEFT JOIN hprov p ON a.provcode = p.provcode
                 WHERE a.hpercode = ?",
                [$hpercode],
            );

            if ($rows === null || $rows === []) {
                return '';
            }

            $row = $rows[0];
            $street = trim((string) ($row['patstr'] ?? ''));
            $barangay = trim((string) ($row['bgyname'] ?? ''));
            $city = trim((string) ($row['ctyname'] ?? ''));
            $province = trim((string) ($row['provname'] ?? ''));
            $zip = trim((string) ($row['patzip'] ?? ''));
            $parts = [$street];

            if ($barangay !== '' || $city !== '' || $province !== '') {
                $parts[] = trim(implode(', ', array_filter([$barangay, $city, $province])));
            }

            if ($zip !== '') {
                $parts[] = $zip;
            }

            return trim(implode(' ', array_filter($parts)));
        } finally {
            self::close($connection);
        }
    }

    /**
     * Current HOMIS connection status for the portal status message.
     *
     * @return array{available:bool,configured:bool,message:string}
     */
    public static function status(): array
    {
        $configured = trim((string) config('services.homis.dsn', 'homis')) !== ''
            && trim((string) config('services.homis.username', 'sa')) !== '';

        if (! config('services.homis.enabled', true)) {
            return [
                'available' => false,
                'configured' => $configured,
                'message' => 'HOMIS records are disabled for this application.',
            ];
        }

        if (! extension_loaded('odbc')) {
            return [
                'available' => false,
                'configured' => $configured,
                'message' => 'HOMIS records require the PHP ODBC extension on the web server.',
            ];
        }

        if (! $configured) {
            return [
                'available' => false,
                'configured' => false,
                'message' => 'HOMIS ODBC credentials are not configured.',
            ];
        }

        $connection = self::connection();

        if ($connection === false) {
            $error = strtolower((string) @odbc_errormsg());
            $message = str_contains($error, 'data source name') || str_contains($error, 'im002')
                ? 'The HOMIS ODBC DSN "homis" is not registered on the web server.'
                : 'HOMIS is temporarily unavailable. Please try again later.';

            return [
                'available' => false,
                'configured' => true,
                'message' => $message,
            ];
        }

        self::close($connection);

        return [
            'available' => true,
            'configured' => true,
            'message' => 'Connected to HOMIS.',
        ];
    }

    /**
     * All prescription lines for a hospital number.
     *
     * @return array<int, array<string, string|null>>
     */
    public static function prescriptions(string $hpercode): array
    {
        $hpercode = trim($hpercode);

        if ($hpercode === '') {
            return [];
        }

        $connection = self::connection();

        if ($connection === false) {
            self::close($connection);

            return [];
        }

        try {
            $rows = self::fetchAll($connection, self::prescriptionQuery(), [$hpercode]);

            if ($rows === null) {
                $rows = self::fetchAll($connection, self::simplePrescriptionQuery(), [$hpercode]);
            }

            if ($rows === null) {
                return [];
            }

            return array_values(array_filter(array_map(
                fn (array $row): array => self::normalizePrescription($row),
                $rows,
            )));
        } finally {
            self::close($connection);
        }
    }

    /**
     * Procedure orders and available result paths for a hospital number.
     *
     * @return array<int, array<string, string|bool|null>>
     */
    public static function procedures(string $hpercode): array
    {
        $hpercode = trim($hpercode);

        if ($hpercode === '') {
            return [];
        }

        $connection = self::connection();

        if ($connection === false) {
            self::close($connection);

            return [];
        }

        try {
            $rows = self::fetchAll(
                $connection,
                "SELECT d.pcchrgcod,
                        d.enccode,
                        d.dodate,
                        d.resultpdf,
                        d.orcode,
                        d.proccode,
                        ISNULL(pr.procdesc, 'Procedure') AS itemdesc,
                        d.pchrgqty AS qty
                 FROM hdocord d WITH (NOLOCK)
                 LEFT JOIN hprocm pr WITH (NOLOCK) ON pr.proccode = d.proccode
                 WHERE d.hpercode = ?
                 ORDER BY d.dodate DESC, d.pcchrgcod DESC",
                [$hpercode],
            );

            if ($rows === null) {
                return [];
            }

            return array_values(array_filter(array_map(
                fn (array $row): array => self::normalizeProcedure($row, $hpercode),
                $rows,
            )));
        } finally {
            self::close($connection);
        }
    }

    /**
     * Chronological HOMIS visit history for a hospital number.
     *
     * @return array<int, array<string, string|null>>
     */
    public static function visitHistory(string $hpercode): array
    {
        $hpercode = trim($hpercode);

        if ($hpercode === '') {
            return [];
        }

        $connection = self::connection();

        if ($connection === false) {
            self::close($connection);

            return [];
        }

        try {
            $rows = self::fetchAll(
                $connection,
                "SELECT 'ER' AS visit_type, enccode, erdate AS visit_date
                 FROM herlog WITH (NOLOCK)
                 WHERE hpercode = ?
                 UNION ALL
                 SELECT 'OPD' AS visit_type, enccode, opddate AS visit_date
                 FROM hopdlog WITH (NOLOCK)
                 WHERE hpercode = ?
                 UNION ALL
                 SELECT 'Admission' AS visit_type, enccode, admdate AS visit_date
                 FROM hadmlog WITH (NOLOCK)
                 WHERE hpercode = ?
                 ORDER BY visit_date DESC",
                [$hpercode, $hpercode, $hpercode],
            );

            if ($rows === null) {
                return [];
            }

            return array_values(array_filter(array_map(
                fn (array $row): array => [
                    'source' => 'HOMIS',
                    'visit_type' => trim((string) ($row['visit_type'] ?? '')),
                    'encounter_code' => trim((string) ($row['enccode'] ?? '')),
                    'date' => trim((string) ($row['visit_date'] ?? '')),
                ],
                $rows,
            )));
        } finally {
            self::close($connection);
        }
    }

    /**
     * True when PHP can actually reach HOMIS from this machine.
     */
    public static function available(): bool
    {
        return self::status()['available'];
    }

    /**
     * Look up a person in HOMIS by hospital number for the kiosk check-in.
     *
     * Unlike person(), this reports why nothing came back so the kiosk can
     * tell "HOMIS did not answer" apart from "no such person".
     *
     * @return array{status: 'ok'|'error', found: bool, person: array<string, string>|null, message: string}
     */
    public static function lookupPerson(string $hpercode): array
    {
        $hpercode = trim($hpercode);

        if ($hpercode === '') {
            return ['status' => 'ok', 'found' => false, 'person' => null, 'message' => 'Hospital number is missing.'];
        }

        $connection = self::connection();

        if ($connection === false) {
            return ['status' => 'error', 'found' => false, 'person' => null, 'message' => 'HOMIS connection failed.'];
        }

        try {
            $rows = self::fetchAll(
                $connection,
                'SELECT hpercode, patfirst, patmiddle, patlast, patbdate, patsex
                 FROM hperson
                 WHERE hpercode = ?',
                [$hpercode],
            );

            if ($rows === null) {
                return ['status' => 'error', 'found' => false, 'person' => null, 'message' => 'HOMIS person query failed.'];
            }

            if ($rows === []) {
                return ['status' => 'ok', 'found' => false, 'person' => null, 'message' => 'Hospital number not found in HOMIS.'];
            }

            $row = $rows[0];
            $person = [];

            foreach (['hpercode', 'patfirst', 'patmiddle', 'patlast', 'patbdate', 'patsex'] as $column) {
                $person[$column] = trim((string) ($row[$column] ?? ''));
            }

            return ['status' => 'ok', 'found' => true, 'person' => $person, 'message' => ''];
        } finally {
            self::close($connection);
        }
    }

    /**
     * Register an OPD encounter in HOMIS for a kiosk check-in.
     *
     * Mirrors the legacy QALINGA1 update_appointment.php flow: compute the
     * patient's age from the date of birth, allocate the daily case number and
     * per-type queue number, then insert henctr + hopdlog. It is idempotent:
     * when the encounter code already exists in HOMIS the existing encounter
     * is adopted instead of inserted again, so retries and re-scans can never
     * duplicate an encounter for the same appointment.
     *
     * @param  array<string, string>  $person  Person row from lookupPerson().
     * @return array{status: 'ok'|'error', enccode: string, message: string}
     */
    public static function registerOpd(array $person, string $dob, string $tscode, string $diagtxt, string $enccode): array
    {
        $hpercode = trim((string) ($person['hpercode'] ?? ''));
        $enccode = trim($enccode);

        if ($hpercode === '' || $enccode === '') {
            return self::registrationError($enccode, 'Hospital number or encounter code is missing.');
        }

        $connection = self::connection();

        if ($connection === false) {
            return self::registrationError($enccode, 'HOMIS connection failed.');
        }

        try {
            $encdate = Carbon::now(self::registrationTimezone())->format('Y-m-d H:i:s');
            $fhud = (string) config('kiosk.homis.fhud', '0001818');
            $entryby = (string) config('kiosk.homis.entry_by', 'na');
            $tacode = (string) config('kiosk.homis.tacode', 'SERVI');

            // Adopt an encounter a previous attempt (or another terminal)
            // already inserted for this encounter code.
            $existing = self::fetchAll($connection, 'SELECT hpercode FROM henctr WHERE enccode = ?', [$enccode]);

            if ($existing === null) {
                return self::registrationError($enccode, 'HOMIS encounter query failed.');
            }

            if ($existing === []) {
                $inserted = self::execute(
                    $connection,
                    'INSERT INTO henctr
                        (enccode, fhud, hpercode, encdate, enctime, toecode, sopcode1, encstat, enclock, updsw, confdl, entryby, tacode)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $enccode,
                        $fhud,
                        $hpercode,
                        $encdate,
                        $encdate,
                        (string) config('kiosk.homis.toecode', 'OPD'),
                        (string) config('kiosk.homis.sopcode1', 'SELPA'),
                        'A',
                        'N',
                        'N',
                        'N',
                        $entryby,
                        $tacode,
                    ],
                );

                if (! $inserted) {
                    // A concurrent attempt may have won the insert; adopt it.
                    $retry = self::fetchAll($connection, 'SELECT hpercode FROM henctr WHERE enccode = ?', [$enccode]);

                    if ($retry === null || $retry === []) {
                        return self::registrationError($enccode, 'HOMIS encounter insert failed.');
                    }
                }
            } elseif (trim((string) ($existing[0]['hpercode'] ?? '')) !== $hpercode) {
                return self::registrationError($enccode, 'Encounter code is already used by another patient.');
            }

            $opdRows = self::fetchAll($connection, 'SELECT enccode FROM hopdlog WHERE enccode = ?', [$enccode]);

            if ($opdRows === null) {
                return self::registrationError($enccode, 'HOMIS OPD log query failed.');
            }

            if ($opdRows === []) {
                $inserted = self::insertOpdLog($connection, $hpercode, $dob, $tscode, $diagtxt, $enccode, $encdate);

                if (! $inserted) {
                    $retry = self::fetchAll($connection, 'SELECT enccode FROM hopdlog WHERE enccode = ?', [$enccode]);

                    if ($retry === null || $retry === []) {
                        return self::registrationError($enccode, 'HOMIS OPD log insert failed.');
                    }
                }
            }

            return ['status' => 'ok', 'enccode' => $enccode, 'message' => ''];
        } finally {
            self::close($connection);
        }
    }

    /**
     * Insert the hopdlog row with the legacy age / case number / queue number
     * logic.
     */
    private static function insertOpdLog(mixed $connection, string $hpercode, string $dob, string $tscode, string $diagtxt, string $enccode, string $encdate): bool
    {
        $age = self::ageParts($dob);
        $patage = $age['years'];
        $patagemo = $patage < 1 ? $age['months'] : 0;
        $patagedy = $patage < 1 && $patagemo < 1 ? $age['days'] : 0;

        return self::execute(
            $connection,
            'INSERT INTO hopdlog
                (enccode, hpercode, upicode, patage, tacode, tscode, opddate, opdtime, opdrem, opdstat, opdlock, updsw, confdl, casenum, patagemo, patagedy, entryby, newold, queno, opdtxt, telesched)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $enccode,
                $hpercode,
                '',
                $patage,
                (string) config('kiosk.homis.tacode', 'SERVI'),
                $tscode,
                $encdate,
                $encdate,
                (string) config('kiosk.homis.opd_remark', 'PAS Registration'),
                'A',
                'N',
                'N',
                'N',
                self::nextCaseNumber($connection),
                $patagemo,
                $patagedy,
                (string) config('kiosk.homis.entry_by', 'na'),
                trim($hpercode) !== '' ? 'N' : 'Y',
                self::nextQueueNumber($connection, $tscode),
                $diagtxt,
                (string) config('kiosk.homis.tele_flag', 'N'),
            ],
        );
    }

    /**
     * Next daily case number (YYYY-NNNNNN), like the legacy kiosk computed it.
     */
    private static function nextCaseNumber(mixed $connection): string
    {
        $year = Carbon::now(self::registrationTimezone())->format('Y');
        $rows = self::fetchAll(
            $connection,
            'SELECT MAX(casenum) AS casenum FROM hopdlog WHERE SUBSTRING(casenum, 1, 4) = ?',
            [$year],
        );

        $last = $rows === null || $rows === [] ? '' : trim((string) ($rows[0]['casenum'] ?? ''));

        if ($last === '') {
            return $year.'-000001';
        }

        $sequence = intval(substr($last, 5)) + 1;

        return $year.'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Next queue number for the service type, counted from today's OPD log.
     */
    private static function nextQueueNumber(mixed $connection, string $tscode): int
    {
        $tstype = '';

        if (trim($tscode) !== '') {
            $rows = self::fetchAll($connection, 'SELECT tstype FROM htypser WHERE tscode = ?', [trim($tscode)]);
            $tstype = $rows === null || $rows === [] ? '' : trim((string) ($rows[0]['tstype'] ?? ''));
        }

        $rows = self::fetchAll(
            $connection,
            'SELECT COUNT(a.enccode) AS queueno
             FROM hopdlog a WITH (NOLOCK), htypser b WITH (NOLOCK)
             WHERE (a.opddate > CONVERT(VARCHAR(10), GETDATE(), 101)
                AND a.opddate < DATEADD(DAY, 1, CONVERT(VARCHAR(10), GETDATE(), 101)))
                AND a.tscode = b.tscode
                AND b.tstype = ?',
            [$tstype],
        );

        if ($rows === null || $rows === []) {
            return 1;
        }

        return (int) $rows[0]['queueno'] + 1;
    }

    /**
     * Whole years, months and days between the date of birth and today in the
     * registration timezone, like the legacy kiosk computed them.
     *
     * @return array{years: int, months: int, days: int}
     */
    private static function ageParts(string $dob): array
    {
        $dob = trim($dob);

        if ($dob === '') {
            return ['years' => 0, 'months' => 0, 'days' => 0];
        }

        try {
            $birth = Carbon::createFromFormat('Y-m-d', substr($dob, 0, 10), self::registrationTimezone())->startOfDay();
        } catch (\Throwable) {
            return ['years' => 0, 'months' => 0, 'days' => 0];
        }

        $interval = $birth->diff(Carbon::now(self::registrationTimezone())->startOfDay());

        return ['years' => $interval->y, 'months' => $interval->m, 'days' => $interval->d];
    }

    private static function registrationTimezone(): string
    {
        return (string) config('app.display_timezone', 'Asia/Manila');
    }

    /**
     * @return array{status: 'error', enccode: string, message: string}
     */
    private static function registrationError(string $enccode, string $message): array
    {
        return ['status' => 'error', 'enccode' => $enccode, 'message' => $message];
    }

    /**
     * Run a parameterised statement without fetching rows.
     *
     * @param  array<int, string|int>  $parameters
     */
    private static function execute(mixed $connection, string $sql, array $parameters = []): bool
    {
        $statement = @odbc_prepare($connection, $sql);

        if ($statement === false) {
            return false;
        }

        return (bool) @odbc_execute($statement, $parameters);
    }

    private static function connection(): mixed
    {
        if (! config('services.homis.enabled', true) || ! extension_loaded('odbc')) {
            return false;
        }

        $username = trim((string) config('services.homis.username', 'sa'));
        $password = (string) config('services.homis.password', '');
        $host = trim((string) config('services.homis.host', '172.16.200.1'));
        $port = trim((string) config('services.homis.port', '1433'));
        $database = trim((string) config('services.homis.database', 'hospital'));

        if ($username === '' || $host === '' || $database === '') {
            return false;
        }

        $connectionString = "Driver={SQL Server};Server={$host},{$port};Database={$database};";

        try {
            return @odbc_connect($connectionString, $username, $password);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<int, string>  $parameters
     * @return array<int, array<string, mixed>>|null null means the ODBC query failed
     */
    private static function fetchAll(mixed $connection, string $sql, array $parameters = []): ?array
    {
        $statement = @odbc_prepare($connection, $sql);

        if ($statement === false) {
            return null;
        }

        if (! @odbc_execute($statement, $parameters)) {
            return null;
        }

        $fieldCount = @odbc_num_fields($statement);

        if ($fieldCount === false || $fieldCount < 1) {
            return [];
        }

        $rows = [];

        while (@odbc_fetch_row($statement)) {
            $row = [];

            for ($index = 1; $index <= $fieldCount; $index++) {
                $field = @odbc_field_name($statement, $index);
                $name = $field === false ? 'column_'.$index : (string) $field;
                $value = @odbc_result($statement, $index);
                $row[$name] = $value === false ? null : $value;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private static function close(mixed $connection): void
    {
        if ($connection !== false && $connection !== null) {
            @odbc_close($connection);
        }
    }

    private static function prescriptionQuery(): string
    {
        return <<<'SQL'
SELECT
    r.pcchrgcod,
    r.enccode,
    r.dodate,
    r.licno,
    r.qtyintake,
    r.uomintake,
    r.reppatrn1,
    r.reppatru1,
    r.repdayno1,
    r.remarks,
    r.pchrgqty AS qty,
    r.medname,
    ISNULL(md.itemdesc, r.medname) AS itemdesc,
    RTRIM(doc.firstname) + ' ' +
        LEFT(RTRIM(ISNULL(doc.middlename, '')), 1) + '. ' +
        RTRIM(doc.lastname) + ',MD' AS doctor_name
FROM hrxo r WITH (NOLOCK)
LEFT JOIN hprovider hp ON hp.licno = r.licno
LEFT JOIN hpersonal doc ON doc.employeeid = hp.employeeid
LEFT JOIN (
    SELECT
        hdmhdr.dmdcomb,
        RTRIM(hgen.gendesc) + ' ' +
            ISNULL(hdmhdr.brandname, '') + ' ' +
            CONVERT(VARCHAR(20), ISNULL(hdmhdr.dmdnost, '')) + ' ' +
            ISNULL(hdmhdr.strecode, '') + ' ' +
            ISNULL(hform.formdesc, '') AS itemdesc
    FROM hdmhdr WITH (NOLOCK)
    JOIN hdruggrp ON hdruggrp.grpcode = hdmhdr.grpcode
    JOIN hgen ON hgen.gencode = hdruggrp.gencode
    LEFT JOIN hform ON hform.formcode = hdmhdr.formcode
) md ON md.dmdcomb = r.dmdcomb
WHERE r.hpercode = ?
ORDER BY r.dodate DESC, r.pcchrgcod
SQL;
    }

    private static function simplePrescriptionQuery(): string
    {
        return <<<'SQL'
SELECT pcchrgcod, dodate, medname, qty
FROM hrxo WITH (NOLOCK)
WHERE hpercode = ?
ORDER BY dodate DESC
SQL;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string|null>
     */
    private static function normalizePrescription(array $row): array
    {
        $medicine = self::value($row, ['itemdesc', 'medname', 'medicinename']);
        $quantity = self::value($row, ['qty', 'pchrgqty', 'quantity']);
        $frequency = trim(implode(' ', array_filter([
            self::value($row, ['reppatru1']),
            self::value($row, ['reppatrn1']),
        ])));
        $days = self::value($row, ['repdayno1']);
        $instructions = trim(implode(' ', array_filter([
            self::value($row, ['qtyintake']),
            self::value($row, ['uomintake']),
            $frequency,
            $days !== '' ? 'for '.$days.' day'.($days === '1' ? '' : 's') : '',
        ])));

        return [
            'source' => 'HOMIS',
            'prescription_number' => self::value($row, ['pcchrgcod', 'prescription_number']),
            'date' => self::value($row, ['dodate', 'date']),
            'doctor_name' => self::value($row, ['doctor_name', 'doctor']),
            'medicine' => $medicine,
            'quantity' => $quantity,
            'instructions' => $instructions,
            'remarks' => self::value($row, ['remarks']),
            'encounter_code' => self::value($row, ['enccode']),
            'license_no' => self::value($row, ['licno']),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string|bool|null>
     */
    private static function normalizeProcedure(array $row, string $hpercode): array
    {
        $resultPath = self::value($row, ['resultpdf']);
        $costCenter = self::value($row, ['orcode']);

        return [
            'source' => 'HOMIS',
            'procedure_number' => self::value($row, ['pcchrgcod']),
            'encounter_code' => self::value($row, ['enccode']),
            'date' => self::value($row, ['dodate', 'date']),
            'procedure' => self::value($row, ['itemdesc', 'procdesc']),
            'quantity' => self::value($row, ['qty', 'pchrgqty']),
            'cost_center' => $costCenter,
            'result_available' => $resultPath !== '',
            'result_url' => self::procedureResultUrl($resultPath, $costCenter, $hpercode),
        ];
    }

    private static function procedureResultUrl(string $resultPath, string $costCenter, string $hpercode): ?string
    {
        if ($resultPath === '') {
            return null;
        }

        if (Str::startsWith(strtolower($resultPath), ['http://', 'https://'])) {
            return $resultPath;
        }

        $center = strtoupper($costCenter);

        if (! str_contains($center, 'LAB') && ! str_contains($center, 'ER')) {
            return null;
        }

        if (! str_contains($resultPath, '\\')) {
            return null;
        }

        $parts = explode('\\', $resultPath);
        $file = (string) end($parts);
        $key = str_contains($resultPath, 'MDL') ? substr($file, 0, 18) : substr($file, 1, 10);

        if ($key === '') {
            return null;
        }

        return rtrim((string) config('services.homis.result_base_url', 'https://weblis.qmmc.com/getPdf'), '/')
            .'/'.rawurlencode($key).'/'.rawurlencode($hpercode);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private static function value(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            foreach ($row as $column => $value) {
                if (strcasecmp((string) $column, $key) === 0 && $value !== null) {
                    return trim((string) $value);
                }
            }
        }

        return '';
    }
}
