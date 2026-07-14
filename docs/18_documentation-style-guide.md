# Documentation Style Guide

> Standards for clear, consistent and easily translatable documentation within the Microgreens ERP project.

## Document Information

| Property | Value |
|----------|-------|
| Document ID | DOC-018 |
| Document | Documentation Style Guide |
| Version | 1.0 |
| Status | Draft |
| Author | Joep Kuijpers |
| Project | Microgreens ERP |
| Location | /docs/18_documentation-style-guide.md |
| Last Updated | 2026-07-13 |
| Target Audience | Developers, administrators and project members |

## Purpose

This document defines the writing, formatting and documentation standards used throughout the Microgreens ERP project.

The objective is to keep all documentation:

- Clear
- Accurate
- Consistent
- Maintainable
- Easy to navigate
- Easy to translate
- Easy for people and AI systems to interpret

This document is the Single Source of Truth for documentation standards.

## Scope

This guide applies to:

- Project documentation
- Technical documentation
- ERP documentation
- Hardware documentation
- Database documentation
- Research documentation
- Meeting notes
- Templates
- Markdown documents
- Source code comments where applicable

## Writing Principles

Documentation should use clear and direct language.

Use short sentences.

Express one main idea per sentence.

Use short paragraphs.

Avoid unnecessary repetition.

Avoid vague language.

Avoid marketing language.

Avoid slang, idioms and regional expressions.

Prefer specific terms over general terms.

Use the same term for the same concept throughout the project.

## Language Standard

All official project documentation is written in English.

Use American English consistently.

Examples:

| Use | Avoid |
|-----|-------|
| organization | organisation |
| behavior | behaviour |
| optimize | optimise |
| standardization | standardisation |

Use simple international English.

Do not use complex wording when a simpler alternative communicates the same meaning.

Prefer:

> The system records every inventory transaction.

Avoid:

> Every inventory transaction is recorded and retained by the system for future operational reference.

## Translation Guidelines

Documentation must be easy to translate into other languages.

To support accurate translation:

- Use short sentences.
- Use one idea per sentence.
- Avoid idioms.
- Avoid humor that depends on language.
- Avoid words with multiple meanings where possible.
- Keep official project terms unchanged.
- Do not combine several instructions in one sentence.
- Use complete sentences.
- Keep headings descriptive and consistent.
- Do not place important information only inside images.

Do not translate official names such as:

- Microgreens ERP
- Crop Profiles
- Grow Batches
- Finished Inventory
- Production Planner
- Raspberry Pi
- Git
- GitHub
- PHP
- SQL
- SQLite
- GPIO

## Terminology

The official terminology is defined in:

```text
17_glossary.md
```

The Glossary is the Single Source of Truth for project terminology.

Before introducing a new term:

1. Check whether the term already exists in the Glossary.
2. Use the existing official term when available.
3. Add a new term to the Glossary before using it throughout the documentation.

Use one preferred term for each concept.

Examples:

| Preferred Term | Avoid |
|----------------|-------|
| Food Chain | Food System |
| Project Foundation | Project Vision Folder |
| Knowledge Sharing | Knowledge Transfer |
| Continuous Improvement | Constant Improvement |
| Freeze Drying | Freeze Drier Process |
| Freeze Dryer | Freeze Drier |
| Grow Batch | Cultivation Batch |
| Inventory | Stock |
| Traceability | Tracking |

Use `Supply Chain` only when referring specifically to logistics, suppliers or distribution.

## Capitalization

Capitalize official document names, module names and product names.

Examples:

- Project Foundation
- Vision
- Mission
- Core Values
- Crop Profiles
- Grow Batches
- Finished Inventory
- Production Planner
- Microgreens ERP

Use lowercase for general concepts in normal sentences.

Examples:

- documentation
- hardware
- automation
- food chain
- sustainability
- database
- inventory transaction

Always use the official spelling of technologies and tools.

| Correct | Incorrect |
|---------|-----------|
| PHP | Php |
| JavaScript | Javascript |
| HTML | Html |
| CSS | Css |
| SQL | Sql |
| GitHub | Github |
| PowerShell | Powershell |
| SQLite | SqlLite |
| Raspberry Pi | Raspberry pi |

## Naming Conventions

### Folders

Use lowercase kebab-case.

Examples:

```text
project-foundation
meeting-notes
freeze-dryer
markdown-templates
```

Avoid:

```text
Project_Foundation
meetingnotes
freeze_dryer
New Folder
```

### Markdown Files

Use lowercase kebab-case.

Examples:

```text
software-architecture.md
database-planning.md
known-issues.md
documentation-style-guide.md
```

The standard entry file for a folder is:

```text
README.md
```

Keep `README.md` uppercase.

### Sequential Documents

Use a two-digit number when documents have a required reading order.

Examples:

```text
00_master-index.md
01_daily-workflow.md
02_new-chat-prompt.md
```

Do not number files when reading order is not important.

### Images and Diagrams

Use lowercase kebab-case.

Examples:

```text
system-architecture.png
database-relationships.svg
deployment-workflow.drawio
```

## Document Structure

Major documents should use the following structure where applicable:

