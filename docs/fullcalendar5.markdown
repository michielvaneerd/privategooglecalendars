---
layout: page
title: FullCalendar 5
permalink: /fullcalendar5/
---

## FullCalendar 5

### TODO:
- Als je eventColor zet, dan deze ook doorvoeren bij custom event colors!
- Dit geldt ook voor de eventTextColor: deze wordt bij custom event colors volgens mij niet overruled.
- "eventColor": "red",
  "eventTextColor": "white",
  "eventDisplay": "block"


## Color:

- Als je events een bepaalde kleur geeft in de Google calendar, dan MOET je eerst Get colorlist kiezen in de settings pagina.
Want die download de kleuren voor je custom events.

Deze plugin gebruikt FullCalendar 5 om de Google agenda's weer te geven. Alle eigenschappen die je aan FullCalendar 5 kunt meegeven, kun je ook meegeven aan deze plugin.
Zie voor alle eigenschappen de <a href="https://fullcalendar.io/docs">FullCalendar 5 documentatie</a>.
Als je de shortcode gebruikt, houdt dan rekening met het omschrijven van de eigenschappen (zie homepage).

## Afmeting

### Vaste hoogte met eventueel scrollbars

Dit is de standaard instelling. De calendar heeft een hoogte op basis van de aspectRatio. Dus de gehele width wordt gebruikt en op basis daarvan wordt bepaald wat de hoogte is
van de rijen.

### Vaste hoogte zonder scrollbars

Zet hiervoor de `dayMaxEvents` op `true`. Als het er meer zijn, zie je de "+ x more" link.
A.h.v. de 'height' setting wordt dan bepaald of er een + link getoond moet worden.
Bijv. als height '800px' is, dan worden er zoveel mogelijk events getoond en als dat niet meer kan, dan een + link.
Als je de height NIET zet, dan zie je waarschijnlijk maar max 1 event in een dag.

### Flexibele hoogte zonder scrollbars

Zet hiervoor de `height` op `auto`.

## Localiseren

Standaard wordt de locale van de Wordpress installatie gebruikt. Maar je kunt deze overrulen door de `locale` te zetten, bijv. op `nl-NL` of `en-US`.
Hiermee worden verschillende onderdelen van de calendar vertaald en aangepast, zoals:
- Namen van de dagen en maanden.
- Hoe de datum en tijd wordt geschreven.
- Wat de eerste dag van de week is.

## Themes

Je kunt een van de bestaande themes selecteren in de instellingen pagina. Je kunt ook zelf een theme maken, of een theme aanpassen,
door in WP zelf wat CSS toe tevoegen. Tip: kijk in de CSS van een bestaande theme welke classes je kunt aanpassen.