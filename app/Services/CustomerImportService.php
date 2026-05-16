<?php

namespace App\Services;

use App\Models\Tenant\Customer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerImportService
{
    public function import(UploadedFile $file): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Cannot open file']];
        }

        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            fclose($handle);

            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Empty file or invalid CSV']];
        }

        $header = array_map('trim', $header);

        if (!in_array('email', $header) || !in_array('name', $header)) {
            fclose($handle);

            return ['imported' => 0, 'skipped' => 0, 'errors' => ['CSV must have at least: name, email columns']];
        }

        $rowNum = 1;
        $maxRows = 20000;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNum++;

            if ($rowNum > $maxRows + 1) {
                $errors[] = "Import stopped at {$maxRows} rows — split large files and upload separately.";
                break;
            }

            if (count($row) < 2) {
                $skipped++;

                continue;
            }

            $data = array_combine(array_slice($header, 0, count($row)), $row);

            $email = trim($data['email'] ?? '');
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNum}: invalid email '{$email}'";
                $skipped++;

                continue;
            }

            try {
                DB::transaction(function () use ($data, $email) {
                    $existing = Customer::where('email', $email)->first();

                    if ($existing) {
                        // Set (not add) loyalty_points if provided — increment() made
                        // re-uploading the same file (a retry after a timeout, or a
                        // manager re-running it) silently add the CSV value again on
                        // top of the existing balance every time.
                        if (isset($data['loyalty_points']) && $data['loyalty_points'] !== '') {
                            $existing->update(['loyalty_points' => max(0, (int) $data['loyalty_points'])]);
                        }

                        return; // deduplication — skip creating
                    }

                    Customer::create([
                        'name' => trim($data['name'] ?? ''),
                        'email' => $email,
                        'phone' => trim($data['phone'] ?? '') ?: null,
                        'delivery_city' => trim($data['city'] ?? '') ?: null,
                        'loyalty_points' => (int) ($data['loyalty_points'] ?? 0),
                        'password' => bcrypt(Str::random(16)),
                    ]);
                });

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNum}: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        return compact('imported', 'skipped', 'errors');
    }
}