```text
Title
Document Information
Purpose
Scope
Contents
Main Content
Related Documents
Revision History
```

Not every short document requires every section.

Do not add empty sections only to follow the template.

Each section must have a clear function.

## Headings

Use headings in sequential order.

Correct:

```text
# Title
## Section
### Subsection
#### Detail
```

Do not skip heading levels.

Incorrect:

```text
# Title
### Subsection
```

Use one level-one heading per document.

Keep headings short and descriptive.

## Horizontal Rules

Do not use horizontal rules between every section.

Headings normally provide enough separation.

Use a horizontal rule only when it clearly separates major independent parts of a document.

## Paragraphs

Keep paragraphs short.

Each paragraph should explain one topic.

Avoid paragraphs that contain several unrelated ideas.

Prefer:

> The system records sensor data.
>
> The data supports climate monitoring.
>
> Historical data is used for analysis.

Avoid:

> The system records sensor data for climate monitoring and analysis while also supporting future automation and reporting requirements.

## Lists

Use bullet lists for items without a required order.

Example:

```md
- Crop Profiles
- Grow Batches
- Harvests
```

Use numbered lists for procedures and ordered steps.

Example:

```md
1. Check the working tree.
2. Review the changes.
3. Run the tests.
4. Create the commit.
```

Keep list items grammatically consistent.

Do not mix full sentences and fragments within the same list unless necessary.

## Tables

Use tables for structured comparisons or fixed data.

Keep tables simple.

Example:

```md
| Property | Value |
|----------|-------|
| Version | 1.0 |
| Status | Draft |
```

Avoid very wide tables.

Use a list instead when a table becomes difficult to read.

Do not place long paragraphs inside table cells.

## Links and Cross-References

Use relative Markdown links for documents inside the repository.

Example:

```md
[Vision](project-foundation/01_vision.md)
```

Do not use plain file paths when a clickable link is more useful.

Preferred:

```md
**See:** [Mission](project-foundation/02_mission.md)
```

Avoid duplicating complete information from another document.

Summarize the topic and link to the Single Source of Truth.

Use this section title consistently:

```md
## Related Documents
```

## Code and Commands

Use fenced code blocks.

Always specify the language when known.

Examples:

```php
<?php
echo 'Microgreens ERP';
```

```sql
SELECT id, crop_name
FROM crop_profiles;
```

```powershell
git status --short
```

```bash
php -l index.php
```

Do not place commands and their output in the same code block unless clearly labeled.

Do not paste PHP, HTML, CSS or JavaScript into the terminal instructions.

Use PowerShell for local Windows commands.

Use Bash for Raspberry Pi commands.

## Paths

Use exact paths when a task depends on a specific location.

Local development repository:

```text
C:\Users\joepk\Downloads\microgreens-erp
```

Raspberry Pi deployment repository:

```text
/var/www/html/microgreens/PHP
```

Use forward slashes in repository-relative Markdown links.

Example:

```text
project-foundation/01_vision.md
```

## Dates

Use the ISO 8601 date format:

```text
YYYY-MM-DD
```

Example:

```text
2026-07-13
```

Do not use:

```text
13-07-2026
July 13, 2026
13 July 2026
```

## Numbers and Units

Place a space between a number and a unit.

Examples:

```text
25 °C
60 %
500 g
10 L
230 V
```

Use consistent unit symbols.

Use a leading zero for decimal values below one.

Correct:

```text
0.5 g
```

Incorrect:

```text
.5 g
```

Define specialized units when they first appear.

Examples:

- Electrical Conductivity (EC)
- parts per million (ppm)

## Status Values

Use the following document status values:

| Status | Meaning |
|--------|---------|
| Planned | The document is planned but not yet developed. |
| Draft | The document contains active work and may change. |
| Review | The document is ready for verification. |
| Stable | The document is approved for normal use. |
| Deprecated | The document is no longer current and should not be used. |
| Archived | The document is retained only for historical reference. |

Use one status value per document.

## AI-Friendly Documentation

Documentation should be easy for AI systems to interpret accurately.

Use descriptive headings.

Use explicit nouns when pronouns may create ambiguity.

Prefer:

> The Production Planner calculates the required number of trays.

Avoid:

> It calculates the required number.

Keep terminology consistent.

Do not hide essential information in decorative formatting.

Do not rely on context from unrelated documents.

Link to related documents when additional context is required.

## Documentation Maintenance

Update documentation when related software, hardware or processes change.

A feature is not complete until:

1. The implementation is complete.
2. The implementation is tested.
3. The related documentation is updated.
4. The change is committed to Git.

Remove outdated information or mark it as deprecated.

Do not keep several active documents that describe the same subject.

One document must remain the Single Source of Truth for each topic.

## Related Documents

- [Glossary](17_glossary.md)
- [Master Index](00_master-index.md)
- [Project Operating Manual](03_project-operating-manual.md)
- [Programming Standards](08_programming-standards.md)

## Revision History

| Date | Version | Author | Change |
|------|---------|--------|--------|
| 2026-07-13 | 1.0 | Joep Kuijpers | Initial Documentation Style Guide |