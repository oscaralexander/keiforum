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

## Bouwen

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
