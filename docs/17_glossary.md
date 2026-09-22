# Glossary

## Purpose

The Glossary establishes the controlled vocabulary of the Microgreens ERP project.

Its purpose is to provide one authoritative definition for every significant concept, principle, technical term, workflow, role, and domain-specific expression used throughout the project.

The Glossary supports consistent communication across the Project Foundation, documentation, software, database, hardware, research, automation, Artificial Intelligence, Safety & Resilience, Stewardship & Governance, operations, and future development.

The Glossary is not a simple dictionary.

It is the semantic backbone of the project.

By maintaining one shared vocabulary, the project reduces ambiguity, improves coordination, strengthens traceability, supports automated validation, and reduces the risk of inconsistent interpretation.

---

## Philosophy

Language shapes understanding.

Consistent terminology enables consistent thinking.

Consistent thinking enables consistent design.

Consistent design enables reliable implementation.

Terminology is therefore regarded as part of the project's architecture rather than merely part of its documentation.

Every significant concept should have one authoritative definition.

Other documents should reference that definition instead of creating competing interpretations.

---

## Objectives

The Glossary aims to:

- Establish one common language across the project
- Provide one authoritative definition for every significant concept
- Reduce ambiguity and duplicate terminology
- Improve communication between technical and non-technical contributors
- Strengthen traceability across software, documentation, research, and engineering
- Support automated terminology checks
- Preserve knowledge across contributors and generations
- Support future expansion without destabilising existing definitions

---

## Core Principles

### Single Point of Definition

Every significant concept should have exactly one authoritative definition.

Other documents may explain how the concept applies in a specific context, but they should not redefine its meaning.

### Stable Identity

Every approved glossary concept may receive a permanent Glossary Identifier.

Definitions may be refined as understanding improves.

Identifiers should remain stable and should never be reused for another concept.

### Timeless Definitions

Definitions should describe the underlying concept rather than a temporary implementation whenever practical.

Technology, interfaces, and workflows may change.

The meaning of the concept should remain understandable across those changes.

### Project-Wide Consistency

The same term should carry the same meaning throughout the project.

Alternative wording may be recorded as an alias, but one preferred term should remain authoritative.

### Human Understanding Before Automation

Automated systems may detect, validate, and suggest terminology changes.

They should not silently redefine project concepts.

Meaning remains subject to human review and stewardship.

### Continuous Evolution

The Glossary develops alongside the project.

Definitions may be expanded, clarified, merged, deprecated, or linked as knowledge grows.

Changes should preserve traceability and should not erase the reasoning behind earlier definitions.

---

## Controlled Vocabulary

The Glossary functions as the project's controlled vocabulary.

Whenever practical, new concepts should be introduced into the Glossary before they become widely used elsewhere in the project.

A controlled vocabulary reduces:

- Inconsistent naming
- Duplicate concepts
- Accidental redefinition
- Unclear database terminology
- Conflicting software labels
- Documentation drift
- Incorrect automation assumptions
- Avoidable coordination errors

Non-standard terminology should be flagged and linked to the preferred term.

---

## Glossary Identifiers

Each approved glossary entry may be assigned a permanent identifier.

The standard format is:

```text
GLS-NNN
```

Examples:

```text
GLS-001 Food Chain
GLS-002 Crop Profile
GLS-003 Batch
GLS-004 Observation
GLS-005 Understanding
GLS-006 Experience
GLS-007 Development
GLS-008 Contribution
GLS-009 Knowledge
GLS-010 Human Flourishing
```

Identifiers should:

- Use leading zeros
- Remain unique
- Remain stable
- Never be reassigned
- Remain reserved after retirement
- Be referenced together with the concept name whenever practical

Example:

```text
Observation (GLS-004)
```

---

## Standard Glossary Entry

Each glossary entry should follow a consistent structure.

```markdown
## GLS-NNN — Concept Name

### Preferred Term

Concept Name

### Definition

The authoritative definition of the concept.

### Purpose

Why the concept exists within the project.

### Scope

What the concept includes and excludes.

### Aliases

Alternative wording that may be encountered.

### Related Terms

Related glossary concepts.

### Used In

Project areas where the concept is used.

### Referenced By

Documents, modules, tables, interfaces, or strategies that reference the concept.

### Related Documents

Relevant documentation.

### Related Strategies

Relevant Project Foundation strategies.

### Related Modules

Relevant ERP or technical modules.

### Related Database Objects

Relevant database tables, fields, views, or relationships.

### Status

Draft, Approved, Deprecated, Retired, or Replaced.

### Revision History

A record of significant definition changes.
```

Not every entry requires every subsection.

The structure provides a consistent framework that can grow with the project.

---

## Concept Relationships

Glossary concepts should not exist in isolation.

Where appropriate, an entry should identify:

- Parent concepts
- Child concepts
- Related concepts
- Supporting concepts
- Dependent concepts
- Opposing concepts
- Aliases
- Deprecated terms
- Replacement terms

These relationships transform the Glossary into an interconnected knowledge network.

---

## Terminology Status

### Draft

The concept has been detected or proposed but is not yet authoritative.

### Approved

The concept has been reviewed and accepted as part of the controlled vocabulary.

### Deprecated

