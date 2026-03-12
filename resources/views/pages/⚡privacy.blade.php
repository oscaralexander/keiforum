<?php

use Livewire\Component;

new class extends Component
{
    public function render()
    {
        return $this->view()
            ->title('Privacybeleid');
    }
};
?>

<div>
    <x-header hide-path title="Privacybeleid" />
    <div class="panel panel--padded">
        <div class="formatted">
            <p>Je privacy is belangrijk. Deze pagina legt uit welke gegevens we verzamelen, waarom we dat doen, en wie wat te zien krijgt. Geen lange lappen juridische tekst, geen kleine lettertjes — gewoon lekker leesbaar.</p>
            <p><i>Laatst bijgewerkt: maart 2026</i></p>

            <h3>Wie is verantwoordelijk?</h3>
            <p>Keiforum is verantwoordelijk voor de verwerking van jouw persoonsgegevens. Heb je vragen of wil je iets weten? Stuur een mail naar <a href="mailto:mail@keiforum.nl">mail@keiforum.nl</a>.</p>

            <h3>Welke gegevens verzamelen we?</h3>
            <p>Als je een account aanmaakt, vragen we om een aantal gegevens. Sommige zijn verplicht, andere optioneel:</p>
            <ul>
                <li><b>E-mailadres</b> — verplicht. We gebruiken dit om in te loggen, je wachtwoord te herstellen en je te informeren over je account.</li>
                <li><b>Volledige naam</b> — optioneel. Je echte naam, als je die wilt delen.</li>
                <li><b>Geboortedatum</b> — optioneel. Zodat andere leden een idee hebben met wie ze praten.</li>
                <li><b>Woonwijk of buurt</b> — optioneel. Handig op een lokaal forum.</li>
                <li><b>Geslacht</b> — optioneel. Helemaal aan jou.</li>
            </ul>
            <p>Daarnaast slaan we technische gegevens op die nodig zijn voor het functioneren van het forum, zoals je inlogtijdstip en IP-adres bij registratie.</p>

            <h3>Wie ziet wat?</h3>
            <p>Niet alles is voor iedereen zichtbaar. Hier is hoe het werkt:</p>
            <ul>
                <li><b>Je e-mailadres</b> is nooit zichtbaar voor andere gebruikers. Wij gebruiken het alleen intern.</li>
                <li><b>Je gebruikersnaam</b> is openbaar — die staat bij je berichten en is ook zichtbaar voor niet-ingelogde bezoekers.</li>
                <li><b>Je volledige naam, geboortedatum, woonwijk en geslacht</b> zijn alleen zichtbaar voor <i>andere ingelogde leden</i>. Niet-ingelogde bezoekers zien deze gegevens niet.</li>
            </ul>

            <h3>Waarom verzamelen we deze gegevens?</h3>
            <p>We vragen alleen wat we echt nodig hebben:</p>
            <ul>
                <li>Je e-mailadres is nodig om je account te beheren en je te bereiken bij problemen.</li>
                <li>De overige profielgegevens helpen de community persoonlijker en lokaler te maken — je weet beter met wie je praat als je weet dat iemand ook in Soesterkwartier woont.</li>
            </ul>
            <p>We gebruiken je gegevens nooit voor advertenties en verkopen ze nooit aan derden.</p>

            <h3>Hoe lang bewaren we je gegevens?</h3>
            <p>Zolang je account actief is, bewaren we je gegevens. Wil je dat we alles verwijderen? Stuur een mail naar <a href="mailto:mail@keiforum.nl">mail@keiforum.nl</a> en we regelen het zo snel mogelijk. Na verwijdering zijn je berichten op het forum niet meer aan jou gekoppeld.</p>

            <h3>Cookies</h3>
            <p>We gebruiken alleen functionele cookies — dat zijn de cookies die nodig zijn om het forum te laten werken, zoals het bijhouden van je inlogsessie. We plaatsen geen tracking- of advertentiecookies.</p>

            <h3>Jouw rechten</h3>
            <p>Op basis van de AVG heb je een aantal rechten:</p>
            <ul>
                <li><b>Inzage</b> — je kunt opvragen welke gegevens we van je hebben.</li>
                <li><b>Correctie</b> — je kunt onjuiste gegevens laten aanpassen. Dat kun je grotendeels zelf doen via je profielpagina.</li>
                <li><b>Verwijdering</b> — je kunt vragen om je account en gegevens te verwijderen.</li>
                <li><b>Bezwaar</b> — je kunt bezwaar maken tegen de verwerking van je gegevens.</li>
            </ul>
            <p>Stuur voor al het bovenstaande een mail naar <a href="mailto:mail@keiforum.nl">mail@keiforum.nl</a>. We reageren binnen 30 dagen.</p>

            <h3>Beveiliging</h3>
            <p>We doen ons best om je gegevens goed te beveiligen. Wachtwoorden worden versleuteld opgeslagen en we maken gebruik van een beveiligde verbinding (HTTPS). Mocht er toch iets misgaan, dan laten we je dat zo snel mogelijk weten.</p>

            <h3>Wijzigingen</h3>
            <p>Als we dit beleid aanpassen, communiceren we dat via het forum. De datum bovenaan geeft aan wanneer het voor het laatst is gewijzigd.</p>

            <h3>Vragen?</h3>
            <p>Kom je er niet uit, of wil je gebruik maken van je rechten? Stuur een mail naar <a href="mailto:mail@keiforum.nl">mail@keiforum.nl</a>. We helpen je graag.</p>
        </div>
    </div>
</div>
