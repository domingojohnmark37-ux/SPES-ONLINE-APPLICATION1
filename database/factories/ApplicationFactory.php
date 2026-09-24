<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        $birthday = fake()->dateTimeBetween('-25 years', '-18 years');
        $firstName = fake()->firstName();
        $middleName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'ref_id' => 'SPES-' . strtoupper(Str::random(8)),
            'user_id' => User::factory(),
            'surname' => $lastName,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'full_name' => trim("{$firstName} {$middleName} {$lastName}"),
            'sex' => fake()->randomElement(['Male', 'Female']),
            'birthday' => $birthday->format('Y-m-d'),
            'age' => (int) abs(now()->diffInYears($birthday)),
            'barangay' => fake()->randomElement(['Alibago', 'Bical', 'Centro Norte', 'Centro Sur', 'Dungeg']),
            'civil_status' => fake()->randomElement(['Single', 'Married', 'Widowed', 'Separated']),
            'parent_status' => fake()->randomElement(['Both Parents Living', 'Solo Parent', 'Orphan', 'Guardian']),
            'education' => fake()->randomElement(['Senior High School Graduate', 'College (Currently Enrolled)', 'College Graduate']),
            'spes_status' => fake()->randomElement(['new', 'baby']),
            'mother_name' => fake()->name('female'),
            'father_guardian_name' => fake()->name('male'),
            'contact_no' => '09' . fake()->numerify('#########'),
            'messenger' => fake()->optional()->userName(),
            'resume' => null,
            'application_letter' => null,
            'indigency' => null,
            'status' => 'pending',
            'admin_comment' => null,
            'forms_step' => 0,
        ];
    }
}
