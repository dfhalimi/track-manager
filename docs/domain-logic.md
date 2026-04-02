# Track Manager – Domain-Logik

## 1. Ziel und Rahmen

Die Anwendung verwaltet Tracks, deren Produktionsfortschritt, aktuelle Audiodateien sowie Release-Projekte mit Projektbildern.

Der aktuelle Scope ist:

- Single-User-App
- lokale Web-Anwendung
- lokale Datenhaltung
- keine Benutzerverwaltung
- keine Cloud-Synchronisierung

## 2. Zentrale Domain-Objekte

### 2.1 Track

Ein Track ist das zentrale Objekt der Anwendung.

Ein Track besitzt fachlich:

- `uuid`
  - technische interne Kennung
- `trackNumber`
  - fortlaufende sichtbare Nummer
  - wird automatisch vergeben
  - wird nicht wiederverwendet
- `beatName`
  - beschreibt den Beat oder die Beat-Idee
- `title`
  - frei editierbarer Track-Titel
- `publishingName`
  - optionaler finaler Veröffentlichungsname
- `bpms`
  - Liste aus einem oder mehreren BPM-Werten
- `musicalKey`
  - musikalische Tonart aus einer unterstützten Auswahlliste
- `notes`
  - optionaler Freitext
- `isrc`
  - optionale ISRC-Nummer
- `createdAt`
- `updatedAt`

### 2.2 ChecklistItem

Jeder Track besitzt eine individuelle Checkliste.

Ein Checklisten-Eintrag besitzt:

- `uuid`
- `trackUuid`
- `label`
- `isCompleted`
- `position`
- `createdAt`
- `updatedAt`

### 2.3 TrackFile

Pro Track gibt es genau eine aktuelle Audiodatei.

Ein TrackFile besitzt fachlich:

- `uuid`
- `trackUuid`
- `originalFilename`
- `storedFilename`
- `mimeType`
- `extension`
- `sizeBytes`
- `uploadedAt`

### 2.4 Project

Ein Projekt ist ein eigenständiges Objekt neben dem Track.

Ein Projekt besitzt:

- `uuid`
- `title`
- `categoryUuid`
- `createdAt`
- `updatedAt`

Ein Projekt darf ohne Tracks existieren.

### 2.5 ProjectCategory

Jedes Projekt hat genau eine Kategorie.

Eine Kategorie besitzt:

- `uuid`
- `name`
- `normalizedName`
- `createdAt`
- `updatedAt`

Standardwerte:

- `Single`
- `EP`
- `Album`

Zusätzlich dürfen benutzerdefinierte Kategorien angelegt und später wiederverwendet werden.

### 2.6 ProjectTrackAssignment

Die Zuordnung zwischen Projekt und Track ist ein eigenes Domain-Objekt.

Sie besitzt:

- `uuid`
- `projectUuid`
- `trackUuid`
- `position`
- `createdAt`
- `updatedAt`

Diese Beziehung trägt die Reihenfolge der Tracks innerhalb eines Projekts.

### 2.7 ProjectMediaAsset

Ein Projekt kann optional genau ein Bild besitzen.

Das Projektbild besitzt:

- `uuid`
- `projectUuid`
- `originalFilename`
- `storedFilename`
- `mimeType`
- `extension`
- `sizeBytes`
- `widthPixels`
- `heightPixels`
- `uploadedAt`

## 3. Track-Erstellung

Beim Anlegen eines Tracks gelten folgende Regeln:

- `trackNumber` wird automatisch vergeben.
- `uuid`, `createdAt` und `updatedAt` werden automatisch gesetzt.
- Der Track erhält automatisch eine Default-Checkliste mit:
  - `Idee`
  - `Produktion`
  - `Publishing`
- Ein Track darf ohne Audiodatei existieren.
- Ein Track darf ohne `publishingName`, `notes` und `isrc` existieren.
- `bpms` muss mindestens einen positiven Wert enthalten.

## 4. Title-Logik

Der vorgeschlagene Track-Titel wird aus den fachlichen Eingangswerten erzeugt:

`TRACKNUMBER_BEATNAME_BPM_KEY`

Beispiel:

`21_ToryLanezTypeBeat_120BPM_180BPM_Amin`

Regeln:

- `title` wird beim Erstellen automatisch vorgeschlagen.
- Der Benutzer darf ihn frei überschreiben.
- BPM-Werte werden als `120BPM` formatiert.
- Mehrere BPM-Werte werden in derselben Reihenfolge eingebaut.
- `musicalKey` wird auf ein kanonisches Format normalisiert.
- Wenn sich `beatName`, `bpms` oder `musicalKey` später ändern, fragt die Anwendung, ob der Titel angepasst werden soll.

