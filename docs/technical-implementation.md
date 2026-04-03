# Track Manager – Technische Umsetzung

## 1. Aktuelles Zielbild

Die Anwendung ist ein Symfony-basierter Monolith im ETFS-Stil mit servergerenderten Views, klar getrennten fachlichen Vertikalen und lokaler Dateispeicherung.

Der aktuelle Fokus liegt auf:

- Track-Erstellung, -Bearbeitung, -Archivierung und -Reaktivierung
- Checklisten-Logik und Fortschrittsableitung
- Upload, Wiedergabe und Export einer aktuellen Audiodatei pro Track
- Projekt-Erstellung, -Bearbeitung, -Archivierung und -Reaktivierung
- Projekt-Kategorien und Track-Zuordnungen
- Projekt-Veröffentlichung mit `published`/`published_at`
- Freitextsuche und Pagination in Track- und Projektlisten
- Projektbilder mit Preview und Export

## 2. Verwendeter Stack

### Backend

- `Symfony 7.4`
- `Doctrine ORM`
- `Doctrine Migrations`
- `MariaDB`

### Frontend

- `Twig`
- `Stimulus`
- `TypeScript`
- `Tailwind CSS`
- `AssetMapper`

### Runtime / Tooling

- `Docker Compose`
- `mise`
- `ffmpeg`
- `nginx`

## 3. Architektur

Die Anwendung folgt einem ETFS-artigen Vertical-Slice-Ansatz:

- `Domain`
  - Entities
  - Domain Services
  - Domain DTOs
- `Infrastructure`
  - Doctrine-Repositories
  - Storage-Services
  - technische Hilfsdienste
- `Facade`
  - Cross-Vertical-Schnittstellen
  - DTO-Rückgaben für andere Vertikalen
- `Presentation`
  - Controller
  - Presentation DTOs
  - Presentation Services
  - Twig-Templates
  - Stimulus-Controller

Wichtige Regel:

- Templates arbeiten mit Presentation DTOs, nicht direkt mit Entities.

## 4. Aktuelle Vertikalen

### 4.1 `TrackManagement`

Verantwortung:

- Tracks erstellen, bearbeiten, archivieren und reaktivieren
- Trackliste, Filter, Sortierung
- Checklistenlogik
- Fortschritt und Status
- Title-Vorschlagslogik
- Ableitung eines Published-Status aus aktiven Projekt-Zuordnungen
- Darstellung von Projekt-Zuordnungen in der Track-Detailansicht

### 4.2 `FileImport`

Verantwortung:

- aktuelle Audiodatei pro Track hochladen
- Datei ersetzen
- lokale Speicherung der Audiodatei
- Playback-Quelle bereitstellen

### 4.3 `FileExport`

Verantwortung:

- Audioexport
- Dateinamenbildung für Exporte
- Audio-Konvertierung via `ffmpeg`

### 4.4 `ProjectManagement`

Verantwortung:

- Projekte erstellen, bearbeiten, archivieren und reaktivieren
- Projekt-Kategorien verwalten bzw. wiederverwenden
- optionale Künstler/Interpreten pflegen
- Tracks Projekten zuordnen
- Reihenfolge der Tracks innerhalb eines Projekts pflegen
- Veröffentlichung und Rücknahme der Veröffentlichung
- Projektliste und Projekt-Detailansicht

### 4.5 `MediaAssetManagement`

Verantwortung:

- Projektbilder hochladen und ersetzen
- Metadaten zu Projektbildern speichern
- Preview-Bild ausliefern
- Projektbild-Export in release-taugliche Formate

## 5. Persistenz

### Datenbank

Aktuell verwendet:

- `MariaDB` im Docker-Setup

### Dateispeicher

Dateien liegen nicht in der Datenbank, sondern lokal im Projekt:

- Audiodateien: `var/storage/track-files/`
- Projektbilder: `var/storage/project-media-assets/`

## 6. Aktuelles Datenmodell

### Tabelle `tracks`

Wichtige Felder:

- `uuid`
- `track_number`
- `beat_name`
- `title`
- `publishing_name`
- `bpms` (JSON-Liste mit `float`)
- `musical_keys` (JSON-Liste)
- `cancelled`
- `notes`
- `isrc`
- `created_at`
- `updated_at`

### Tabelle `checklist_items`

- `uuid`
- `track_uuid`
- `label`
- `is_completed`
- `position`
- `created_at`
- `updated_at`

### Tabelle `track_files`

- `uuid`
- `track_uuid`
- `original_filename`
- `stored_filename`
- `mime_type`
- `extension`
- `size_bytes`
- `uploaded_at`

Nebenbedingung:

- genau eine aktuelle Datei pro Track

### Tabelle `project_categories`

- `uuid`
- `name`
- `normalized_name`
- `created_at`
- `updated_at`

### Tabelle `projects`

- `uuid`
- `title`
- `category_uuid`
- `normalized_title`
- `artists` (JSON-Liste)
- `cancelled`
- `published`
- `published_at`
- `created_at`
- `updated_at`

### Tabelle `project_track_assignments`

- `uuid`
- `project_uuid`
- `track_uuid`
- `position`
- `created_at`
- `updated_at`

Nebenbedingung:

