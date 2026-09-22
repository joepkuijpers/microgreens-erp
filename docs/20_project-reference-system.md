# Project Reference System

## Purpose

The Project Reference System (PRS) establishes a unified identification and traceability system for significant objects within the Microgreens ERP project.

Its purpose is to improve communication, coordination, documentation, engineering, software development, research, maintenance, safety, governance, and long-term knowledge preservation.

The PRS provides a consistent way to reference documents, concepts, principles, strategies, architecture, software modules, user interfaces, APIs, database objects, hardware, drivers, sensors, actuators, automation, reports, tests, procedures, research, safety controls, governance decisions, suggestions, and validation findings.

The PRS functions as the project's universal reference language.

---

## Philosophy

Every significant project object should have one stable identity.

Files, implementations, and locations may change.

The identity of the object should remain traceable.

A reference system should help people understand the project.

It should not create unnecessary administrative burden.

Identifiers should therefore be introduced where they improve clarity, traceability, impact analysis, coordination, validation, recovery, and long-term maintenance.

The PRS supports human understanding first.

Automation may maintain, validate, and suggest references, but significant changes remain subject to human review.

---

## Objectives

The Project Reference System aims to:

- Establish unique identifiers across the project
- Reduce ambiguity
- Improve cross-referencing
- Strengthen traceability
- Support dependency analysis
- Improve change-impact assessment
- Reduce duplicate definitions
- Support automated validation
- Preserve institutional knowledge
- Strengthen safety and resilience
- Support long-term stewardship

---

## Core Principles

### Permanent Identity

Identifiers should remain stable over time.

Objects may evolve.

Their identifiers should not.

### No Reuse

Identifiers should never be reassigned to another object.

Retired identifiers remain reserved.

### One Object, One Identifier

A significant object should have one authoritative PRS identifier within its category.

### Human-Readable Context

Identifiers should normally appear together with a descriptive name.

Example:

```text
Human Flourishing Strategy (STR-017)
```

### Traceability Before Complexity

Identifiers should be used where they improve understanding and traceability.

They should not be introduced merely to increase the number of references.

### Controlled Evolution

New categories may be added without changing existing identifiers.

Changes to category meaning require documented review.

---

## Identifier Format

The standard format is:

```text
PREFIX-NNN
```

Examples:

```text
DOC-001
GLS-004
STR-013
DBT-021
DRV-003
SNS-002
```

Where larger categories require additional capacity, more digits may be used:

```text
SGS-00021
VAL-00142
```

Identifiers should:

- Use uppercase prefixes
- Use a hyphen separator
- Use leading zeros
- Remain unique within their category
- Remain stable
- Never be reused

---

## Reserved Categories

| Prefix | Category |
|---|---|
| DOC | Documentation |
| GLS | Glossary Concept |
| PRN | Principle |
| STR | Strategy |
| ARC | Architecture Component |
| MOD | ERP Module |
| UI | User Interface |
| API | Application Programming Interface |
| DBT | Database Table |
| DBF | Database Field |
| DBV | Database View |
| DBR | Database Relationship |
| MIG | Database Migration |
| HAL | Hardware Abstraction Layer |
| DRV | Driver |
| SNS | Sensor |
| ACT | Actuator |
| AUT | Automation Rule or Process |
| CFG | Configuration Item |
| WRK | Workflow |
| DEP | Deployment Item |
| REL | Release |
| RPT | Report |
| RES | Research Item |
| TST | Test |
| PRC | Procedure |
| SEC | Safety or Security Control |
| GOV | Governance Item |
| DEC | Decision Record |
| SGS | Suggestion |
| VAL | Validation Finding |

Additional categories may be introduced through documented review.

Existing prefixes should not be repurposed.

---

## Object Registration

A registered project object should include, where applicable:

- Identifier
- Name
- Category
- Purpose
- Status
- Definition
- Location
- Owner or Steward
- Related Objects
- Dependencies
- Referenced By
- Revision Metadata

Example:

```text
Identifier:
DBT-021

Name:
labor_entries

Category:
Database Table

Purpose:
Stores labour registrations linked to project operations and cultivation batches.

Defined In:
database/schema.sql

Used By:
MOD-006
UI-014
RPT-005

Related Strategy:
STR-017

Status:
Active
```

