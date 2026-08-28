<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Encrypt existing PHI rows so Laravel `encrypted` casts can read them.
     */
    public function up(): void
    {
        $this->encryptColumns('appointments', ['symptoms', 'chief_complaint', 'clinical_summary']);
        $this->encryptColumns('patient_profiles', ['medical_history']);
    }

    public function down(): void
    {
        // Irreversible without knowing which rows were previously plaintext.
    }

    /**
     * @param  list<string>  $columns
     */
    private function encryptColumns(string $table, array $columns): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        DB::table($table)->orderBy('id')->chunkById(100, function ($rows) use ($table, $columns): void {
            foreach ($rows as $row) {
                $updates = [];

                foreach ($columns as $column) {
                    $value = $row->{$column} ?? null;

                    if (! is_string($value) || $value === '') {
                        continue;
                    }

                    if ($this->isAlreadyEncrypted($value)) {
                        continue;
                    }

                    $updates[$column] = Crypt::encryptString($value);
                }

                if ($updates !== []) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        });
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
};
