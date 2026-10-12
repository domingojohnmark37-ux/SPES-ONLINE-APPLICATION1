@props([
    'id' => 'personal_security_question',
    'selected' => null,
    'autofocus' => false,
])

@php
    $selectedQuestion = old('question', $selected);
    $isLegacyQuestion = filled($selectedQuestion)
        && ! in_array($selectedQuestion, \App\Models\PersonalizedSecurityVerification::SUGGESTED_QUESTIONS, true);
@endphp

<label for="{{ $id }}">{{ __('Your security question') }}</label>
<div class="security-question-picker">
    <select
        id="{{ $id }}"
        name="question"
        required
        @if($autofocus) autofocus @endif
    >
        <option value="" disabled @selected(! filled($selectedQuestion))>{{ __('Choose a security question') }}</option>
        @if($isLegacyQuestion)
            <option value="{{ $selectedQuestion }}" @selected($selectedQuestion === old('question', $selected))>
                {{ __('Previously saved question: :question', ['question' => $selectedQuestion]) }}
            </option>
        @endif
        @foreach(\App\Models\PersonalizedSecurityVerification::SUGGESTED_QUESTIONS as $question)
            <option value="{{ $question }}" @selected($selectedQuestion === $question)>{{ $question }}</option>
        @endforeach
    </select>
</div>
