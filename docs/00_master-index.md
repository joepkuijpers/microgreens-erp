# Microgreens ERP

> One source of truth

---

## Documentinformatie

| Eigenschap | Waarde |
|------------|---------|
| Document ID | DOC-000 |
| Document | Master Index |
| Versie | 1.0 |
| Status | 🔄 In ontwikkeling |
| Auteur | Joep Kuijpers |
| Project | Microgreens ERP |
| Locatie | /docs/00_MASTER_INDEX.md |
| Laatste wijziging | 2026-07-12 |
| Doelgroep | Ontwikkelaars, beheerders en projectleden |

---

# Inhoud

01. Leeswijzer
02. Welkom
03. Projectoverzicht
04. Projectvisie
05. Projectmissie
06. Kernwaarden
07. Projectdoelen
08. Projectscope

09. Documentatiestructuur
10. Documentatieprincipes
11. Documentmatrix
12. Documentstatus
13. Gerelateerde documenten

14. Ontwikkelworkflow
15. Git Workflow
16. Deployment Workflow

17. Technologie
18. Architectuur
19. Database
20. ERP Modules
21. Hardware
22. AI & Automatisering
23. Freeze Dryer

24. Roadmap
25. Onderhoud
26. Revisiegeschiedenis
27. Woordenlijst

---

# Leeswijzer

Nieuwe gebruiker?

Lees de documentatie bij voorkeur in onderstaande volgorde:

1. **README.md** – Overzicht van de documentatie.
2. **00_MASTER_INDEX.md** – Centrale navigatie en projectoverzicht.
3. **01_DAILY_WORKFLOW.md** – Dagelijkse ontwikkelworkflow.
4. **02_NEW_CHAT_PROMPT.md** – Standaardprompt voor nieuwe ChatGPT-sessies.
5. **03_PROJECT_OPERATING_MANUAL.md** – Projectregels, standaarden en werkwijzen.

Daarna kunnen de overige documenten zelfstandig worden geraadpleegd.

---

# Welkom

Welkom bij het Microgreens ERP-project.

Dit project heeft als doel een compleet ERP-, automatiserings- en kennisplatform te ontwikkelen voor een moderne, duurzame en schaalbare microgreens-kwekerij.

Deze documentatie vormt de centrale kennisbank van het project.

## Doel

De Master Index vormt het centrale startpunt van de volledige documentatie van het Microgreens ERP-project.

Dit document biedt een overzicht van:

- de projectstructuur;
- alle projectdocumentatie;
- ontwikkelstandaarden;
- workflows;
- projectstatus;
- technische documentatie;
- belangrijke verwijzingen.

Nieuwe projectleden en ontwikkelaars dienen altijd met dit document te beginnen voordat zij andere onderdelen van de documentatie raadplegen.

---

## Reikwijdte

De Master Index beschrijft uitsluitend de structuur, navigatie en organisatie van het project.

Technische details worden uitgewerkt in de afzonderlijke documentatiebestanden, zodat dit document compact, overzichtelijk en eenvoudig te onderhouden blijft.
---

## Projectstatus

**Status:** Actief in ontwikkeling

**Huidige ontwikkelfase:**

- Professionele documentatiestructuur opzetten
- Arbeidsmodule uitbreiden
- ERP v1.0 stabiliseren
- Lokale ontwikkelomgeving optimaliseren

De projectdocumentatie wordt parallel met de software ontwikkeld en maakt integraal onderdeel uit van het project.

---

# Projectoverzicht

## Samenvatting

Het Microgreens ERP-project combineert bedrijfsvoering, automatisering, hardware, onderzoek en documentatie in één geïntegreerd en schaalbaarplatform.

Het systeem ondersteunt de volledige levenscyclus van microgreens, vanaf de inkoop van zaden tot en met de verkoop van het eindproduct.

De software wordt ontwikkeld volgens professionele standaarden en is ontworpen om schaalbaar, onderhoudbaar en uitbreidbaar te blijven.

---

## ERP

De ERP-software ondersteunt onder andere:

- Crop Profiles
- Batchbeheer
- Productieplanning
- Arbeidsregistratie
- Oogstregistratie

- Voorraadbeheer
- Voorraadtransacties

- Productbeheer
- Klantenbeheer
- Leveranciersbeheer
- Verkoop

- Traceability
- Rapportages

- Apparatuurbeheer
- Energiebeheer

---

## Hardware

Het platform ondersteunt onder andere de volgende hardware:
- Raspberry Pi
- GPIO
- Sensoren
- Relais
- Klimaatregeling
- Verlichting
- Irrigatie

---

## Onderzoek

Naast softwareontwikkeling wordt binnen het project onderzoek gedaan naar:

- Microgreens
- Plantfysiologie
- Energiebeheer
- HACCP
- Vriesdrogen
- Automatisering
- Kunstmatige intelligentie (AI)

---

## Schaalbaarheid

Het systeem is ontworpen zodat het in de toekomst eenvoudig uitgebreid kan worden met:

- Nieuwe ERP-modules
- Extra hardware
- Nieuwe sensoren
- Robotisering
- Cloudintegratie
- AI-functionaliteit
- Ondersteuning voor meerdere productielocaties

---
## Vision

An honest, transparent and sustainable food chain.

A chain in which technology and nature strengthen one another.

A chain that minimizes waste, shares knowledge openly and continuously improves.

A chain that reconnects future generations with the origin of life: plants, animals, nature and one another.

**Full vision:** [project-foundation/01_vision.md](project-foundation/01_vision.md)

---

## Mission

To develop an honest and sustainable food chain.

Transparency is the standard.

Innovation and automation are applied with purpose.

The project works toward cultivation based on biological principles.

Every stage of the food chain is continuously evaluated and improved.

