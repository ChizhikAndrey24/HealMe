<?php

namespace App\Domain\Patients\Data;

use App\Domain\Patients\Models\PatientCardNote;
use App\Enums\PatientCardNoteAuthor;
use App\Enums\PatientCardNoteSource;

readonly class PatientCardNoteSummary
{
    public function __construct(
        public int $id,
        public PatientCardNoteAuthor $authorType,
        public ?int $authorDoctorId,
        public ?string $authorDoctorName,
        public PatientCardNoteSource $source,
        public ?int $appointmentId,
        public string $body,
        public string $createdAt,
    ) {}

    public static function fromModel(PatientCardNote $note): self
    {
        $note->loadMissing('authorDoctor.user');

        return new self(
            id: $note->id,
            authorType: $note->author_type,
            authorDoctorId: $note->author_doctor_id,
            authorDoctorName: $note->authorDoctor?->user?->name,
            source: $note->source,
            appointmentId: $note->appointment_id,
            body: $note->body,
            createdAt: $note->created_at?->toIso8601String() ?? now()->toIso8601String(),
        );
    }

    /**
     * @return array{
     *     id: int,
     *     author_type: string,
     *     author_doctor_id: int|null,
     *     author_doctor_name: string|null,
     *     source: string,
     *     appointment_id: int|null,
     *     body: string,
     *     created_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'author_type' => $this->authorType->value,
            'author_doctor_id' => $this->authorDoctorId,
            'author_doctor_name' => $this->authorDoctorName,
            'source' => $this->source->value,
            'appointment_id' => $this->appointmentId,
            'body' => $this->body,
            'created_at' => $this->createdAt,
        ];
    }
}