---

## Traceability Model

The PRS supports traceability across the complete project.

A single object may be connected through multiple layers.

```text
Glossary Concept
    ↓
Strategy
    ↓
Architecture
    ↓
Module
    ↓
Database Object
    ↓
API
    ↓
User Interface
    ↓
Test
    ↓
Documentation
```

Hardware traceability may follow:

```text
Sensor
    ↓
Driver
    ↓
Hardware Abstraction Layer
    ↓
Automation
    ↓
Database
    ↓
ERP Module
    ↓
User Interface
    ↓
Documentation
```

Database traceability may follow:

```text
Database Table
    ↓
Defined
    ↓
Migrated
    ↓
Queried
    ↓
Displayed
    ↓
Reported
    ↓
Archived
```

---

## Reference Requirements

Whenever practical, documentation should identify:

- Where an object is defined
- Where it is implemented
- Where it is referenced
- Which components depend upon it
- Which documents describe it
- Which tests validate it
- Which strategy governs it
- Which safety controls apply to it

References should remain useful to people reading the documentation without requiring specialist tooling.

---

## Machine-Readable Registers

The PRS should be supported by machine-readable registers.

Recommended files include:

```text
docs/project-reference-register.json
docs/glossary-register.json
docs/document-register.json
docs/architecture-rules.json
docs/validation-rules.json
```

The registers may support identifier validation, dependency analysis, duplicate detection, reference checking, documentation generation, impact analysis, automated suggestions, future visualisation, and future AI-assisted navigation.

Markdown remains the primary human-readable format.

Machine-readable registers support automation and validation.

---

## Automated Reference Maintenance

The Project Reference System should support automated validation and assisted maintenance.

Automation may detect:

- New objects without identifiers
- Duplicate identifiers
- Invalid identifier formats
- Reused retired identifiers
- Missing references
- Broken links
- Undefined software components
- Undefined database objects
- Invalid category usage
- Missing documentation
- Inconsistent terminology
- Incorrect architectural placement
- Formatting errors
- Stale dependencies

Detected issues should produce suggestions rather than uncontrolled modifications.

---

## Standard Suggestion Flow

The standard human-in-the-loop review flow is:

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
Apply, Defer, or Reject
    ↓
Re-Validate
    ↓
Commit
```

No automated suggestion should be treated as correct merely because it was generated automatically.

---

## Suggestion Actions

Every PRS suggestion should support the following actions:

| Action | Purpose |
|---|---|
| Accept | Approve and apply the suggestion. |
| Edit | Modify the suggestion before approval. |
| Skip | Leave the suggestion unresolved for later review. |
| Delete | Remove an incorrect or unnecessary suggestion. |
| Regenerate | Produce a new suggestion using current project information. |
| Re-Validate | Re-run validation after edits or changes. |
| Link | Associate the suggestion with an existing project object. |
| Merge | Combine duplicate or overlapping objects under one authoritative reference. |
| Reject | Decline the suggestion while preserving the reason. |

Where useful, the interface may also support:

- View Context
- Compare
- Undo
- Restore
- Escalate for Review

---

## Suggestion Lifecycle

Every suggestion should have a traceable lifecycle.

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

Alternative paths include:

```text
Detected → Suggested → Skipped
Detected → Suggested → Merged → Re-Validated
Detected → Suggested → Rejected → Archived
Detected → Suggested → Deleted
```

Deleted suggestions should remain recoverable where an audit trail is required.

---

## Suggestion Identifiers

Suggestions may receive permanent identifiers.

Recommended format:

```text
SGS-NNNNN
```

Example:

```text
SGS-00021

Status:
Suggested

Priority:
Medium

Origin:
Documentation Validator

Detected In:
07_version-control-and-deployment.md

Suggested Object:
Decision Traceability

Suggested Category:
GLS

Suggested Identifier:
GLS-041

Related Strategy:
STR-013

Affected Objects:
DOC-007
DOC-020

Current State:
Awaiting Review
```

---

## Validation Findings

Validation findings may receive identifiers using:

```text
VAL-NNNNN
```

Example:

```text
VAL-00142

Severity:
Warning

