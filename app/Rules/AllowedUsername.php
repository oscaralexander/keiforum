<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AllowedUsername implements ValidationRule
{
    /**
     * Reserved usernames that cannot be used.
     *
     * @var array<string>
     */
    protected array $reservedUsernames = [
        'admin',
        'administrator',
        'api',
        'bolsius',
        'burgermeester',
        'email',
        'false',
        'help',
        'lucas.bolsius',
        'mail',
        'mod',
        'moderator',
        'null',
        'root',
        'support',
        'system',
        'test',
        'testing',
        'true',
        'undefined',
        'www',
    ];

    /**
     * Profane words that cannot be used in usernames.
     *
     * @var array<string>
     */
    protected array $profaneWords = [
        'fuck',
        'kanker',
    ];

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        $username = strtolower($value);

        // Check for invalid characters
        if (!preg_match('/^[a-z0-9_]+$/', $username)) {
            $fail(__('validation.allowed_username.invalid_characters'));
            return;
        }

        // Check for reserved usernames
        if (in_array($username, array_map('strtolower', $this->reservedUsernames))) {
            $fail(__('validation.allowed_username.reserved'));
            return;
        }

        // Check for profane words
        foreach ($this->profaneWords as $word) {
            if (str_contains($username, strtolower($word))) {
                $fail(__('validation.allowed_username.profane'));
                return;
            }
        }
    }
}
