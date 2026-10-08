<?php

namespace Database\Seeders;

use App\Models\AdditionalRequirement;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SpesRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $requirements = [
            [
                'name' => 'School Certification (QFR-QOP-02-11)',
                'filename' => 'QFR-QOP-02-11 School Certification.doc',
                'description' => 'Have your school complete and certify this form. Submit it to the LGU Lal-lo PESO office as soon as possible and follow any deadline announced by PESO.',
            ],
            [
                'name' => 'SPES Form 2 - Application Form',
                'filename' => 'SPES FORM 2 - APPLICATION FORM (1).docx',
                'description' => 'Download, complete, and sign this application form. Submit it to the LGU Lal-lo PESO office as soon as possible and follow any deadline announced by PESO.',
            ],
            [
                'name' => 'SPES Form 2-A-1 - Oath of Undertaking (New Applicants)',
                'filename' => 'SPES FORM 2-A-1 -OATH  OF UNDERTAKING NEW.docx',
                'description' => 'For new applicants: download, complete, and sign this oath of undertaking. Submit it to the LGU Lal-lo PESO office as soon as possible and follow any deadline announced by PESO.',
            ],
            [
                'name' => 'SPES Form 4 - Employment Contract',
                'filename' => 'SPES FORM 4 - EMPLOYMENT CONTRACT.docx',
                'description' => 'Download and complete this employment contract as instructed by PESO. Submit it to the LGU Lal-lo PESO office by the deadline provided for your application.',
            ],
        ];

        foreach ($requirements as $requirement) {
            $existingRequirement = AdditionalRequirement::where('name', $requirement['name'])->first();
            if ($existingRequirement?->template_path) {
                continue;
            }

            $sourcePath = resource_path('forms/spes-requirements/'.$requirement['filename']);
            if (! is_file($sourcePath)) {
                throw new RuntimeException("SPES requirement form is missing: {$requirement['filename']}");
            }

            $extension = pathinfo($requirement['filename'], PATHINFO_EXTENSION);
            $storedName = Str::slug(pathinfo($requirement['filename'], PATHINFO_FILENAME)).'.'.$extension;
            $templatePath = Storage::disk('local')->putFileAs(
                'requirements/templates',
                new File($sourcePath),
                $storedName,
            );

            if (! is_string($templatePath)) {
                throw new RuntimeException("Unable to install SPES requirement form: {$requirement['filename']}");
            }

            if ($existingRequirement) {
                $existingRequirement->update([
                    'template_path' => $templatePath,
                    'template_original_name' => $requirement['filename'],
                ]);
            } else {
                AdditionalRequirement::create([
                    'name' => $requirement['name'],
                    'description' => $requirement['description'],
                    'is_required' => true,
                    'is_active' => true,
                    'template_path' => $templatePath,
                    'template_original_name' => $requirement['filename'],
                ]);
            }
        }
    }
}
