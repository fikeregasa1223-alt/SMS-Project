<?php
declare(strict_types=1);

/**
 * SMS — Bulk importer (CSV)
 */

if (defined('SMS_IMPORTER_LOADED')) { return; }
define('SMS_IMPORTER_LOADED', true);

/**
 * Parse a CSV file into an array of associative rows.
 */
function csv_parse(string $path, array $requiredHeaders = []): array
{
    if (!is_file($path)) {
        return ['ok' => false, 'error' => 'File not found.', 'rows' => [], 'headers' => []];
    }

    $handle = @fopen($path, 'r');
    if (!$handle) {
        return ['ok' => false, 'error' => 'Cannot open file.', 'rows' => [], 'headers' => []];
    }

    $headers = [];
    $rows    = [];
    $first   = true;

    while (($row = fgetcsv($handle, 4096, ',')) !== false) {
        if ($first) {
            $headers = array_map(fn($h) => strtolower(trim((string)$h)), $row);
            $first = false;
            continue;
        }
        if (count($row) === 1 && $row[0] === null) continue;

        $assoc = [];
        foreach ($headers as $i => $h) {
            $assoc[$h] = isset($row[$i]) ? trim((string)$row[$i]) : '';
        }
        $rows[] = $assoc;
    }
    fclose($handle);

    if ($requiredHeaders) {
        $missing = array_diff($requiredHeaders, $headers);
        if ($missing) {
            return ['ok' => false, 'error' => 'Missing columns: ' . implode(', ', $missing), 'rows' => [], 'headers' => $headers];
        }
    }

    return ['ok' => true, 'error' => null, 'rows' => $rows, 'headers' => $headers];
}

/**
 * Import students from CSV.
 * Required columns: first_name, last_name, gender, date_of_birth, admission_date, status
 * Optional: middle_name, phone, email, address, emergency_name, emergency_phone
 *
 * @return array{ok:bool, imported:int, errors:array, error:?string}
 */
function import_students(string $path): array
{
    $required = ['first_name','last_name','gender','date_of_birth','admission_date','status'];
    $parsed = csv_parse($path, $required);
    if (!$parsed['ok']) {
        return ['ok' => false, 'imported' => 0, 'errors' => [], 'error' => $parsed['error']];
    }

    $imported = 0;
    $errors   = [];

    foreach ($parsed['rows'] as $i => $row) {
        $lineNo = $i + 2;
        try {
            $data = [
                'student_code'    => next_student_code(),
                'admission_no'    => next_admission_no(),
                'first_name'      => $row['first_name'] ?? '',
                'middle_name'     => $row['middle_name'] ?? null,
                'last_name'       => $row['last_name'] ?? '',
                'gender'          => strtolower($row['gender'] ?? ''),
                'date_of_birth'   => $row['date_of_birth'] ?? '',
                'phone'           => $row['phone'] ?? null,
                'email'           => $row['email'] ?? null,
                'address'         => $row['address'] ?? null,
                'emergency_name'  => $row['emergency_name'] ?? null,
                'emergency_phone' => $row['emergency_phone'] ?? null,
                'admission_date'  => $row['admission_date'] ?? '',
                'status'          => strtolower($row['status'] ?? 'active'),
                'photo'           => null,
            ];

            if (!$data['first_name'] || !$data['last_name']) {
                throw new RuntimeException('Missing first/last name.');
            }
            if (!in_array($data['gender'], gender_list(), true)) {
                throw new RuntimeException('Invalid gender: ' . $data['gender']);
            }
            if (!in_array($data['status'], student_status_list(), true)) {
                throw new RuntimeException('Invalid status: ' . $data['status']);
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_of_birth'])) {
                throw new RuntimeException('Invalid date_of_birth (use YYYY-MM-DD).');
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['admission_date'])) {
                throw new RuntimeException('Invalid admission_date (use YYYY-MM-DD).');
            }

            Database::insert('students', $data);
            $imported++;
        } catch (Throwable $e) {
            $errors[] = 'Line ' . $lineNo . ': ' . $e->getMessage();
        }
    }

    return ['ok' => true, 'imported' => $imported, 'errors' => $errors, 'error' => null];
}

/**
 * Import teachers from CSV.
 * Required: first_name, last_name, gender, employment_status
 */
function import_teachers(string $path): array
{
    $required = ['first_name','last_name','gender','employment_status'];
    $parsed = csv_parse($path, $required);
    if (!$parsed['ok']) {
        return ['ok' => false, 'imported' => 0, 'errors' => [], 'error' => $parsed['error']];
    }

    $imported = 0;
    $errors   = [];

    foreach ($parsed['rows'] as $i => $row) {
        $lineNo = $i + 2;
        try {
            $data = [
                'teacher_code'      => next_teacher_code(),
                'employee_no'       => next_employee_no(),
                'first_name'        => $row['first_name'] ?? '',
                'middle_name'       => $row['middle_name'] ?? null,
                'last_name'         => $row['last_name'] ?? '',
                'gender'            => strtolower($row['gender'] ?? ''),
                'date_of_birth'     => $row['date_of_birth'] ?: null,
                'phone'             => $row['phone'] ?? null,
                'email'             => $row['email'] ?? null,
                'address'           => $row['address'] ?? null,
                'qualification'     => $row['qualification'] ?? null,
                'specialization'    => $row['specialization'] ?? null,
                'hire_date'         => $row['hire_date'] ?: null,
                'employment_status' => strtolower($row['employment_status'] ?? 'full_time'),
                'photo'             => null,
            ];

            if (!$data['first_name'] || !$data['last_name']) {
                throw new RuntimeException('Missing first/last name.');
            }
            if (!in_array($data['gender'], gender_list(), true)) {
                throw new RuntimeException('Invalid gender: ' . $data['gender']);
            }
            if (!in_array($data['employment_status'], employment_status_list(), true)) {
                throw new RuntimeException('Invalid employment_status: ' . $data['employment_status']);
            }

            Database::insert('teachers', $data);
            $imported++;
        } catch (Throwable $e) {
            $errors[] = 'Line ' . $lineNo . ': ' . $e->getMessage();
        }
    }

    return ['ok' => true, 'imported' => $imported, 'errors' => $errors, 'error' => null];
}

/**
 * Return the CSV template headers for a given import type.
 */
function import_template(string $type): array
{
    return match ($type) {
        'students' => [
            'first_name','middle_name','last_name','gender','date_of_birth',
            'phone','email','address','emergency_name','emergency_phone',
            'admission_date','status',
        ],
        'teachers' => [
            'first_name','middle_name','last_name','gender','date_of_birth',
            'phone','email','address','qualification','specialization',
            'hire_date','employment_status',
        ],
        default => [],
    };
}

/**
 * Output a CSV template as a download.
 */
function import_template_download(string $type): void
{
    $headers = import_template($type);
    if (!$headers) { http_response_code(400); exit('Invalid template type.'); }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sms_' . $type . '_template.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    // Sample rows
    if ($type === 'students') {
        fputcsv($out, ['John','','Doe','male','2010-05-12','+251911000000','john@example.com','Addis Ababa','Jane Doe','+251911000001','2025-09-01','active']);
    } elseif ($type === 'teachers') {
        fputcsv($out, ['Abebe','','Kebede','male','1985-03-20','+251911000002','abebe@example.com','Addis Ababa','B.Ed','Mathematics','2023-09-01','full_time']);
    }
    fclose($out);
    exit;
}