<?php

namespace App\Console\Commands;

use App\Domain\Doctors\Services\DoctorAvailabilitySlotGenerator;
use Illuminate\Console\Command;

class RegenerateDoctorAvailabilitySlotsCommand extends Command
{
    protected $signature = 'doctors:regenerate-slots';

    protected $description = 'Replace future open doctor slots with a full-year availability schedule';

    public function handle(DoctorAvailabilitySlotGenerator $generator): int
    {
        $this->info('Regenerating doctor availability slots for the next year...');

        $created = $generator->regenerateAllFutureOpenSlots();

        $this->info("Created {$created} open slots.");

        return self::SUCCESS;
    }
}
