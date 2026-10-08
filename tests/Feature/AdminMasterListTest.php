<?php

namespace Tests\Feature;

use App\Models\AdditionalRequirement;
use App\Models\Application;
use App\Models\ApplicationAdditionalRequirement;
use App\Models\MasterList;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\FinalListExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use ZipArchive;

class AdminMasterListTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_list_year_uses_the_configured_submission_start_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Application::factory()->create(['status' => 'approved']);
        SystemSetting::current()->update([
            'application_start_date' => '2027-01-15 08:00:00',
            'application_end_date' => '2027-02-15 17:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.masterlist.index'))
            ->assertOk()
            ->assertSee('Final List of Batch 2027')
            ->assertSee('Final List of Applicants')
            ->assertSee('Final List Name (Optional)')
            ->assertSee('value="Final List of Batch 2027"', false)
            ->assertSee('a separate folder for each applicant')
            ->assertDontSee('Approved Applicants Master List');
    }

    public function test_applicant_name_search_filters_approved_results_and_is_preserved_when_saving(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $matchingApplicant = Application::factory()->create([
            'status' => 'approved',
            'full_name' => 'Maria Santos',
            'first_name' => 'Maria',
            'surname' => 'Santos',
        ]);
        $otherApprovedApplicant = Application::factory()->create([
            'status' => 'approved',
            'full_name' => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'surname' => 'Dela Cruz',
        ]);
        Application::factory()->create([
            'status' => 'denied',
            'full_name' => 'Maria Denied',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.masterlist.index', ['search' => 'mArIa']))
            ->assertOk()
            ->assertSee('Search approved applicant by name')
            ->assertSee('Maria')
            ->assertSee('Santos')
            ->assertDontSee('Dela Cruz')
            ->assertDontSee('Maria Denied')
            ->assertSee('value="mArIa"', false);

        $this->post(route('admin.masterlist.store'), [
            'name' => '',
            'search' => 'mArIa',
            'sort' => 'name_asc',
        ])->assertRedirect(route('admin.masterlist.index'));

        $masterList = MasterList::firstOrFail();
        $this->assertSame('mArIa', $masterList->filters_json['search']);
        $this->assertDatabaseHas('master_list_applications', [
            'master_list_id' => $masterList->id,
            'application_id' => $matchingApplicant->id,
        ]);
        $this->assertDatabaseMissing('master_list_applications', [
            'master_list_id' => $masterList->id,
            'application_id' => $otherApprovedApplicant->id,
        ]);
    }

    public function test_admin_final_list_table_uses_the_placement_report_columns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create();
        $applicant->profile()->create([
            'permanent_address' => '12 Main Street, Lal-lo',
        ]);
        $application = Application::factory()->for($applicant)->create([
            'status' => 'approved',
            'full_name' => 'Jane Marie Doe',
            'surname' => 'Doe',
            'first_name' => 'Jane',
            'middle_name' => 'Marie',
            'sex' => 'Female',
            'age' => 19,
            'barangay' => 'Tucalana',
            'parent_status' => 'Both Parents Living',
            'education' => 'Senior High School Graduate',
            'grade_year_level' => 'Grade 12',
            'spes_status' => 'baby',
            'f3_position' => 'Office Assistant',
            'f3_employer_address' => 'Municipal Hall',
            'f3_start_date' => '2026-07-01',
            'f3_end_date' => '2026-08-01',
            'f3_wage_rate' => '500',
        ]);
        SystemSetting::current()->update([
            'application_start_date' => '2026-09-30 17:16:00',
            'application_end_date' => '2026-10-21 17:16:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.masterlist.index'))
            ->assertOk()
            ->assertSee([
                'Ref. No.',
                'Name of Students',
                'Last Name',
                'First Name',
                'M.I.',
                'Sex',
                'Age',
                'Address',
                'Status of Parents',
                'Student / OSY / Dependent',
                'Educational Attainment',
                'Grade / Year Level',
                'New or SPES Baby',
                'Nature of Work',
                'Place of Work',
                'Employment Period',
                'Wage Rate per Day',
                'Company Share',
            ])
            ->assertSeeInOrder([
                'Doe',
                'Jane',
                'M',
                '12 Main Street, Lal-lo',
                'Student',
                'SHS',
                'Grade 12',
                'Baby',
                'Office Assistant',
                'Municipal Hall',
                'Sep 30, 2026',
                'Oct 21, 2026',
                '500',
            ])
            ->assertSee('name="placement['.$application->id.'][nature_of_work]"', false)
            ->assertSee('type="number"', false);
    }

    public function test_placement_export_amounts_only_accept_non_negative_integers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.masterlist.index'))
            ->post(route('admin.masterlist.store'), [
                'placement' => [
                    123 => [
                        'wage_rate' => '0',
                        'company_share' => '0',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.masterlist.index'))
            ->assertSessionHasErrors([
                'placement.123.wage_rate',
                'placement.123.company_share',
            ]);
    }

    public function test_saving_without_a_custom_name_uses_submission_start_year(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create();
        Storage::disk('public')->put('applications/resumes/birth-certificate.pdf', 'birth certificate bytes');
        Storage::disk('public')->put('applications/enrollment/enrollment.pdf', 'enrollment certificate bytes');
        Storage::disk('local')->put('requirements/consent.pdf', 'parent consent bytes');
        $application = Application::factory()->for($applicant)->create([
            'status' => 'approved',
            'full_name' => 'Jane Doe',
            'first_name' => 'Jane',
            'surname' => 'Doe',
            'middle_name' => 'Marie',
            'education' => 'College (Currently Enrolled)',
            'parent_status' => 'Both Parents Living',
            'spes_status' => 'new',
            'age' => 19,
            'barangay' => 'Tucalana',
            'resume' => 'applications/resumes/birth-certificate.pdf',
            'certificate_enrollment' => 'applications/enrollment/enrollment.pdf',
        ]);
        $requirement = AdditionalRequirement::create([
            'name' => 'Parent Consent Form',
            'is_required' => true,
            'is_active' => true,
        ]);
        ApplicationAdditionalRequirement::create([
            'application_id' => $application->id,
            'additional_requirement_id' => $requirement->id,
            'file_path' => 'requirements/consent.pdf',
            'original_name' => 'consent.pdf',
        ]);
        SystemSetting::current()->update([
            'application_start_date' => '2027-01-15 08:00:00',
            'application_end_date' => '2027-02-15 17:00:00',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.masterlist.store'), [
                'name' => '',
                'sort' => 'name_asc',
                'placement' => [
                    $application->id => [
                        'nature_of_work' => 'Office Support',
                        'place_of_assignment' => 'PESO Office',
                        'wage_rate' => '500',
                        'company_share' => '250',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.masterlist.index'))
            ->assertSessionHas('success', "Final list 'Final List of Batch 2027' saved as an Excel placement report with applicant details and submitted documents.");

        $masterList = MasterList::firstOrFail();
        $this->assertSame('Final List of Batch 2027', $masterList->name);
        $this->assertNotNull($masterList->archive_path);
        $this->assertDatabaseHas('master_list_applications', [
            'master_list_id' => $masterList->id,
            'application_id' => $application->id,
        ]);

        $archive = new ZipArchive;
        $this->assertTrue($archive->open(Storage::disk('local')->path($masterList->archive_path)));
        $entries = [];
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $entries[] = $archive->getNameIndex($index);
        }
        $this->assertContains('Final List.xlsx', $entries);
        $this->assertContains('Applicant Documents/001 - Jane-Doe/Applicant Details.pdf', $entries);
        $this->assertContains('Applicant Documents/001 - Jane-Doe/Birth-Certificate.pdf', $entries);
        $this->assertContains('Applicant Documents/001 - Jane-Doe/Certificate-of-Enrollment.pdf', $entries);
        $this->assertContains('Applicant Documents/001 - Jane-Doe/Additional Requirements/001 - Parent-Consent-Form/Parent-Consent-Form.pdf', $entries);
        $this->assertDoesNotMatchRegularExpression('/audit/i', implode("\n", $entries));
        $excelPath = tempnam(sys_get_temp_dir(), 'spes-test-final-list-');
        $this->assertNotFalse($excelPath);
        file_put_contents($excelPath, $archive->getFromName('Final List.xlsx'));
        $worksheet = IOFactory::load($excelPath)->getActiveSheet();
        @unlink($excelPath);
        $this->assertSame('PLACEMENT REPORT', $worksheet->getCell('A10')->getValue());
        $this->assertSame(1, $worksheet->getCell('A17')->getValue());
        $this->assertSame('Doe', $worksheet->getCell('B17')->getValue());
        $this->assertSame('Jane', $worksheet->getCell('C17')->getValue());
        $this->assertSame('M', $worksheet->getCell('D17')->getValue());
        $this->assertSame('College', $worksheet->getCell('J17')->getValue());
        $this->assertSame('Both Parents Living', $worksheet->getCell('H17')->getValue());
        $this->assertSame('Student', $worksheet->getCell('I17')->getValue());
        $this->assertSame('New', $worksheet->getCell('L17')->getValue());
        $this->assertSame('Jan 15, 2027', $worksheet->getCell('O17')->getValue());
        $this->assertSame('Feb 15, 2027', $worksheet->getCell('P17')->getValue());
        $this->assertSame('Office Support', $worksheet->getCell('M17')->getValue());
        $this->assertSame('PESO Office', $worksheet->getCell('N17')->getValue());
        $this->assertSame(500, $worksheet->getCell('Q17')->getValue());
        $this->assertSame(250, $worksheet->getCell('R17')->getValue());
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'f3_position' => null,
            'f3_employer_address' => null,
        ]);
        $this->assertNotSame('ABADILLA', $worksheet->getCell('B17')->getValue());
        $emailRows = collect(app(FinalListExportService::class)->applicantFieldRows($application->load('user')))
            ->where('label', 'Applicant Account Email');
        $this->assertCount(1, $emailRows);
        $this->assertSame($applicant->email, $emailRows->first()['value']);
        $this->assertSame(
            [],
            collect(app(FinalListExportService::class)->applicantFieldRows($application))
                ->filter(fn (array $field): bool => str_contains(strtolower($field['label']), 'audit')
                    || str_contains(strtolower($field['label']), 'form 2')
                    || str_contains(strtolower($field['label']), 'forms step'))
                ->all(),
        );
        $this->assertSame(
            'birth certificate bytes',
            $archive->getFromName('Applicant Documents/001 - Jane-Doe/Birth-Certificate.pdf'),
        );
        $this->assertSame(
            'parent consent bytes',
            $archive->getFromName('Applicant Documents/001 - Jane-Doe/Additional Requirements/001 - Parent-Consent-Form/Parent-Consent-Form.pdf'),
        );
        $archive->close();

        $this->actingAs($admin)
            ->get(route('admin.masterlist.download', $masterList))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $this->actingAs($admin)
            ->get(route('admin.masterlist.index'))
            ->assertOk()
            ->assertSee('Saved Final List Packages')
            ->assertSee(route('admin.masterlist.download', $masterList), false);
    }

    public function test_final_list_year_falls_back_to_current_year_without_a_submission_start_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->travelTo(now()->setDate(2026, 10, 5));

        $this->actingAs($admin)
            ->get(route('admin.masterlist.index'))
            ->assertOk()
            ->assertSee('Final List of Batch 2026');
    }
}
