<?php

namespace App\Exports;

use App\Models\Application;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ApplicationsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Application::query()->with('user.profile');

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['barangay'])) {
            $query->where('barangay', $this->filters['barangay']);
        }

        if (!empty($this->filters['spes_status'])) {
            $query->where('spes_status', $this->filters['spes_status']);
        }

        if (!empty($this->filters['search'])) {
            $query->where('full_name', 'like', '%' . $this->filters['search'] . '%');
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Reference ID',
            'Full Name',
            'Surname',
            'First Name',
            'Middle Name',
            'Sex',
            'Birthday',
            'Age',
            'Barangay',
            'Civil Status',
            'SPES Type',
            'Contact Number',
            'Status',
            'Submitted Date',
        ];
    }

    public function map($application): array
    {
        return [
            $application->ref_id,
            $application->full_name,
            $application->surname,
            $application->first_name,
            $application->middle_name,
            $application->sex,
            $application->birthday,
            $application->age,
            $application->barangay,
            $application->civil_status,
            $application->spes_status === 'new' ? 'New' : 'SPES Baby',
            $application->contact_no ?: ($application->user?->profile?->contact_number ?: ($application->mother_contact_no ?: $application->father_contact_no)),
            ucfirst($application->status),
            $application->created_at->format('Y-m-d H:i'),
        ];
    }
}
