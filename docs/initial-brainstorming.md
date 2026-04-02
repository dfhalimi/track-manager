# Track Manager – Fachliche Anforderungen

## 1. Ziel der Anwendung

Die Anwendung dient dazu, Tracks, ihre Produktionsschritte und zugehörige Release-Projekte an einem Ort zu verwalten.

Der Benutzer soll:

- schnell neue Tracks anlegen können
- den Produktionsfortschritt pro Track sehen
- aktuelle Audiodateien verwalten und exportieren
- Projekte wie Singles, EPs oder Alben strukturieren
- Projektbilder verwalten und in release-tauglichen Formaten exportieren

Die App ist für einen Benutzer gedacht und läuft lokal im Browser.

## 2. Kernobjekte

Die Anwendung verwaltet aktuell folgende fachliche Hauptobjekte:

- `Track`
- `Projekt`
- `Projekt-Kategorie`
- `Projektbild`
- `Checklisten-Eintrag`
- `aktuelle Audiodatei pro Track`

## 3. Track-Datenmodell

Ein Track enthält folgende Informationen:

### Pflichtfelder

- `uuid`
  - technische interne Kennung
- `trackNumber`
  - fortlaufende sichtbare Nummer
  - wird automatisch vergeben
  - wird nicht wiederverwendet
- `beatName`
  - beschreibt den Beat-Typ oder die Beat-Idee
- `title`
  - frei editierbarer Track-Titel
  - wird bei Erstellung automatisch vorgeschlagen
- `bpms`
  - ein oder mehrere numerische BPM-Werte
- `musicalKey`
  - musikalische Tonart aus einer unterstützten Auswahlliste

### Optionale Felder

- `publishingName`
  - finaler Name für die spätere Veröffentlichung
- `notes`
  - Freitext für zusätzliche Informationen
- `isrc`
  - optionale ISRC-Nummer

### Systemfelder

- `createdAt`
- `updatedAt`

## 4. Title-Logik

Der vorgeschlagene Track-Titel wird aus diesen Werten aufgebaut:

`TRACKNUMBER_BEATNAME_BPM_KEY`

Beispiel:

`21_ToryLanezTypeBeat_120BPM_180BPM_Amin`

Regeln:

- Der Titel wird beim Erstellen automatisch vorgefüllt.
- Der Benutzer darf ihn jederzeit manuell anpassen.
- Wenn sich `beatName`, `bpms` oder `musicalKey` später ändern, fragt die App per Modal:
  - Titel automatisch anpassen und speichern
  - ohne Titel-Anpassung speichern
  - abbrechen

## 5. Export-Naming für Track-Dateien

Für den Export der Track-Audiodatei wird der aktuelle `title` verwendet.

Fallback, falls technisch nötig:

`TRACKNUMBER_BEATNAME_BPM_KEY`

Mehrere BPMs werden in derselben Reihenfolge als `120BPM_180BPM` eingebaut.

## 6. Checkliste pro Track

Jeder Track besitzt eine eigene Checkliste.

### Standard-Checkliste

- Idee
- Produktion
- Publishing

### Regeln

- Einträge können hinzugefügt, umbenannt, entfernt und als erledigt markiert werden.
- Die Reihenfolge ist fachlich relevant und kann per Drag-and-Drop angepasst werden.
- Eine Checkliste muss immer mindestens einen Eintrag enthalten.

## 7. Fortschritt und Status

### Fortschritt

Der Fortschritt wird automatisch berechnet:

`(erledigte Einträge / gesamte Einträge) * 100`

### Status

Der Status wird automatisch aus der Checkliste abgeleitet:

- `New`
- `In Progress`
- `Done`

## 8. Audiodatei pro Track

Pro Track gibt es genau eine aktuelle Audiodatei.

### Erlaubte Formate

- `mp3`
- `wav`

### Verhalten

- Datei hochladen
- bestehende Datei ersetzen
- Datei in der App abspielen
- Datei als `mp3` oder `wav` exportieren
- echte Konvertierung zwischen `mp3` und `wav` beim Export

## 9. Projekte

Ein Projekt ist ein eigenständiges Objekt neben dem Track.

Ein Projekt hat:

- einen `title`
- genau eine `category`
- null, einen oder mehrere Tracks
- optional genau ein Projektbild

Wichtig:

- Ein Projekt darf auch ohne Tracks existieren.
- Das ist sinnvoll für geplante Releases, deren Tracks noch nicht vollständig produziert sind.

## 10. Projekt-Kategorien

Jedes Projekt hat genau eine Kategorie.

### Standard-Kategorien

- `Single`
- `EP`
- `Album`

### Regeln

- Der Benutzer kann auch eigene Kategorien anlegen.
- Bereits verwendete Kategorien werden später wieder vorgeschlagen.
- Projekte können nach Kategorie gefiltert werden.

## 11. Track-Zuordnung zu Projekten

Ein Track kann:

- in keinem Projekt sein
- in genau einem Projekt sein
- in mehreren Projekten sein

Ein Projekt kann:

- keine Tracks enthalten
- beliebig viele Tracks enthalten

### Regeln

- Ein Track darf innerhalb desselben Projekts nur einmal vorkommen.
- Die Reihenfolge der Tracks innerhalb eines Projekts ist fachlich relevant.
- Die Reihenfolge muss anpassbar sein, z. B. für Album-Tracklists.
- In der Track-Detailansicht soll sichtbar sein, in welchen Projekten der Track enthalten ist.

## 12. Projektbilder

Ein Projekt kann optional genau ein Bild besitzen.

### Verhalten

- Bild beim Projekt anlegen oder später ergänzen
- Bild ersetzen
- quadratische Vorschau direkt in der App
- Export in sinnvolle Zielvarianten für Plattformen wie Spotify

### Aktueller Scope

- Erlaubte Upload-Formate: `jpg`, `png`
- Export als quadratisches `3000x3000`-Cover in `jpg` oder `png`

## 13. Suche, Filter, Sortierung

### Track-Übersicht

- Suche nach TrackNumber, Beat Name oder Title
- Filter nach Status
- Sortierung nach z. B. letzter Änderung, TrackNumber, Progress, Status

### Projekt-Übersicht

- Suche nach Projekt-Titel oder Kategorie
- Filter nach Kategorie
- Sortierung nach Titel, letzter Änderung oder Track-Anzahl

## 14. Grundfunktionen des aktuellen MVP

- Tracks erstellen, bearbeiten, löschen
- Track-Checklisten pflegen und neu anordnen
- Audiodateien hochladen, ersetzen, abspielen, exportieren
- Projekte erstellen, bearbeiten, löschen
- Tracks Projekten zuordnen und aus Projekten entfernen
- Track-Reihenfolge innerhalb eines Projekts ändern
- Projektbilder hochladen, ersetzen, ansehen und exportieren

## 15. Nicht Teil des aktuellen MVP

- Mehrere aktive Audiodateien pro Track
- Mehrere Projektbilder pro Projekt
- Mehrbenutzerbetrieb
- Cloud-Synchronisierung
- Rechte-/Rollensystem
