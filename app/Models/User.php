<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Application;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, MustVerifyEmailTrait, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'last_active_at',
        'profile_photo',
        'last_name',
        'first_name',
        'middle_name',
        'sex',
        'date_of_birth',
        'place_of_birth',
        'status',
        'citizenship',
        'social_media',
        'gsis_beneficiary',
        'contact_number',
        'present_address',
        'permanent_address',
        'applicant_category',
        'education_history',
        'father_name',
        'father_contact_number',
        'father_occupation',
        'mother_name',
        'mother_contact_number',
        'mother_occupation',
        'parent_status_details',
        'special_skills',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'education_history' => 'array',
            'parent_status_details' => 'array',
        ];
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo ? asset('storage/' . $this->profile_photo) : null;
    }

    /**
     * A user may have many applications.
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function adminPreference(): HasOne
    {
        return $this->hasOne(AdminPreference::class);
    }

    public function activityStatus(): string
    {
        return $this->last_active_at && $this->last_active_at->greaterThanOrEqualTo(now()->subYear())
            ? 'Active'
            : 'Inactive';
    }

    public function displayStatus(?string $applicationStatus = null): string
    {
        return strtolower((string) $applicationStatus) === 'pending'
            ? 'Pending'
            : $this->activityStatus();
    }

    public static function applicantCategoryLabel(?string $category): ?string
    {
        return match ($category) {
            'student' => 'Student',
            'out_of_school_youth' => 'Out-of-School Youth',
            'working_student' => 'Working Student',
            default => $category,
        };
    }

    public function activityDescription(): string
    {
        if (!$this->last_active_at) {
            return 'Never active';
        }

        return ($this->activityStatus() === 'Active' ? 'Active ' : 'Logged out ')
            . $this->last_active_at->diffForHumans();
    }
}