Rule:
PRS-DUPLICATE-ID

Affected Identifier:
DBT-021

Detected In:
project-reference-register.json

Status:
Open
```

This separates detected validation problems from proposed solutions.

---

## Architecture Validation

The PRS may validate whether project objects are placed in the appropriate architectural layer.

```text
Architecture warning

File:
ui/control_panel.php

Detected:
Direct GPIO operation

Problem:
User Interface code should not directly control hardware.

Suggested correction:
Move hardware communication to DRV or HAL.

Expose the operation through:
Business Logic → API → Controller
```

Architecture rules should be defined in:

```text
docs/architecture-rules.json
```

---

## Terminology Validation

The PRS should integrate with the Glossary.

When non-standard terminology is detected, the system should suggest the authoritative term.

```text
Unrecognised term:
AI Prediction

Suggested controlled term:
Predictive Analysis (GLS-031)

Reason:
Predictions may originate from humans, statistics, rules, simulations, or Artificial Intelligence.

Actions:
Accept
Edit
Skip
Delete
Regenerate
Re-Validate
Link
Merge
Reject
```

---

## Human Review Requirements

Automated changes should never bypass explicit human review when they affect:

- Vision
- Mission
- Core Values
- Ethics
- Safety
- Governance
- Human Flourishing
- Architecture
- Data meaning
- Database migrations
- Access control
- Project philosophy
- Stewardship responsibilities

These areas require accountable human approval.

---

## Validation Before Commit

The PRS validator may run:

- During documentation review
- Before commit
- Before push
- During pull request review
- Before release
- After large structural changes

```text
Project reference validation

Passed:
✓ Identifier format
✓ No duplicate active identifiers
✓ Valid category prefixes
✓ Valid internal references
✓ Valid Markdown structure

Review required:
! 2 new concepts
! 1 undefined database table
! 1 architecture-layer warning
! 1 deprecated term still in use

No files were changed automatically.
```

---

## Audit Trail

Every accepted, edited, merged, rejected, or deleted suggestion should preserve sufficient history to explain:

- What was detected
- What was proposed
- Who reviewed it
- What action was selected
- Why the action was selected
- What files changed
- Whether re-validation succeeded
- Which commit implemented the result

The audit trail protects traceability and supports future learning.

---

## Relationship with the Glossary

The Glossary defines what concepts mean.

The PRS identifies where those concepts and other project objects exist.

The Glossary provides semantic consistency.

The PRS provides stable identity and traceability.

Together they support shared understanding, reduced ambiguity, coordinated development, automated validation, dependency analysis, and knowledge preservation.

---

## Safety and Governance

The PRS should support the Safety & Resilience Strategy and Stewardship & Governance.

Critical objects should be identifiable, traceable, reviewable, and recoverable.

The PRS should make it easier to determine:

- Which systems are affected by a change
- Which safety controls apply
- Which documentation must be updated
- Which stewards must review the change
- How the change may be reversed
- How the previous state may be restored

---

## Implementation Roadmap

### Stage 1 — Manual Registration

Identifiers are created and maintained in Markdown.

### Stage 2 — Machine-Readable Registers

Identifiers and relationships are stored in JSON registers.

### Stage 3 — Validation Scripts

Scripts validate identifiers, references, terminology, and structure.

### Stage 4 — Assisted Suggestions

The system proposes identifiers, links, definitions, and corrections.

### Stage 5 — Git Integration

Validation runs before commit, push, pull request, and release.

### Stage 6 — Visualisation and Impact Analysis

Dependencies and relationships can be explored through generated maps.

### Stage 7 — Integrated ERP Support

The ERP may provide interfaces for reviewing, editing, validating, and approving reference updates.

Human approval remains part of the workflow.

---

## Long-Term Vision

The Project Reference System is intended to become the central navigation and traceability framework of the Microgreens ERP project.

As the project grows, the PRS should support documentation generation, dependency analysis, architecture visualisation, impact assessment, automated validation, change coordination, safety review, governance review, and future AI-assisted engineering.

The objective is not simply to assign identifiers.

The objective is to preserve knowledge, reduce error margins, improve coordination, and ensure that the project remains understandable and maintainable across technologies, contributors, and generations.