- ein Track darf pro Projekt nur einmal vorkommen

### Tabelle `project_media_assets`

- `uuid`
- `project_uuid`
- `original_filename`
- `stored_filename`
- `mime_type`
- `extension`
- `size_bytes`
- `width_pixels`
- `height_pixels`
- `uploaded_at`

Nebenbedingung:

- maximal ein aktuelles Bild pro Projekt

## 7. Upload- und Speicherstrategie

### Audiodateien

Ablauf:

1. Datei auswählen
2. Dateityp validieren (`mp3`/`wav`)
3. im Storage unter technischem Dateinamen speichern
4. Datenbankeintrag erzeugen oder aktualisieren
5. bestehende Datei beim Replace physisch entfernen

### Projektbilder

Ablauf:

1. Bild auswählen
2. Dateityp validieren (`jpg`/`png`)
3. lokal speichern
4. Bilddimensionen auslesen
5. Datenbankeintrag erzeugen oder aktualisieren

Projektbilder sind beim Erstellen optional und können später ergänzt werden.

## 8. Validierung

### Track-seitig

- `beatName` darf nicht leer sein
- `title` darf nicht leer sein
- `bpms` muss mindestens einen positiven Wert enthalten
- `musicalKeys` muss mindestens einen gültigen Eintrag aus der unterstützten Liste enthalten
- `isrc` ist optional

### Checkliste

- mindestens ein Eintrag
- Reordering nur mit vollständiger und duplikatfreier Liste

### Projekte

- `title` darf nicht leer sein
- `category` darf nicht leer sein
- `artists` ist optional und wird als String-Liste gespeichert
- Track darf innerhalb desselben Projekts nur einmal vorkommen
- archivierte Projekte dürfen nicht veröffentlicht oder ent-veröffentlicht werden

### Medien

- Track-Audio nur `mp3`/`wav`
- Projektbild nur `jpg`/`png`

## 9. Ableitbare Logik

Folgende Werte werden nicht manuell redundant gepflegt:

- `progress`
- `status`
- vorgeschlagener Track-Titel
- Export-Dateiname
- Published-Status eines Tracks aus aktiven veröffentlichten Projekten

## 10. UI-Interaktionen

Gezielte Interaktionen laufen über `Stimulus`:

- Track-Title-Logik mit Modal-Entscheidung
- Reordering von Checklisten
- Reordering von Tracks innerhalb eines Projekts
- Datei-Upload-Buttons
- Freitextsuche für Track-Zuordnung in Projekten

## 11. Audio-Export

Aktuelle Umsetzung:

- Export als `mp3` oder `wav`
- wenn Quelldatei bereits im Zielformat vorliegt: direkt kopieren
- sonst Konvertierung über `ffmpeg`
- Download als temporäre Datei mit anschließendem Cleanup

## 12. Projektbild-Export

Aktuelle Umsetzung:

- Export als `jpg` oder `png`
- Zielgröße `3000x3000`
- Quadrat wird aus dem Originalbild zentriert zugeschnitten und skaliert
- Export-Datei wird temporär erzeugt und nach Auslieferung entfernt

## 13. Routing und Presentation

Aktuell wichtige Oberflächen:

- Trackliste mit Suche, Filtern und Pagination
- Track-Formular
- Track-Detailansicht
- Projektliste mit Suche, Filtern und Pagination
- Projekt-Formular
- Projekt-Detailansicht

Cross-Vertical-Anbindung:

- `TrackManagement` zeigt Projekt-Zuordnungen über die `ProjectManagementFacade`
- `ProjectManagement` nutzt Track-Daten für Auswahl und Darstellung über die `TrackManagementFacade`
- `MediaAssetManagement` prüft Projekt-Existenz über die `ProjectManagementFacade`

## 14. Aktuelle Projektstruktur

Wesentliche Bereiche:

- `src/TrackManagement/...`
- `src/FileImport/...`
- `src/FileExport/...`
- `src/ProjectManagement/...`
- `src/MediaAssetManagement/...`
- `assets/bootstrap.ts`
- `config/packages/doctrine.yaml`
- `config/packages/twig.yaml`
- `config/packages/asset_mapper.yaml`
- `config/services.yaml`
- `migrations/`
- `var/storage/track-files/`
- `var/storage/project-media-assets/`

## 15. Wichtige technische Hinweise

- Doctrine-Mappings für neue Vertikalen müssen explizit in `doctrine.yaml` registriert sein.
- Twig-Namespaces für neue Presentation-Vertikalen müssen in `twig.yaml` registriert sein.
- Neue Asset-Pfade müssen in `asset_mapper.yaml` und `assets/bootstrap.ts` verdrahtet werden.
- Projektbilder sind optional, obwohl pro Projekt technisch maximal ein aktuelles Bild vorgesehen ist.

## 16. Fazit

Die Anwendung ist aktuell kein generischer Starter mehr, sondern fachlich bereits klar als eigenes Track-/Projekt-Management-System ausgebaut.

Technisch ist die Architektur inzwischen auf folgende Weiterentwicklungen vorbereitet:

- weitere Projekt-Kategorien
- zusätzliche Medienarten
- erweiterte Exportvarianten
- neue Vertikalen ohne großen Umbau des bestehenden Kerns

