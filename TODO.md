# To-do: het forum tot leven brengen

## Nu: Nieuwsplein33-samenwerking

Elk artikel uit de feed krijgt een topic in het forum "Nieuws", geplaatst door het account "Nieuwsplein33". Claude beoordeelt elk artikel:

- **Goedgekeurd**: topic direct zichtbaar
- **Neutraal**: topic verborgen tot de eerste reactie
- **Geblokkeerd** (slachtoffers, strafzaken, geen discussiestof): geen topic

Gideon linkt onder elk artikel naar `keiforum.nl/praat-mee/{artikel-id}`.

- [x] Feed elk kwartier ophalen, inclusief `description` en artikel-id
- [x] Beoordeling door Claude (goedgekeurd / neutraal / geblokkeerd) en reden opslaan
- [x] Botaccount "Nieuwsplein33" en forum "Nieuws"
- [x] Topic aanmaken met samenvatting, openingsvraag en "Lees meer"-link
- [x] Zichtbaarheid: `is_visible` op topics, bijwerken bij nieuwe en verwijderde reacties
- [x] Verborgen topics weren uit index, forumlijsten, gebiedspagina's, profielen en sitemap
- [x] Verborgen topics: `noindex` en geen gestructureerde data
- [x] `/praat-mee/{artikel-id}`: doorsturen naar topic, feed opnieuw ophalen bij onbekend artikel, terugval op forum "Nieuws"
- [x] Terugkeren naar het topic na inloggen, registreren (ook via Google) en accountactivatie
- [x] `ANTHROPIC_API_KEY` instellen op productie
- [ ] Afspraken met Gideon op schrift (titels, intro, afbeeldingen)
- [ ] Gideon de link laten plaatsen onder elk artikel

## Agenda (branch `agenda`)

Self-service agenda waarop bars, theaters en andere locaties hun eigen activiteiten plaatsen. Elke venue heeft een eigen account; plaatsen gaat geld kosten via Stripe.

- [x] Activiteiten met titel, beschrijving, categorie, datum en tijd, locatie, prijs en poster (A4)
- [x] Overzicht per dag, per categorie (`/agenda/club`), per venue (`/agenda/@venue`) en beide
- [x] Detailpagina (`/agenda/@venue/2026-10-14/slug`) met gestructureerde data voor Google
- [x] "Ik ben erbij" en "Ik ga misschien"
- [x] Activiteiten toevoegen (`/agenda/nieuw`)
- [x] Komende activiteiten in de sitemap
- [x] Places-autocomplete via `GOOGLE_API_KEY` (lokaal getest)
- [ ] `GOOGLE_API_KEY` op productie: Maps JavaScript API + Places API (New) aan en `keiforum.nl` toegestaan
- [ ] Lokaal in de browser testen, ook op mobiel en met Places-autocomplete
- [ ] Activiteiten bewerken en verwijderen (policy bestaat al)
- [ ] Seeder met voorbeeldactiviteiten
- [ ] Stripe: prijsmodel bepalen (per activiteit, bundel of abonnement)
- [ ] Stripe: Cashier of Checkout kiezen en de dependency toevoegen
- [ ] Stripe: activiteit pas publiceren na betaling (`published_at` vullen via webhook)
- [ ] Stripe: bevestigingsmail en factuur naar de venue
- [ ] Overzicht "Mijn activiteiten" voor venues
- [ ] Venueprofiel: naam, logo en adres bij het account (nu alleen gebruikersnaam)
- [ ] Moderatie: activiteiten kunnen melden en door een admin laten verwijderen
- [ ] Agenda opnemen in de wekelijkse e-mailsamenvatting
- [ ] Agenda op de homepage tonen (bijv. "Vandaag en morgen")
- [ ] Oude posters opruimen van afgelopen of verwijderde activiteiten
- [ ] Migraties draaien op productie bij de deploy

## Search Console

- [x] Na deploy: "Oplossing valideren" bij "Dubbele pagina zonder door de gebruiker geselecteerde canonieke versie"
- [ ] Na deploy: "Oplossing valideren" bij de `userInteractionCount`-melding (Discussieforum)
- [ ] Na deploy: sitemap opnieuw indienen
- [x] Over een paar weken: aantal niet-geïndexeerde pagina's opnieuw bekijken (26-10: 268 niet / 107 wel; ~200 terecht)
- [x] Inloglinks `nofollow`, `/auth/` uitgesloten in `robots.txt`, profielen pas indexeren vanaf 3 berichten
- [ ] Email Obfuscation uitzetten in Cloudflare (404 op `/cdn-cgi/l/email-protection`)
- [ ] Indexering aanvragen voor belangrijke onderwerpen en nieuwsberichten die "Gevonden/Gecrawld - momenteel niet geïndexeerd" zijn

## Bouwen

- [x] **Beveiliging:** post bodies saniteren met `HtmlSanitizer` en berichten in gesprekken escapen (stored XSS)
- [ ] De 28 al falende tests repareren (o.a. gesprekken, image proxy, gebiedspagina's)

- [x] E-mailmelding bij een reactie op je topic of een @vermelding (bestond al; nu opt-out)
- [x] Wekelijkse e-mailsamenvatting (populairste en nieuwe topics; nog zonder agenda)
- [ ] Uitnodigende lege-staatweergave op rustige pagina's
- [x] Open Graph-tags per topic (mooie preview op WhatsApp/Facebook)
- [x] Controleren dat topics zonder inloggen te lezen zijn (SEO)
- [ ] Nagaan of accountactivatie vóór de eerste post echt nodig is

## Inhoud

- [ ] Dagelijks zelf een paar topics posten, liefst als vraag
- [ ] 5–10 bekenden vragen om mee te doen (vooral reageren)
- [ ] Lokaal nieuws plaatsen (gemeente, wegwerkzaamheden, nieuwe zaken)
- [ ] Aantal fora beperken tot 3–4; pas splitsen als het druk wordt
- [ ] Elk nieuw topic binnen een uur een eerste reactie geven

## Promotie

- [ ] Verenigingen, bibliotheek, buurthuis en sportclubs benaderen voor de agenda
- [ ] Af en toe een interessant topic delen in lokale Facebookgroepen / Nextdoor
- [ ] Flyer of poster bij supermarkt en buurthuis
- [ ] Stukje in de lokale krant
- [ ] Lokale ondernemers uitnodigen om vragen te beantwoorden of zich voor te stellen
