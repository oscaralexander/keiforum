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
            'description' => 'Zichtbaar voor niemand.',
            'label' => 'E-mail',
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
    'activation_email_sent_text' => 'We hebben een activatielink naar je e-mail verstuurd. Klik op de link in de mail om je account te activeren. Niks ontvangen? Check even je spam en <a href="mailto:mail@keiforum.nl">neem contact met ons op.</a>',
];