## 5. Checklistenlogik

### 5.1 Grundregeln

- Jeder Track hat genau eine Checkliste.
- Eine Checkliste enthält mindestens einen Eintrag.
- Einträge können hinzugefügt, umbenannt, entfernt und als erledigt markiert werden.

### 5.2 Reihenfolge

- Checklisten-Einträge haben eine fachliche Reihenfolge.
- Neue Einträge werden standardmäßig am Ende eingefügt.
- Die Reihenfolge kann per Drag-and-Drop geändert werden.
- Beim Reordering müssen alle Einträge genau einmal enthalten sein.

## 6. Fortschritt und Status

### Fortschritt

Der Fortschritt eines Tracks ist ein abgeleiteter Wert:

`(erledigte Einträge / gesamte Einträge) * 100`

### Status

Der Status wird automatisch aus der Checkliste abgeleitet:

- `New`
- `In Progress`
- `Done`

Status und Fortschritt werden nicht manuell gepflegt.

## 7. Audiodatei pro Track

### 7.1 Grundregel

Pro Track gibt es genau eine aktuelle Audiodatei.

### 7.2 Erlaubte Formate

- `mp3`
- `wav`

### 7.3 Verhalten

- Ein Track darf ohne Datei existieren.
- Upload validiert Dateiformat.
- Eine bestehende Datei kann ersetzt werden.
- Nur die aktuelle Datei ist relevant.
- Die Datei kann direkt in der Anwendung abgespielt werden.

## 8. Audio-Export

Ein Track mit vorhandener Datei kann exportiert werden.

Regeln:

- Export verwendet den aktuellen Track-Titel als Dateinamen.
- Export unterstützt `mp3` und `wav`.
- Bei Bedarf wird eine echte Konvertierung durchgeführt.
- Ist keine Konvertierung nötig, kann die vorhandene Datei direkt ausgeliefert werden.

## 9. Projekte

### 9.1 Grundregeln

- Ein Projekt hat genau einen Titel.
- Ein Projekt hat genau eine Kategorie.
- Ein Projekt kann null, einen oder mehrere Tracks enthalten.
- Ein Track kann in null, einem oder mehreren Projekten enthalten sein.

### 9.2 Einschränkungen

- Ein Track darf innerhalb desselben Projekts nur einmal vorkommen.
- Die Track-Reihenfolge innerhalb eines Projekts ist fachlich relevant.
- Die Reihenfolge wird an der Beziehung `ProjectTrackAssignment` gespeichert, nicht am Track selbst.

### 9.3 Kategorien

- Kategorien sind wiederverwendbar.
- Standardkategorien sind vorhanden.
- Neue Kategorien können inline beim Anlegen/Bearbeiten eines Projekts entstehen.
- Projekte können nach Kategorie gefiltert werden.

## 10. Projektbilder

### 10.1 Grundregel

- Ein Projekt kann optional genau ein Bild besitzen.
- Das Bild kann direkt beim Erstellen oder später ergänzt werden.

### 10.2 Erlaubte Formate

- `jpg`
- `png`

### 10.3 Verhalten

- Bild hochladen
- Bild ersetzen
- quadratische Vorschau anzeigen
- Export als release-taugliches Quadrat

### 10.4 Export

Aktueller Scope:

- Export als `jpg` oder `png`
- Zielgröße `3000x3000`
- Fokus auf Spotify-taugliches quadratisches Cover

## 11. Suche, Filter und Sortierung

### Tracks

- Suche nach TrackNumber, Beat Name oder Title
- Filter nach Status
- Sortierung nach letzter Änderung, TrackNumber, Erstellung, Progress, Status

### Projekte

- Suche nach Titel oder Kategorie
- Filter nach Kategorie
- Sortierung nach letzter Änderung, Titel oder Track-Anzahl

## 12. Cross-Object-Sichtbarkeit

- In der Track-Detailansicht ist sichtbar, in welchen Projekten ein Track enthalten ist.
- In der Projekt-Detailansicht sind die zugeordneten Tracks mit Reihenfolge sichtbar.

## 13. Aktueller MVP-Scope

Das MVP unterstützt aktuell fachlich:

- Track-Verwaltung
- Checklisten-Management
- Audiodatei-Management
- Audio-Export
- Projekt-Verwaltung
- Projekt-Kategorien
- Track-Zuordnungen zu Projekten
- Reordering von Projekt-Tracklisten
- Projektbild-Management

## 14. Nicht Teil des aktuellen Scope

- mehrere aktive Audiodateien pro Track
- mehrere Bilder pro Projekt
- Dateihistorien
- Cloud-Synchronisierung
- Mehrbenutzerbetrieb

