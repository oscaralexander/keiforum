<?php

return [
    'text' => 'Heb je al een account? <a href=":login_url" wire:navigate>Log hier in.</a>',
    'title' => 'Registreren',
    'form' => [
        'area_id' => [
            'description' => 'Alleen zichtbaar voor leden.',
            'empty' => 'Deel ik liever niet',
            'label' => 'In welke buurt woon je?',
        ],
        'birthdate' => [
            'description' => 'Alleen zichtbaar voor leden.',
            'label' => 'Geboortedatum',
        ],
        'email' => [
            'label' => 'Email',
        ],
        'gender' => [
            'description' => 'Alleen zichtbaar voor leden.',
            'empty' => 'Deel ik liever niet',
            'label' => 'Geslacht',
        ],
        'name' => [
            'description' => 'Alleen zichtbaar voor leden.',
            'label' => 'Naam',
        ],
        'password' => [
            'label' => 'Wachtwoord',
        ],
        'submit' => 'Aanmelden',
        'terms' => [
            'label' => 'Ik ga akkoord met de <a href=":terms_url" target="_blank">algemene voorwaarden</a>',
        ],
        'username' => [
            'description' => 'Alleen kleine letters, cijfers en underscores.',
            'label' => 'Gebruikersnaam',
        ],
    ],
    'activation_email_sent_title' => 'Gelukt!',
    'activation_email_sent_callout' => 'We hebben een activatielink naar je email verstuurd. Klik op de link in de email om je account te activeren. Niks ontvangen? <a href="mailto:mail@keiforum.nl">Neem contact met ons op.</a>',
];
