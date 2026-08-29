<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class ExcelImportReader
{
    public const MAX_UPLOAD_KILOBYTES = 10240;

    public const MAX_IMPORT_ROWS = 5000;

    private const ALIASES = [
        'nom' => 'last_name',
        'last_name' => 'last_name',
        'prenom' => 'first_name',
        'first_name' => 'first_name',
        'matricule' => 'student_number',
        'student_number' => 'student_number',
        'student_id' => 'student_number',
        'classroom_id' => 'classroom_id',
        'classroom' => 'classroom_id',
        'class' => 'classroom_id',
        'phone' => 'phone',
        'telephone' => 'phone',
        'email' => 'email',
        'diplome' => 'diploma',
        'diploma' => 'diploma',
        'ville' => 'city',
        'city' => 'city',
        'adresse' => 'address',
        'adress' => 'address',
        'address' => 'address',
        'niveau_scolaire' => 'education_level',
        'education_level' => 'education_level',
        'taille' => 'height',
        'height' => 'height',
        'poids' => 'weight',
        'weight' => 'weight',
        'note_appreciation' => 'appreciation_score',
        'appreciation_score' => 'appreciation_score',
        'arrets' => 'absences_count',
        'absences_count' => 'absences_count',
        'appreciation' => 'appreciation',
        'fcb' => 'semester_1',
        'semester_1' => 'semester_1',
        'mo' => 'semester_2',
        'semester_2' => 'semester_2',
        'so' => 'semester_3',
        'semester_3' => 'semester_3',
        'pa' => 'semester_4',
        'semester_4' => 'semester_4',
        'pj' => 'semester_5',
        'police_j' => 'semester_5',
        'semester_5' => 'semester_5',
        'pj2' => 'semester_6',
        'police_j2' => 'semester_6',
        'semester_6' => 'semester_6',
        'prescription_medical' => 'medical_prescription',
        'medical_prescription' => 'medical_prescription',
        'date' => 'date',
        'type' => 'type',
    ];

    public function __construct(private readonly UploadedFile|string $file) {}

    public static function fromUploadedFile(UploadedFile $file): self
    {
        return new self($file);
    }

    public static function uploadRules(): array
    {
        return ['required', 'file', 'mimes:xlsx,xls,csv', 'max:'.self::MAX_UPLOAD_KILOBYTES];
    }

    public function rows(string $field = 'excel_file', string $operation = 'excel import', ?int $maxRows = null): array
    {
        $path = $this->file instanceof UploadedFile ? $this->file->getRealPath() : $this->file;
        $maxRows ??= self::MAX_IMPORT_ROWS;

        try {
            $readerType = IOFactory::identify($path);
            $extension = mb_strtolower($this->file instanceof UploadedFile
                ? $this->file->getClientOriginalExtension()
                : pathinfo((string) $path, PATHINFO_EXTENSION));

            if (! in_array($readerType, ['Xlsx', 'Xls', 'Csv'], true)
                || (in_array($extension, ['xlsx', 'xls'], true) && $readerType === 'Csv')) {
                throw new \RuntimeException("Unsupported or mismatched spreadsheet reader type [{$readerType}].");
            }

            $spreadsheet = IOFactory::load($path);
        } catch (Throwable $exception) {
            Log::warning('Spreadsheet import failed while loading workbook.', [
                'operation' => $operation,
                'field' => $field,
                'client_extension' => $this->file instanceof UploadedFile ? $this->file->getClientOriginalExtension() : null,
                'client_mime_type' => $this->file instanceof UploadedFile ? $this->file->getClientMimeType() : null,
                'size' => $this->file instanceof UploadedFile ? $this->file->getSize() : null,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                $field => 'The uploaded workbook could not be read. Please upload a valid spreadsheet file.',
            ]);
        }
        $worksheet = $spreadsheet->getActiveSheet();
        $headers = [];
        $rows = [];

        foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
            $values = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $values[] = $cell->getValue();
            }

            if ($rowIndex === 1) {
                $headers = $this->headersFromValues($values);

                continue;
            }

            if ($this->isEmptyRow($values)) {
                continue;
            }

            $rowData = ['_row' => $rowIndex];

            foreach ($headers as $index => $header) {
                if ($header === null) {
                    continue;
                }

                $value = $values[$index] ?? null;

                if (! array_key_exists($header, $rowData) || $this->isBlank($rowData[$header])) {
                    $rowData[$header] = $value;
                }
            }

            $rows[] = $rowData;

            if (count($rows) > $maxRows) {
                Log::warning('Spreadsheet import rejected because it exceeded the row limit.', [
                    'operation' => $operation,
                    'field' => $field,
                    'max_rows' => $maxRows,
                    'client_extension' => $this->file instanceof UploadedFile ? $this->file->getClientOriginalExtension() : null,
                    'client_mime_type' => $this->file instanceof UploadedFile ? $this->file->getClientMimeType() : null,
                    'size' => $this->file instanceof UploadedFile ? $this->file->getSize() : null,
                ]);

                throw ValidationException::withMessages([
                    $field => "The uploaded workbook may not contain more than {$maxRows} data rows.",
                ]);
            }
        }

        return $rows;
    }

    public function text(array $row, string $key, ?string $default = null): ?string
    {
        $value = $this->value($row, $key);

        if ($this->isBlank($value)) {
            return $default;
        }

        return trim((string) $value);
    }

    public function integer(array $row, string $key, ?int $default = null): ?int
    {
        $number = $this->number($this->value($row, $key));

        return $number === null ? $default : (int) round($number);
    }

    public function decimal(array $row, string $key, ?float $default = null): ?float
    {
        return $this->number($this->value($row, $key)) ?? $default;
    }

    public function date(array $row, string $key, ?string $default = null): ?string
    {
        $value = $this->value($row, $key);

        if ($this->isBlank($value)) {
            return $default;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return $default;
        }
    }

    public function averageNumeric(array $row, ?array $onlyKeys = null, array $except = []): ?float
    {
        $except = array_flip(array_map(fn (string $key) => $this->canonicalHeader($key), $except));
        $keys = $onlyKeys === null ? array_keys($row) : array_map(fn (string $key) => $this->canonicalHeader($key), $onlyKeys);
        $numbers = [];

        foreach ($keys as $key) {
            if ($key === '_row' || isset($except[$key])) {
                continue;
            }

            $number = $this->number($row[$key] ?? null);

            if ($number !== null) {
                $numbers[] = $number;
            }
        }

        if ($numbers === []) {
            return null;
        }

        return round(array_sum($numbers) / count($numbers), 2);
    }

    public function canonicalHeader(string $header): string
    {
        $normalized = $this->normalizeHeader($header);

        return self::ALIASES[$normalized] ?? $normalized;
    }

    private function headersFromValues(array $values): array
    {
        return array_map(function ($value) {
            if ($this->isBlank($value)) {
                return null;
            }

            return $this->canonicalHeader((string) $value);
        }, $values);
    }

    private function normalizeHeader(string $header): string
    {
        $header = trim(mb_strtolower($header));
        $header = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header) ?: $header;
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header;

        return trim($header, '_');
    }

    private function value(array $row, string $key): mixed
    {
        return $row[$this->canonicalHeader($key)] ?? null;
    }

    private function number(mixed $value): ?float
    {
        if ($this->isBlank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace([' ', ','], ['', '.'], trim((string) $value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function isEmptyRow(array $values): bool
    {
        return array_filter($values, fn ($value) => ! $this->isBlank($value)) === [];
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '';
    }
}
