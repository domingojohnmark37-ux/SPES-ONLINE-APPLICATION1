<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalizedSecurityVerification extends Model
{
    public const SUGGESTED_QUESTIONS = [
        'What was the name of your first school?',
        'What was the nickname only your family used for you?',
        'What was the name of your first pet?',
        'What is a personal phrase that helps you remember an important moment?',
        'What was the first book you remember receiving as a gift?',
        'What was the name of a childhood friend you remember fondly?',
        'What was the first place you visited that was special to you?',
        'What was the name of your favorite childhood fictional character?',
        'What word did you often mispronounce as a child?',
        'What was the first non-school skill you taught yourself?',
        'What was the title of a song that reminds you of a personal memory?',
        'What was the name of the first game you enjoyed playing?',
        'What was the first thing you bought using your own money?',
        'What is a made-up word or phrase that only you would remember?',
        'What personal code word would you choose as your private answer?',
    ];

    protected $hidden = ['answer_hash'];

    protected $fillable = [
        'user_id',
        'question',
        'answer_hash',
        'enabled_at',
    ];

    protected function casts(): array
    {
        return ['enabled_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
