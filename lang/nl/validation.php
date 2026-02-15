<?php

return [
    'min' => [
        'string' => 'Dit veld moet minimaal :min karakters bevatten.',
    ],
    'required' => 'Dit veld is verplicht.',

    // Custom validation rules
    'email' => [
        'unique' => 'Aan dit e-mailadres is al een account gekoppeld.',
    ],
    'allowed_username' => [
        'reserved' => 'Deze gebruikersnaam is niet toegestaan.',
        'profane' => 'Deze gebruikersnaam is niet toegestaan.',
    ],
    'username' => [
        'available' => 'Gebruikersnaam is beschikbaar!',
        'unique' => 'Deze gebruikersnaam is al in gebruik.',
    ],
    'password' => [
        'letters' => 'Wachtwoord moet minimaal één letter bevatten.',
        'min' => 'Wachtwoord moet minimaal :min tekens bevatten.',
        'mixed' => 'Wachtwoord moet minimaal één hoofdletter bevatten.',
        'numbers' => 'Wachtwoord moet minimaal één cijfer bevatten.',
        'symbols' => 'Wachtwoord moet minimaal één speciaal teken bevatten.',
        'live' => [
            'letters' => 'Minimaal één letter',
            'min' => 'Minimaal :min tekens',
            'mixed' => 'Minimaal één hoofdletter',
            'numbers' => 'Minimaal één cijfer',
            'symbols' => 'Minimaal één speciaal teken',
        ]
    ],
    'terms' => [
        'accepted' => 'Je moet akkoord gaan met de voorwaarden.',
    ],
];