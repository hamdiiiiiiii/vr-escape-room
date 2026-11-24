<?php

namespace App\Http\Requests;

use App\Services\AvatarService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Only ids from config/avatar.php are accepted: there is no free text, so a name can never be inappropriate. */
class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isStudent();
    }

    public function rules(): array
    {
        $a = app(AvatarService::class);

        return [
            'head' => ['required', Rule::in($a->ids('heads'))],
            'skin_tone' => ['required', Rule::in($a->ids('skin_tones'))],
            'hair_color' => ['required', Rule::in($a->ids('hair_colors'))],
            'outfit' => ['required', Rule::in($a->ids('outfits'))],
            'headgear' => ['required', Rule::in($a->ids('headgear'))],
            'gear' => ['required', Rule::in($a->ids('gear'))],
            'held' => ['required', Rule::in($a->ids('held'))],
            'suit_color' => ['required', Rule::in($a->ids('suit_colors'))],
            'team_color' => ['required', Rule::in($a->ids('team_colors'))],
            'name_first' => ['required', Rule::in($a->ids('name_words'))],
            'name_second' => ['nullable', Rule::in($a->ids('name_words'))],
            'name_number' => ['nullable', 'integer', 'between:1,99'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $a = app(AvatarService::class);
            $callsign = $a->callsign(
                $this->input('name_first'),
                $this->input('name_second') ?: null,
                $this->filled('name_number') ? (int) $this->input('name_number') : null
            );
            if ($a->callsignTaken($callsign, $this->user()->id)) {
                $validator->errors()->add('name_first', $callsign.' is already taken. Change a word or the number.');
            }
        }];
    }
}
