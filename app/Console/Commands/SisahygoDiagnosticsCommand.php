<?php

namespace App\Console\Commands;

use App\Application\System\BuildReleaseDiagnostics;
use Illuminate\Console\Command;

class SisahygoDiagnosticsCommand extends Command
{
    protected $signature = 'sisahygo:diagnostics';

    protected $description = 'Print sanitized release-candidate diagnostics for Sisahygo Connect.';

    public function handle(BuildReleaseDiagnostics $diagnostics): int
    {
        $this->info('Sisahygo Connect diagnostics');

        foreach ($diagnostics() as $key => $value) {
            $this->line($key.': '.$this->formatValue($value));
        }

        return self::SUCCESS;
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return is_scalar($value) ? (string) $value : 'unavailable';
    }
}