The term should no longer be introduced, but remains documented for backwards compatibility.

### Replaced

The concept or term has been superseded by another approved glossary entry.

### Retired

The concept is no longer active but remains preserved for traceability.

---

## Automated Glossary Maintenance

The Glossary should support automated validation and assisted maintenance.

Automation may detect:

- New concepts without Glossary Identifiers
- Duplicate or near-duplicate concepts
- Unrecognised terminology
- Inconsistent capitalisation
- Undefined abbreviations
- Conflicting definitions
- Outdated aliases
- Broken glossary references
- Use of deprecated terms
- Missing relationships
- Formatting errors
- Entries that do not follow the approved structure

Detected issues should produce suggestions rather than uncontrolled changes.

The standard review flow is:

```text
Detect
    ↓
Analyse
    ↓
Validate
    ↓
Suggest
    ↓
Human Review
    ↓
Apply or Defer
    ↓
Re-Validate
    ↓
Commit
```

---

## Glossary Suggestion Actions

Every automated glossary suggestion should support the following actions:

| Action | Purpose |
|---|---|
| Accept | Approve the suggestion without changes. |
| Edit | Modify the suggestion before approval. |
| Skip | Leave the suggestion unresolved for later review. |
| Delete | Remove an incorrect or unnecessary suggestion. |
| Regenerate | Produce a new suggestion using current project information. |
| Re-Validate | Re-run validation after edits or structural changes. |
| Link | Associate the suggestion with an existing glossary concept. |
| Merge | Combine duplicate or overlapping concepts under one authoritative entry. |
| Reject | Decline the suggestion while recording the reason. |

Automation should never approve its own terminology changes when they affect Vision, Mission, Core Values, Ethics, Safety, Governance, Human Flourishing, Architecture, or Project Philosophy.

These changes require explicit human review.

---

## Suggestion Lifecycle

Every glossary suggestion should have a traceable lifecycle.

```text
Detected
    ↓
Analysed
    ↓
Suggested
    ↓
Edited
    ↓
Validated
    ↓
Accepted
    ↓
Implemented
    ↓
Verified
    ↓
Archived
```

Alternative paths may include:

```text
Detected → Suggested → Skipped
Detected → Suggested → Merged → Re-Validated
Detected → Suggested → Rejected → Archived
Detected → Suggested → Deleted
```

Deleted suggestions should remain recoverable where an audit trail is required.

---

## Suggestion Records

Glossary suggestions may receive their own identifiers.

The recommended format is:

```text
SGS-NNNNN
```

Example:

```text
SGS-00021

Status:
Suggested

Origin:
Documentation Validator

Detected In:
07_version-control-and-deployment.md

Proposed Concept:
Decision Traceability

Suggested Glossary Identifier:
GLS-041

Related Strategy:
STR-013

Affected Documents:
DOC-007
DOC-020

Current State:
Awaiting Review
```

Suggestion identifiers preserve the audit trail even when a proposal is edited, merged, rejected, or deleted.

---

## Machine-Readable Register

The human-readable Glossary should remain in Markdown.

A machine-readable register may be maintained alongside it.

Recommended file:

```text
docs/glossary-register.json
```

The register may contain:

- Glossary Identifier
- Preferred Term
- Definition
- Status
- Aliases
- Relationships
- Referenced Documents
- Referenced Modules
- Referenced Database Objects
- Revision Metadata

The Markdown file remains authoritative for human understanding.

The machine-readable register supports validation, automation, dependency analysis, search, and future tooling.

---

## Validation Rules

The Glossary validator should verify:

- Identifier uniqueness
- Identifier format
- Preferred-term uniqueness
- Alias conflicts
- Required headings
- Valid statuses
- Valid concept relationships
- Valid references
- Missing referenced concepts
- Use of deprecated terms
- Duplicate definitions
- Formatting consistency

Validation should produce a report before changes are committed.

```text
Glossary validation

Passed:
✓ Identifier format
✓ No duplicate approved identifiers
✓ Valid entry structure
✓ Valid internal references

Review required:
! 2 new concepts detected
! 1 possible duplicate
! 1 deprecated term still in use

No files were changed automatically.
```

---

## Knowledge Integrity

The Glossary protects the semantic integrity of the project.

Every strategy, document, software module, database table, interface, workflow, and research activity should use the same terminology for the same concept.

Automation exists to reduce errors, identify inconsistencies, and suggest improvements.

Human contributors remain responsible for meaning, context, and final approval.

---

## Relationship with the Project Reference System

The Glossary defines what concepts mean.

The Project Reference System defines how significant project objects are uniquely identified and connected.

The Glossary uses the `GLS` category within the Project Reference System.

Together they provide semantic consistency, stable identification, cross-document traceability, automated validation, reduced coordination errors, and preserved project knowledge.

---

## Long-Term Vision

The Glossary should develop into a continuously maintained semantic map of the Microgreens ERP project.

It should enable contributors to:

- Discover the authoritative meaning of a concept
- Locate where that concept is implemented
- Understand which systems depend upon it
- Identify related strategies and documents
- Detect conflicting terminology
- Preserve meaning across future revisions

The objective is not to control language unnecessarily.

The objective is to ensure that shared understanding remains strong enough to support safe, coordinated, and responsible development.