Waste is reduced.

Food is preserved for longer.

Storage and transportation are made more efficient.

Knowledge is shared openly.

**Full mission:** [project-foundation/02_mission.md](project-foundation/02_mission.md)

---

# Kernwaarden

> Dit hoofdstuk wordt in een volgende revisie uitgewerkt.

---

# Projectdoelen

> Dit hoofdstuk wordt in een volgende revisie uitgewerkt.

---

# Projectscope

> Dit hoofdstuk wordt in een volgende revisie uitgewerkt.

---

# Projectprincipes

Het Microgreens ERP-project wordt ontwikkeld volgens een aantal vaste principes. Deze vormen de basis voor alle technische en organisatorische keuzes.

De onderstaande principes vormen de basis van alle ontwerp-, ontwikkel- en beheerbeslissingen binnen het Microgreens ERP-project.

Ze zorgen ervoor dat het systeem consistent, onderhoudbaar, schaalbaar en toekomstbestendig blijft.

## Eén bron van waarheid

Alle broncode, documentatie, databaseontwerpen en projectbestanden worden beheerd vanuit één centrale Git-repository.

Hierdoor is altijd duidelijk welke versie de meest actuele is.

### Praktische toepassing

Binnen het Microgreens ERP-project betekent dit dat:

- Git de enige bron van waarheid is.
- Documentatie altijd synchroon loopt met de broncode.
Goede documentatie is een essentieel onderdeel van het project.

Hierdoor blijft kennis behouden, wordt samenwerking eenvoudiger en kunnen toekomstige uitbreidingen veilig worden uitgevoerd.
- Nieuwe functionaliteit pas gereed is nadat deze is getest én gedocumenteerd.
- Oude bestanden niet worden gekopieerd maar via Git worden beheerd.
- Ontwikkeling lokaal plaatsvindt en pas daarna naar GitHub en de Raspberry Pi wordt overgebracht.


---

## Documentatie is onderdeel van het project


Nieuwe functionaliteit wordt pas als voltooid beschouwd wanneer zowel de software als de bijbehorende documentatie zijn bijgewerkt.

Documentatie wordt daarom gelijktijdig met de broncode onderhouden en maakt integraal onderdeel uit van het ontwikkelproces.

---

## Kleine gecontroleerde wijzigingen

Nieuwe functionaliteit wordt ontwikkeld volgens een vaste werkwijze:

1. Controle
2. Analyse
3. Verbeterplan
4. Goedkeuring
5. Kleine wijziging
6. Testen
7. Commit
8. Push
9. Deployment

Hierdoor blijven wijzigingen overzichtelijk en eenvoudig terug te draaien.

---

## Lokaal ontwikkelen

Alle softwareontwikkeling vindt plaats op de lokale Windows-computer.

Werkmap:

```
C:\Users\joepk\Downloads\microgreens-erp
```

De Raspberry Pi wordt uitsluitend gebruikt als doelomgeving voor deployment en hardwaretesten.

---

## Git Workflow

Ontwikkeling verloopt volgens onderstaande volgorde.

```
VS Code
    │
    ▼
Lokale test
    │
    ▼
git status
    │
    ▼
git diff
    │
    ▼
git add
    │
    ▼
git commit
    │
    ▼
git push
    │
    ▼
GitHub
    │
    ▼
Raspberry Pi
git pull
```

---

# Technologie

## Backend

Het Microgreens ERP is ontwikkeld met:

- PHP
- SQLite
- Apache

---

## Frontend

De gebruikersinterface is opgebouwd met:

- HTML5
- CSS3
- JavaScript

---

## Hardware

Voor de hardware-integratie wordt gebruikgemaakt van:

- Raspberry Pi
- GPIO
- BH1750
- BME280
- Relaismodules

---

## Versiebeheer

Het project maakt gebruik van:

- Git
- GitHub

---

## Ontwikkelomgeving

De standaard ontwikkelomgeving bestaat uit:

- Windows 11
- VS Code
- Git
- PowerShell

De Raspberry Pi wordt uitsluitend gebruikt voor deployment, hardware-integratie en praktijktesten.

# Documentmatrix

Onderstaande matrix geeft een overzicht van alle hoofddocumenten binnen het project en hun huidige ontwikkelstatus.

| ID | Document | Status | Prioriteit |
|----|----------|---------|------------|
| DOC-000 | Master Index | 🔄 | Hoog |
| DOC-001 | Daily Workflow | 📋 | Hoog |
| DOC-002 | New Chat Prompt | 📋 | Hoog |
| DOC-003 | Project Operating Manual | 📋 | Hoog |
| DOC-004 | Project Knowledge Base | 📋 | Hoog |
| DOC-005 | Architecture | 📋 | Hoog |
| DOC-006 | Database Planning | 📋 | Hoog |
| DOC-007 | Git Deployment | 📋 | Hoog |
| DOC-008 | Coding Standards | 📋 | Hoog |
| DOC-009 | Raspberry Pi | 📋 | Gemiddeld |
| DOC-010 | ERP Modules | 📋 | Hoog |
| DOC-011 | Roadmap | 📋 | Gemiddeld |
| DOC-012 | Changelog | 📋 | Hoog |
| DOC-013 | Known Issues | 📋 | Hoog |
| DOC-014 | Freeze Dryer | 📋 | Gemiddeld |
| DOC-015 | AI Automation | 📋 | Gemiddeld |
| DOC-016 | Decisions | 📋 | Gemiddeld |
| DOC-017 | Glossary | 📋 | Laag |

# Revisiegeschiedenis

| Datum | Versie | Auteur | Wijziging |
|--------|---------|---------|-----------|
| 2026-07-12 | 1.0 | Joep Kuijpers | Eerste versie van de Master Index |