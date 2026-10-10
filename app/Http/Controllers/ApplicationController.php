<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AuditAction;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\ApplicantPortalUpdate;
use App\Services\ApplicationApprovalCapacity;
use App\Services\ApplicantNotificationService;
use App\Services\ApplicationReviewSuggestions;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ApplicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin')->only([
            'index', 'show', 'approve', 'deny', 'addComment', 'users',
        ]);
    }

    // -------------------------------------------------------
    // USER SIDE
    // -------------------------------------------------------

    /**
     * Show the application creation form.
     * Redirect to My Application if already submitted.
     */
    public function create()
    {
        // Check if application window is open
        $settings = SystemSetting::current();
        if (! $settings->isApplicationOpen()) {
            return redirect()->route('dashboard')
                ->with('error', 'Applications are currently closed.')
                ->with('approval_capacity_closed', app(ApplicationApprovalCapacity::class)->isFull($settings));
        }

        $existing = Application::where('user_id', Auth::id())->latest('created_at')->first();
        if ($existing) {
            if ($existing->status === 'denied') {
                return redirect()->route('applications.edit')
                    ->with('info', 'Your previous application was denied. Update your details and submit again to reapply.');
            }

            return redirect()->route('applications.myApplication')
                ->with('info', 'You have already submitted an application.');
        }

        $user = Auth::user();
        $profile = $user->profile;

        $defaults = [
            'full_name' => $profile
                ? trim(sprintf('%s %s %s', $profile->last_name, $profile->first_name, $profile->middle_name))
                : $user->name,
            'sex' => optional($profile)->sex ?? $user->sex,
            'birthday' => optional($profile?->date_of_birth)->format('Y-m-d'),
            'age' => $profile && $profile->date_of_birth ? $profile->date_of_birth->age : null,
            'civil_status' => optional($profile)->status,
            'mother_name' => optional($profile)->mother_name,
            'father_guardian_name' => optional($profile)->father_name,
            'contact_no' => optional($profile)->contact_number,
            'messenger' => optional($profile)->social_media,
        ];

        return view('application.applications.create', ['application' => null, 'defaults' => $defaults]);
    }

    public function edit()
    {
        $application = Application::where('user_id', Auth::id())->latest('created_at')->firstOrFail();
        $settings = SystemSetting::current();
        if ($application->status !== 'approved'
            && app(ApplicationApprovalCapacity::class)->isFull($settings)) {
            return redirect()->route('dashboard')
                ->with('error', 'The SPES approval limit has been reached. Your application cannot be resubmitted this season.')
                ->with('approval_capacity_closed', true);
        }

        return view('application.applications.create', compact('application'));
    }

    /**
     * Store a new application with file uploads.
     */
    public function store(
        Request $request,
        AuditLogger $auditLogger,
        ApplicantNotificationService $notifications,
    )
    {
        $this->normalizeLegacyApplicationData($request);

        // Check if application window is open
        $settings = SystemSetting::current();
        if (! $settings->isApplicationOpen()) {
            return redirect()->route('dashboard')
                ->with('error', 'Applications are currently closed.')
                ->with('approval_capacity_closed', app(ApplicationApprovalCapacity::class)->isFull($settings));
        }

        // Prevent duplicate applications
        $existing = Application::where('user_id', Auth::id())->latest('created_at')->first();
        if ($existing) {
            if ($existing->status === 'denied') {
                return redirect()->route('applications.edit')
                    ->with('info', 'Your previous application was denied. Update your details and submit again to reapply.');
            }

            return redirect()->route('applications.myApplication')
                ->with('error', 'You have already submitted an application.');
        }

        $validated = $request->validate([
            'surname' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => ['required', 'string', 'min:2', 'max:255', 'not_regex:/^[A-Za-z]\.?$/'],
            'sex' => 'required|in:Male,Female',
            'birthday' => 'required|date|before:today',
            'age' => 'required|integer|min:15|max:30',
            'barangay' => 'required|string|max:100',
            'civil_status' => 'required|in:Single,Married,Widowed,Separated',
            'parent_status' => 'required|in:Both Parents Living,Solo Parent,Orphan,Guardian',
            'education' => 'required|string|max:100',
            'grade_year_level' => 'required|in:Grade 7,Grade 8,Grade 9,Grade 10,Grade 11,Grade 12,1st year,2nd year,4th year,5th year',
            'spes_status' => 'required|in:new,baby',
            'mother_name' => 'nullable|string|max:255',
            'mother_occupation' => 'nullable|string|max:255',
            'mother_contact_no' => 'nullable|string|max:20',
            'father_guardian_name' => 'nullable|string|max:255',
            'father_occupation' => 'nullable|string|max:255',
            'father_contact_no' => 'nullable|string|max:20',
            'messenger' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'resume' => 'required|file|mimes:pdf|max:5120',
            'certificate_enrollment' => 'required|file|mimes:pdf|max:5120',
            'certificate_grade' => 'nullable|file|mimes:pdf|max:5120',
            'application_letter' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'indigency' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ], [
            'middle_name.not_regex' => 'Please enter your complete middle name. Single initials such as A or A. are not accepted.',
        ]);

        $this->validateFamilyContact($request);

        // Handle file uploads
        $resumePath = null;
        $enrollmentPath = null;
        $gradePath = null;
        $letterPath = null;
        $indigencyPath = null;

        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('applications/resumes', 'public');
        }
        if ($request->hasFile('certificate_enrollment')) {
            $enrollmentPath = $request->file('certificate_enrollment')->store('applications/enrollment', 'public');
        }
        if ($request->hasFile('certificate_grade')) {
            $gradePath = $request->file('certificate_grade')->store('applications/grades', 'public');
        }
        if ($request->hasFile('application_letter')) {
            $letterPath = $request->file('application_letter')->store('applications/letters', 'public');
        }
        if ($request->hasFile('indigency')) {
            $indigencyPath = $request->file('indigency')->store('applications/indigency', 'public');
        }
        $documentOriginalNames = [];
        foreach ([
            'resume',
            'certificate_enrollment',
            'certificate_grade',
            'application_letter',
            'indigency',
        ] as $document) {
            if ($request->hasFile($document)) {
                $documentOriginalNames[$document] = $request->file($document)->getClientOriginalName();
            }
        }

        $fullName = trim(implode(' ', array_filter([
            $validated['first_name'],
            str_replace('N/A', '', $validated['middle_name'] ?? ''),
            $validated['surname'],
        ], fn ($part) => ! empty(trim((string) $part)) && trim((string) $part) !== 'N/A')));

        $applicant = Auth::user();
        $createdApplication = DB::transaction(function () use ($validated, $fullName, $resumePath, $enrollmentPath, $gradePath, $letterPath, $indigencyPath, $documentOriginalNames, $applicant, $auditLogger): Application {
            $application = Application::create([
                'user_id' => Auth::id(),
                'full_name' => $fullName,
                'surname' => $validated['surname'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'sex' => $validated['sex'],
                'birthday' => $validated['birthday'],
                'age' => $validated['age'],
                'barangay' => $validated['barangay'],
                'civil_status' => $validated['civil_status'],
                'parent_status' => $validated['parent_status'],
                'education' => $validated['education'],
                'grade_year_level' => $validated['grade_year_level'],
                'spes_status' => $validated['spes_status'],
                'mother_name' => $validated['mother_name'],
                'mother_occupation' => $validated['mother_occupation'],
                'mother_contact_no' => $validated['mother_contact_no'],
                'father_guardian_name' => $validated['father_guardian_name'],
                'father_occupation' => $validated['father_occupation'],
                'father_contact_no' => $validated['father_contact_no'],
                'messenger' => $validated['messenger'] ?? null,
                'facebook' => $validated['facebook'] ?? null,
                'resume' => $resumePath,
                'certificate_enrollment' => $enrollmentPath,
                'certificate_grade' => $gradePath,
                'application_letter' => $letterPath,
                'indigency' => $indigencyPath,
                'document_original_names' => $documentOriginalNames,
                'status' => 'pending',
            ]);
            $createdApplication = $application;

            $auditLogger->record(
                AuditAction::APPLICATION_SUBMITTED,
                'Application',
                $applicant,
                $applicant,
                $application,
                ['field_name' => 'status', 'old_value' => null, 'new_value' => 'pending'],
            );

            foreach ([
                'Birth Certificate' => $resumePath,
                'Certificate of Enrollment' => $enrollmentPath,
                'Certificate of Grades' => $gradePath,
                'Application Letter' => $letterPath,
                'Certificate of Indigency' => $indigencyPath,
            ] as $label => $path) {
                if ($path) {
                    $auditLogger->recordDocumentSubmitted($applicant, $application, $label);
                }
            }

            return $application;
        });

        $notifications->notifyApplicant($applicant, new ApplicantPortalUpdate(
            'notify_documents',
            'Application received',
            'Your SPES application has been received and is pending review by PESO.',
            [
                'application_id' => $createdApplication->id,
                'status' => 'pending',
                'event' => 'application_submitted',
            ],
        ), "application:{$createdApplication->id}:submitted:{$createdApplication->created_at?->getTimestamp()}");

        return redirect()->route('applications.myApplication')
            ->with('success', 'Your application has been submitted successfully!');
    }

    public function update(
        Request $request,
        AuditLogger $auditLogger,
        ApplicantNotificationService $notifications,
    )
    {
        $application = Application::where('user_id', Auth::id())->latest('created_at')->firstOrFail();
        $settings = SystemSetting::current();
        if ($application->status !== 'approved'
            && app(ApplicationApprovalCapacity::class)->isFull($settings)) {
            return redirect()->route('dashboard')
                ->with('error', 'The SPES approval limit has been reached. Your application cannot be resubmitted this season.')
                ->with('approval_capacity_closed', true);
        }
        $this->normalizeLegacyApplicationData($request);

        $documentRules = [
            'resume' => $application->resume && Storage::disk('public')->exists($application->resume)
                ? 'nullable|file|mimes:pdf|max:5120'
                : 'required|file|mimes:pdf|max:5120',
            'certificate_enrollment' => $application->certificate_enrollment && Storage::disk('public')->exists($application->certificate_enrollment)
                ? 'nullable|file|mimes:pdf|max:5120'
                : 'required|file|mimes:pdf|max:5120',
        ];

        $validated = $request->validate([
            'surname' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => ['required', 'string', 'min:2', 'max:255', 'not_regex:/^[A-Za-z]\.?$/'],
            'sex' => 'required|in:Male,Female',
            'birthday' => 'required|date|before:today',
            'age' => 'required|integer|min:15|max:30',
            'barangay' => 'required|string|max:100',
            'civil_status' => 'required|in:Single,Married,Widowed,Separated',
            'parent_status' => 'required|in:Both Parents Living,Solo Parent,Orphan,Guardian',
            'education' => 'required|string|max:100',
            'grade_year_level' => 'required|in:Grade 7,Grade 8,Grade 9,Grade 10,Grade 11,Grade 12,1st year,2nd year,4th year,5th year',
            'spes_status' => 'required|in:new,baby',
            'mother_name' => 'nullable|string|max:255',
            'mother_occupation' => 'nullable|string|max:255',
            'mother_contact_no' => 'nullable|string|max:20',
            'father_guardian_name' => 'nullable|string|max:255',
            'father_occupation' => 'nullable|string|max:255',
            'father_contact_no' => 'nullable|string|max:20',
            'messenger' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'resume' => $documentRules['resume'],
            'certificate_enrollment' => $documentRules['certificate_enrollment'],
            'certificate_grade' => 'nullable|file|mimes:pdf|max:5120',
            'application_letter' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'indigency' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ], [
            'middle_name.not_regex' => 'Please enter your complete middle name. Single initials such as A or A. are not accepted.',
        ]);

        $this->validateFamilyContact($request);
        $original = $application->getOriginal();
        $applicant = Auth::user();
        $submittedDocuments = [];
        $documentOriginalNames = $application->document_original_names ?? [];

        if ($request->hasFile('resume')) {
            if ($application->resume) {
                Storage::disk('public')->delete($application->resume);
            }
            $application->resume = $request->file('resume')->store('applications/resumes', 'public');
            $documentOriginalNames['resume'] = $request->file('resume')->getClientOriginalName();
            $submittedDocuments['Birth Certificate'] = true;
        }
        foreach (['certificate_enrollment' => 'applications/enrollment', 'certificate_grade' => 'applications/grades'] as $document => $directory) {
            if ($request->hasFile($document)) {
                if ($application->{$document}) {
                    Storage::disk('public')->delete($application->{$document});
                }
                $application->{$document} = $request->file($document)->store($directory, 'public');
                $documentOriginalNames[$document] = $request->file($document)->getClientOriginalName();
                if ($document === 'certificate_enrollment') {
                    $submittedDocuments['Certificate of Enrollment'] = true;
                } elseif ($document === 'certificate_grade') {
                    $submittedDocuments['Certificate of Grades'] = true;
                }
            }
        }
        if ($request->hasFile('application_letter')) {
            if ($application->application_letter) {
                Storage::disk('public')->delete($application->application_letter);
            }
            $application->application_letter = $request->file('application_letter')->store('applications/letters', 'public');
            $documentOriginalNames['application_letter'] = $request->file('application_letter')->getClientOriginalName();
            $submittedDocuments['Application Letter'] = true;
        }
        if ($request->hasFile('indigency')) {
            if ($application->indigency) {
                Storage::disk('public')->delete($application->indigency);
            }
            $application->indigency = $request->file('indigency')->store('applications/indigency', 'public');
            $documentOriginalNames['indigency'] = $request->file('indigency')->getClientOriginalName();
            $submittedDocuments['Certificate of Indigency'] = true;
        }

        $fullName = trim(implode(' ', array_filter([
            $validated['first_name'],
            str_replace('N/A', '', $validated['middle_name'] ?? ''),
            $validated['surname'],
        ], fn ($part) => ! empty(trim((string) $part)) && trim((string) $part) !== 'N/A')));

        $updates = array_merge($validated, [
            'full_name' => $fullName,
            'resume' => $application->resume,
            'certificate_enrollment' => $application->certificate_enrollment,
            'certificate_grade' => $application->certificate_grade,
            'application_letter' => $application->application_letter,
            'indigency' => $application->indigency,
            'document_original_names' => $documentOriginalNames,
        ]);

        $wasDenied = $application->status === 'denied';
        if ($wasDenied) {
            $updates['status'] = 'pending';
            $updates['admin_comment'] = null;
        }

        DB::transaction(function () use ($application, $updates, $original, $applicant, $submittedDocuments, $auditLogger): void {
            $application->update($updates);
            $auditedFields = [
                'full_name', 'surname', 'first_name', 'middle_name', 'sex', 'birthday', 'age',
                'barangay', 'civil_status', 'parent_status', 'education', 'grade_year_level', 'spes_status',
                'mother_name', 'mother_occupation', 'mother_contact_no', 'father_guardian_name',
                'father_occupation', 'father_contact_no', 'messenger', 'facebook', 'status', 'admin_comment',
            ];
            $newValues = array_intersect_key($application->getAttributes(), array_flip($auditedFields));
            $auditLogger->recordChanges(
                $original,
                $newValues,
                'Application Information',
                $applicant,
                $applicant,
                $application,
                AuditAction::APPLICATION_UPDATED,
                ['status_action' => AuditAction::APPLICATION_STATUS_CHANGED],
            );

            foreach (array_keys($submittedDocuments) as $documentLabel) {
                $auditLogger->recordDocumentSubmitted($applicant, $application, $documentLabel);
            }
        });

        if ($wasDenied) {
            $notifications->notifyApplicant($applicant, new ApplicantPortalUpdate(
                'notify_documents',
                'Application resubmitted',
                'Your updated SPES application has been submitted and is pending review by PESO.',
                [
                    'application_id' => $application->id,
                    'status' => 'pending',
                    'event' => 'application_resubmitted',
                ],
            ), "application:{$application->id}:resubmitted:{$application->updated_at?->getTimestamp()}");
        }

        return redirect()->route('applications.myApplication')
            ->with('success', 'Your application has been updated successfully!');
    }

    /**
     * Normalize legacy payloads that send old values or only full_name into the newer split-name structure.
     */
    private function normalizeLegacyApplicationData(Request $request): void
    {
        $statusMap = [
            'Both Parents' => 'Both Parents Living',
            'Both Parents Living Together' => 'Both Parents Living',
            'Single Parent' => 'Solo Parent',
        ];

        if ($request->filled('parent_status')) {
            $status = trim((string) $request->input('parent_status'));
            if (isset($statusMap[$status])) {
                $request->merge(['parent_status' => $statusMap[$status]]);
            }
        }

        if (
            $request->filled('surname') || $request->filled('first_name') || $request->filled('middle_name')
        ) {
            return;
        }

        if (! $request->filled('full_name')) {
            return;
        }

        $fullName = trim((string) $request->input('full_name'));
        $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) >= 3) {
            $request->merge([
                'surname' => array_pop($parts),
                'first_name' => array_shift($parts),
                'middle_name' => implode(' ', $parts) ?: 'N/A',
            ]);
        } elseif (count($parts) === 2) {
            $request->merge([
                'surname' => $parts[1],
                'first_name' => $parts[0],
                'middle_name' => 'N/A',
            ]);
        } else {
            $request->merge([
                'surname' => $fullName,
                'first_name' => $fullName,
                'middle_name' => 'N/A',
            ]);
        }
    }

    /**
     * Only Both Parents Living requires every family/contact field.
     * Solo Parent, Orphan, and Guardian may leave the entire section blank.
     */
    private function validateFamilyContact(Request $request): void
    {
        if ($request->input('parent_status') !== 'Both Parents Living') {
            return;
        }

        validator($request->all(), [
            'mother_name' => 'required',
            'mother_contact_no' => 'required',
            'mother_occupation' => 'required',
            'father_guardian_name' => 'required',
            'father_contact_no' => 'required',
            'father_occupation' => 'required',
        ], [
            '*.required' => 'This Family & Contact Information field is required when both parents are living.',
        ])->validate();
    }

    /**
     * User: show "My Application" page.
     */
    public function myApplication()
    {
        $application = Application::where('user_id', Auth::id())->latest('created_at')->first();

        return view('application.applications.my-application', compact('application'));
    }

    /**
     * View a submitted application document.
     */
    public function viewDocument(Application $application, string $document)
    {
        $this->authorizedDocumentPath($application, $document);

        $user = auth()->user();
        $backUrl = $user->role === 'admin'
            ? route('admin.applications.show', $application)
            : ($application->status === 'denied'
                ? route('applications.edit')
                : route('applications.myApplication'));

        return view('application.documents.preview', [
            'application' => $application,
            'document' => $document,
            'documentUrl' => route('applications.document.stream', [
                'application' => $application,
                'document' => $document,
            ]),
            'backUrl' => $backUrl,
            'documentTitle' => match ($document) {
                'resume' => __('Birth Certificate'),
                'certificate_enrollment' => __('Certificate of Enrollment'),
                'certificate_grade' => __('Certificate of Grades'),
                'application_letter' => __('Application Letter'),
                'indigency' => __('Certificate of Indigency'),
            },
        ]);
    }

    /**
     * Stream a submitted document to the built-in preview page or another viewer.
     */
    public function streamDocument(Application $application, string $document)
    {
        $path = $this->authorizedDocumentPath($application, $document);
        $filePath = Storage::disk('public')->path($path);
        $originalName = $application->document_original_names[$document] ?? basename($path);
        $fileName = basename(str_replace('\\', '/', $originalName));
        $fallbackName = Str::ascii($fileName) ?: 'application-document.pdf';

        return response()->file($filePath, [
            'Content-Disposition' => HeaderUtils::makeDisposition('inline', $fileName, $fallbackName),
        ]);
    }

    private function authorizedDocumentPath(Application $application, string $document): string
    {
        if (! in_array($document, ['resume', 'certificate_enrollment', 'certificate_grade', 'application_letter', 'indigency'], true)) {
            abort(404);
        }

        $user = auth()->user();
        if ($user->role !== 'admin' && $application->user_id !== $user->id) {
            abort(403);
        }

        $path = $application->{$document};
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return $path;
    }

    // -------------------------------------------------------
    // ADMIN SIDE
    // -------------------------------------------------------

    /**
     * Admin: list all applications.
     */
    public function index(Request $request, ApplicationApprovalCapacity $capacity)
    {
        $query = Application::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('barangay')) {
            $query->where('barangay', $request->barangay);
        }
        if ($request->filled('search')) {
            $query->where('full_name', 'like', '%'.$request->search.'%');
        }

        $applications = $query->paginate(15);

        // Stats for the top cards
        $stats = [
            'total' => Application::count(),
            'pending' => Application::where('status', 'pending')->count(),
            'approved' => Application::where('status', 'approved')->count(),
            'denied' => Application::where('status', 'denied')->count(),
        ];
        $settings = SystemSetting::current();
        $approvalCapacity = [
            'approved' => $capacity->approvedCount($settings),
            'limit' => $settings->approved_applicant_limit,
            'full' => $capacity->isFull($settings),
        ];

        return view('admin.applications', compact('applications', 'stats', 'approvalCapacity'));
    }

    /**
     * Admin: view a single application's full details.
     */
    public function show(
        Application $application,
        AuditLogger $auditLogger,
        ApplicationApprovalCapacity $capacity,
    )
    {
        $application->load('user.profile', 'additionalRequirementSubmissions.requirement');
        DB::transaction(fn () => $auditLogger->record(
            AuditAction::APPLICANT_VIEWED,
            'Applicant Information',
            Auth::user(),
            $application->user,
            $application,
            ['description' => 'Application record viewed'],
        ));

        $reviewSuggestions = app(ApplicationReviewSuggestions::class)->forApplication($application);
        $settings = SystemSetting::current();
        $approvalCapacity = [
            'approved' => $capacity->approvedCount($settings),
            'limit' => $settings->approved_applicant_limit,
            'full' => $capacity->isFull($settings),
        ];

        return view('admin.application-detail', compact('application', 'reviewSuggestions', 'approvalCapacity'));
    }

    /**
     * Admin: approve an application.
     */
    public function approve(
        Application $application,
        AuditLogger $auditLogger,
        ApplicationApprovalCapacity $capacity,
        ApplicantNotificationService $notifications,
    )
    {
        $settings = SystemSetting::current();
        $approved = false;
        $justApproved = false;
        $capacityReached = false;
        $newlyDenied = collect();

        DB::transaction(function () use (
            $application,
            $auditLogger,
            $capacity,
            $settings,
            &$approved,
            &$justApproved,
            &$capacityReached,
            &$newlyDenied,
        ): void {
            $lockedSettings = SystemSetting::query()->lockForUpdate()->findOrFail($settings->id);
            $lockedApplication = Application::query()->lockForUpdate()->with('user')->findOrFail($application->id);

            if ($lockedApplication->status === 'approved') {
                $approved = true;
                return;
            }

            if ($capacity->isFull($lockedSettings)) {
                $capacityReached = true;
                $newlyDenied = $capacity->denyRemainingPending(
                    $lockedSettings,
                    Auth::user(),
                    $auditLogger,
                );
                return;
            }

            $oldStatus = $lockedApplication->status;
            $lockedApplication->update(['status' => 'approved']);
            $auditLogger->record(
                AuditAction::APPLICATION_APPROVED,
                'Application',
                Auth::user(),
                $lockedApplication->user,
                $lockedApplication,
                [
                    'field_name' => 'status',
                    'old_value' => $oldStatus,
                    'new_value' => 'approved',
                    'description' => $lockedApplication->admin_comment,
                ],
            );
            $application->setRawAttributes($lockedApplication->getAttributes(), true);
            $application->setRelation('user', $lockedApplication->user);
            $approved = true;
            $justApproved = true;

            if ($capacity->isFull($lockedSettings)) {
                $capacityReached = true;
                $newlyDenied = $capacity->denyRemainingPending($lockedSettings, Auth::user(), $auditLogger);
            }
        });

        if (! $approved && $capacityReached) {
            $capacity->notifyClosedApplicants(
                $newlyDenied,
                (int) $settings->fresh()->approved_applicant_limit,
                $notifications,
            );

            return back()
                ->with('error', 'The approved-applicant limit has been reached. This application was not approved.')
                ->with('approval_limit_notice', 'The approved-applicant limit has been reached. Further approvals and new submissions are closed.');
        }

        if ($justApproved) {
            $notifications->notifyApplicant($application->user, new ApplicantPortalUpdate(
                'notify_documents',
                'Application approved',
                'Your SPES application has been approved. Review your application status for next steps.',
                ['application_id' => $application->id, 'status' => 'approved', 'event' => 'application_approved'],
            ), "application:{$application->id}:approved:{$application->updated_at?->getTimestamp()}");
        }

        if ($capacityReached) {
            $capacity->notifyClosedApplicants(
                $newlyDenied,
                (int) $settings->fresh()->approved_applicant_limit,
                $notifications,
            );
            return back()
                ->with('success', "Application for {$application->full_name} has been approved. The approved-applicant limit has now been reached.")
                ->with('approval_limit_notice', 'The approved-applicant limit has been reached. New submissions and approvals are closed, and remaining pending applicants were notified.');
        }

        return back()->with('success', "Application for {$application->full_name} has been approved.");
    }

    /**
     * Admin: deny an application.
     */
    public function deny(
        Application $application,
        AuditLogger $auditLogger,
        ApplicantNotificationService $notifications,
    )
    {
        if ($application->status !== 'denied') {
            DB::transaction(function () use ($application, $auditLogger): void {
                $oldStatus = $application->status;
                $application->update(['status' => 'denied']);
                $auditLogger->record(
                    AuditAction::APPLICATION_REJECTED,
                    'Application',
                    Auth::user(),
                    $application->user,
                    $application,
                    [
                        'field_name' => 'status',
                        'old_value' => $oldStatus,
                        'new_value' => 'denied',
                        'description' => $application->admin_comment,
                    ],
                );
            });
            $notifications->notifyApplicant($application->user, new ApplicantPortalUpdate(
                'notify_documents',
                'Application denied',
                'Your SPES application was not approved. Review the remarks in your application status for more information.',
                ['application_id' => $application->id, 'status' => 'denied', 'event' => 'application_denied'],
            ), "application:{$application->id}:denied:{$application->updated_at?->getTimestamp()}");
        }

        return back()->with('success', "Application for {$application->full_name} has been denied.");
    }

    /**
     * Admin: save a comment/feedback on an application.
     */
    public function addComment(
        Request $request,
        Application $application,
        AuditLogger $auditLogger,
        ApplicantNotificationService $notifications,
    )
    {
        $request->validate([
            'admin_comment' => 'required|string|max:2000',
        ]);

        $commentChanged = $application->admin_comment !== $request->admin_comment;
        if ($commentChanged) {
            DB::transaction(function () use ($application, $request, $auditLogger): void {
                $oldComment = $application->admin_comment;
                $application->update(['admin_comment' => $request->admin_comment]);
                $auditLogger->record(
                    AuditAction::REMARKS_ADDED,
                    'Application',
                    Auth::user(),
                    $application->user,
                    $application,
                    [
                        'field_name' => 'admin_comment',
                        'old_value' => $oldComment,
                        'new_value' => $request->admin_comment,
                        'description' => $request->admin_comment,
                    ],
                );
            });
        }

        if ($commentChanged) {
            $notifications->notifyApplicant($application->user, new ApplicantPortalUpdate(
                'notify_documents',
                'Document requirement update',
                "PESO left a document requirement update for your application: {$application->admin_comment}",
                ['application_id' => $application->id, 'event' => 'application_feedback'],
            ), "application:{$application->id}:feedback:{$application->updated_at?->getTimestamp()}");
        }

        return back()->with('success', 'Comment saved successfully.');
    }

    /**
     * Admin: view all registered users.
     */
    public function users()
    {
        $users = User::with([
            'profile',
            'applications' => fn ($query) => $query->latest('created_at'),
        ])->where('role', 'user')->latest()->paginate(20);
        $totalUsers = User::where('role', 'user')->count();

        return view('admin.users', compact('users', 'totalUsers'));
    }

    /**
     * Admin: view a registered user's profile and application history.
     */
    public function showUser(Request $request, User $user, AuditLogger $auditLogger)
    {
        $user->load([
            'profile',
            'applications' => fn ($query) => $query->latest('created_at'),
        ]);
        DB::transaction(fn () => $auditLogger->record(
            AuditAction::APPLICANT_VIEWED,
            'Applicant Information',
            Auth::user(),
            $user->role === 'user' ? $user : null,
            $user->applications->first(),
            ['description' => 'Applicant profile viewed'],
        ));

        return view('admin.user-profile', [
            'user' => $user,
            'returnSearch' => $request->query('search'),
        ]);
    }
}
