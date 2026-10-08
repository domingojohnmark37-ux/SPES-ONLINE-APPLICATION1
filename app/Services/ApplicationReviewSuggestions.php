<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicationReviewSuggestions
{
    private const DOCUMENTS = [
        'resume' => ['label' => 'Birth Certificate', 'required' => true, 'disk' => 'public'],
        'certificate_enrollment' => ['label' => 'Certificate of Enrollment', 'required' => true, 'disk' => 'public'],
        'certificate_grade' => ['label' => 'Certificate of Grades', 'required' => false, 'disk' => 'public'],
        'application_letter' => ['label' => 'Application Letter', 'required' => false, 'disk' => 'public'],
        'indigency' => ['label' => 'Certificate of Indigency', 'required' => false, 'disk' => 'public'],
    ];

    private const DOCUMENT_KEYWORDS = [
        'birth' => ['birthcertificate', 'birthcert', 'psa', 'nso'],
        'enrollment' => ['enrollment', 'enrolment', 'registration', 'school'],
        'grades' => ['grade', 'grades', 'reportcard', 'transcript'],
        'letter' => ['applicationletter', 'coverletter'],
        'indigency' => ['indigency', 'indigent', 'poverty'],
    ];

    public function forApplication(Application $application): array
    {
        $suggestions = [];
        $this->checkProfileName($application, $suggestions);
        $this->checkDocuments($application, $suggestions);
        $this->checkAdditionalRequirements($application, $suggestions);

        return $suggestions;
    }

    private function checkProfileName(Application $application, array &$suggestions): void
    {
        $profile = $application->user?->profile;
        if (! $profile) {
            return;
        }

        $nameDifferences = [];
        foreach ([
            'first_name' => 'first name',
            'last_name' => 'last name',
        ] as $profileField => $label) {
            $profileValue = trim((string) $profile->{$profileField});
            $applicationValue = trim((string) $application->{match ($profileField) {
                'first_name' => 'first_name',
                'last_name' => 'surname',
            }});

            if ($profileValue !== ''
                && $applicationValue !== ''
                && $this->normalizeName($profileValue) !== $this->normalizeName($applicationValue)) {
                $nameDifferences[] = sprintf(
                    '%s: profile says "%s", application says "%s"',
                    ucfirst($label),
                    $profileValue,
                    $applicationValue,
                );
            }
        }

        $middleName = trim((string) $application->middle_name);
        $applicationNameFromParts = trim(implode(' ', array_filter([
            $application->first_name,
            in_array(Str::lower($middleName), ['n/a', 'na'], true) ? '' : $middleName,
            $application->surname,
        ])));
        if ($application->full_name !== ''
            && $applicationNameFromParts !== ''
            && $this->normalizeName($application->full_name) !== $this->normalizeName($applicationNameFromParts)) {
            $nameDifferences[] = 'The full name does not match the first, middle, and last name fields';
        }

        if ($nameDifferences !== []) {
            $suggestions[] = [
                'type' => 'warning',
                'title' => 'Could the applicant name differ from their profile?',
                'message' => implode('; ', $nameDifferences).'. Verify the applicant’s identity and documents before deciding.',
            ];
        }

        if ($profile->date_of_birth
            && $application->birthday
            && ! $profile->date_of_birth->isSameDay($application->birthday)) {
            $suggestions[] = [
                'type' => 'warning',
                'title' => 'Could the birth date differ from the profile?',
                'message' => sprintf(
                    'Profile says %s, while the application says %s. Verify the date against the applicant’s documents.',
                    $profile->date_of_birth->format('M j, Y'),
                    $application->birthday->format('M j, Y'),
                ),
            ];
        }
    }

    private function checkDocuments(Application $application, array &$suggestions): void
    {
        foreach (self::DOCUMENTS as $field => $document) {
            $path = $application->{$field};
            if (! filled($path)) {
                if ($document['required']) {
                    $suggestions[] = [
                        'type' => 'warning',
                        'title' => 'Is a required document missing?',
                        'message' => $document['label'].' has not been uploaded.',
                    ];
                }

                continue;
            }

            $disk = Storage::disk($document['disk']);
            if (! $disk->exists($path)) {
                $suggestions[] = [
                    'type' => 'warning',
                    'title' => 'Can the uploaded document be opened?',
                    'message' => $document['label'].' is missing from file storage. Ask the applicant to upload it again.',
                ];

                continue;
            }

            $originalName = $application->document_original_names[$field] ?? basename($path);
            if ($this->clearlyWrongDocumentName($field, $originalName)) {
                $suggestions[] = [
                    'type' => 'warning',
                    'title' => 'Could this be the wrong document?',
                    'message' => sprintf(
                        '%s was uploaded with the filename "%s". Check that it contains the requested document.',
                        $document['label'],
                        $originalName,
                    ),
                ];
            }

            $expectedExtension = in_array($field, ['resume', 'certificate_enrollment', 'certificate_grade'], true)
                ? ['pdf']
                : ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            if (! $this->fileContentMatches($document['disk'], $path, $expectedExtension)) {
                $suggestions[] = [
                    'type' => 'warning',
                    'title' => 'Could this document have an invalid file type?',
                    'message' => sprintf(
                        '%s does not appear to be a valid %s file. Open it to verify the contents.',
                        $document['label'],
                        implode(' or ', $expectedExtension),
                    ),
                ];
            }
        }
    }

    private function checkAdditionalRequirements(Application $application, array &$suggestions): void
    {
        foreach ($application->additionalRequirementSubmissions as $submission) {
            $requirementName = $submission->requirement?->name ?? 'Additional Requirement';
            $disk = Storage::disk('local');
            if (! $disk->exists($submission->file_path)) {
                $suggestions[] = [
                    'type' => 'warning',
                    'title' => 'Can this additional document be opened?',
                    'message' => sprintf(
                        '%s is missing from file storage. Ask the applicant to upload it again.',
                        $requirementName,
                    ),
                ];

                continue;
            }

            if ($this->clearlyWrongAdditionalDocumentName($requirementName, $submission->original_name)) {
                $suggestions[] = [
                    'type' => 'warning',
                    'title' => 'Could this additional document be for a different requirement?',
                    'message' => sprintf(
                        '"%s" was submitted for "%s". Check that the file contains the requested document.',
                        $submission->original_name,
                        $requirementName,
                    ),
                ];
            }

            if (! $this->fileContentMatches(
                'local',
                $submission->file_path,
                ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
                $submission->original_name,
            )) {
                $suggestions[] = [
                    'type' => 'warning',
                    'title' => 'Could this additional document have an invalid file type?',
                    'message' => sprintf(
                        '%s does not appear to be a valid PDF, Word document, JPG, or PNG file. Open it to verify.',
                        $requirementName,
                    ),
                ];
            }
        }
    }

    private function clearlyWrongDocumentName(string $field, string $name): bool
    {
        $expectedKind = match ($field) {
            'resume' => 'birth',
            'certificate_enrollment' => 'enrollment',
            'certificate_grade' => 'grades',
            'application_letter' => 'letter',
            'indigency' => 'indigency',
            default => null,
        };

        if (! $expectedKind) {
            return false;
        }

        return $this->hasKnownDocumentKind($name, $expectedKind) === false
            && $this->hasAnyOtherKnownDocumentKind($name, $expectedKind);
    }

    private function clearlyWrongAdditionalDocumentName(string $requirement, string $originalName): bool
    {
        $requirementKind = $this->knownDocumentKind($requirement);

        return $requirementKind !== null
            && $this->hasKnownDocumentKind($originalName, $requirementKind) === false
            && $this->hasAnyOtherKnownDocumentKind($originalName, $requirementKind);
    }

    private function hasAnyOtherKnownDocumentKind(string $name, string $expectedKind): bool
    {
        foreach (array_keys(self::DOCUMENT_KEYWORDS) as $kind) {
            if ($kind !== $expectedKind && $this->hasKnownDocumentKind($name, $kind)) {
                return true;
            }
        }

        return false;
    }

    private function hasKnownDocumentKind(string $name, string $kind): bool
    {
        $normalized = $this->normalizeName(pathinfo($name, PATHINFO_FILENAME));
        foreach (self::DOCUMENT_KEYWORDS[$kind] ?? [] as $keyword) {
            if (Str::contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function knownDocumentKind(string $name): ?string
    {
        foreach (array_keys(self::DOCUMENT_KEYWORDS) as $kind) {
            if ($this->hasKnownDocumentKind($name, $kind)) {
                return $kind;
            }
        }

        return null;
    }

    private function fileContentMatches(
        string $diskName,
        string $path,
        array $allowedExtensions,
        ?string $originalName = null,
    ): bool {
        $extension = Str::lower(pathinfo($originalName ?: $path, PATHINFO_EXTENSION));
        if (! in_array($extension, $allowedExtensions, true)) {
            return false;
        }

        $stream = Storage::disk($diskName)->readStream($path);
        if (! is_resource($stream)) {
            return false;
        }

        try {
            $sample = fread($stream, 8192);
        } finally {
            fclose($stream);
        }

        if (! is_string($sample) || $sample === '') {
            return false;
        }

        if ($extension === 'pdf') {
            return strpos(substr($sample, 0, 1024), '%PDF-') !== false;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($sample);

        return match ($extension) {
            'jpg', 'jpeg' => $mime === 'image/jpeg',
            'png' => $mime === 'image/png',
            'doc' => in_array($mime, ['application/msword', 'application/CDFV2', 'application/x-ole-storage'], true),
            'docx' => in_array($mime, ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true),
            default => false,
        };
    }

    private function normalizeName(string $value): string
    {
        $ascii = Str::ascii(Str::lower(trim($value)));

        return preg_replace('/[^a-z0-9]/', '', $ascii) ?? '';
    }
}
