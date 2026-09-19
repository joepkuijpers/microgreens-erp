# Project roadmap

## Purpose

This roadmap defines the recommended development order for the Microgreens ERP project.

It translates the project's long-term direction into controlled implementation phases without assigning artificial completion percentages or fixed deadlines.

The [Project status](21_project-status.md) describes what currently exists.

This roadmap describes what should be strengthened or developed next.

## Roadmap principles

Development should:

- protect working production functions;
- validate existing features before expanding their scope;
- keep public, private and runtime layers separated;
- improve traceability before adding complexity;
- treat biological measurements as evidence rather than decoration;
- distinguish operational features from experiments;
- keep documented ambitions labelled honestly;
- introduce one controlled and testable change at a time.

A phase is complete only when its defined outcome has been demonstrated.

## Current objective

The current objective is to turn the working single-installation foundation into a reliable and accountable operational ERP.

The immediate focus is therefore not rapid feature growth.

It is validation, security, recovery, traceability and confidence in the existing daily workflow.

## Phase 1 — Reliability and validation

### Why this phase comes first

The application already contains many operational components, but most have been validated individually rather than as one complete business process.

Reliable evidence is required before further automation or scaling.

### Main work

- validate the workflow from supplier and raw inventory through cultivation, harvest, finished inventory and sale;
- resolve current PHP runtime warnings and incomplete error handling;
- add automated checks for critical pages and API endpoints;
- add a repeatable scheduler test;
- verify database constraints and failure behaviour;
- document the Raspberry Pi deployment configuration;
- introduce log retention and rotation;
- establish automated database backups;
- perform and document a successful database restoration.

### Completion evidence

This phase is complete when:

- the critical operational workflow passes a documented end-to-end test;
- important pages and APIs have repeatable regression checks;
- scheduled execution failures are detectable;
- a current backup can be restored into a working test environment;
- deployment can be reproduced from documented instructions.

## Phase 2 — Security and accountability

### Why this phase is necessary

The current installation operates in a controlled environment, but wider use requires users, permissions and record-level accountability.

### Main work

- introduce authentication and secure sessions;
- define roles and permissions;
- protect state-changing requests;
- validate and sanitise user input consistently;
- record important data changes;
- associate changes with a user and timestamp;
- define access rules for administration, operations and observation;
- review the public API surface.

### Completion evidence

This phase is complete when:

- unauthenticated users cannot perform protected actions;
- each role has verified access boundaries;
- important record changes are traceable;
- security checks are included in regression testing.

## Phase 3 — Operational completeness

### Why this phase follows security

Daily records become more valuable when they are complete, consistent and attributable.

### Main work

- complete customer, supplier and product management;
- strengthen lot and batch identity;
- register harvest quality, measured yield and losses;
- validate finished-inventory movements;
- improve order, sale, cancellation and payment states;
- verify labour corrections and cost calculations;
- validate production-planning formulas against real cultivation data;
- review report calculations and accounting assumptions;
- replace remaining settings placeholders with working or clearly disabled options;
- complete translations only after interface text has stabilised.

### Completion evidence

This phase is complete when:

- each core record has a controlled lifecycle;
- stock movements can be traced to their source;
- operational totals reconcile across modules;
- incomplete features are clearly labelled rather than presented as working.

## Phase 4 — Sensor and hardware validation

### Why this remains a separate phase

Software presence does not prove measurement quality or physical safety.

Sensors and actuators must be tested against real equipment and biological conditions.

### Main work

- reconnect the environmental sensors;
- validate continuous sensor logging;
- test sensor disconnection and recovery;
- calibrate light, temperature and humidity measurements;
- validate threshold settings against crop profiles;
- test GPIO and relay outputs under representative loads;
- verify overrides, priorities and expiry behaviour;
- define physical and software fail-safe states;
- connect hardware-health failures to useful alerts;
- begin measuring real energy consumption.

### Completion evidence

This phase is complete when:

- measurements remain stable over a sustained test period;
- stale and missing data are detected correctly;
- outputs return to a safe state after failure;
- calibration and test evidence are documented;
- automation decisions can be reconstructed from stored data.

## Phase 5 — QR-based traceability

### Why this is the first major extension

QR identification directly connects the documented traceability principles to practical daily work.

It can improve accuracy without requiring premature automation.

### Main work

- define the permanent identifier format;
- connect identifiers to database records;
- generate printable QR labels;
- identify racks, positions, batches, equipment and containers;
- add scanning to relevant operational workflows;
- prevent duplicate and invalid identifiers;
- preserve identifier history when an item moves.

### Completion evidence

This phase is complete when:

- a physical item or position can be scanned reliably;
- the correct ERP record opens;
- movements remain traceable;
- identifiers are unique, stable and documented.

## Phase 6 — Controlled biological and preservation research

### Why this phase depends on earlier work

Experiments are only useful when measurements, identities and records are dependable.

### Main work

- create a controlled experiment record;
- connect experiments to batches, measurements and observations;
- distinguish hypotheses from validated findings;
- register indicator-plant observations;
- evaluate lighting and water experiments;
- define quality measurements for preservation;
- record packaging and shelf-life trials;
- prepare data structures for freeze-drying experiments;
- include energy use and nutrient-quality considerations.

### Completion evidence

This phase is complete when:

- experiments can be reproduced from stored records;
- control and treatment groups remain distinguishable;
- observations are linked to measurements and identifiers;
- conclusions state their evidence and limitations.

## Phase 7 — Preservation and responsible scaling

### Long-term direction

This phase develops only after the local production and research system has demonstrated reliable operation.

### Main work

- validate freeze-drying processes;
- measure quality, energy use and storage performance;
- integrate preserved inventory into traceability;
- evaluate packaging and distribution requirements;
- document economic and biological trade-offs;
- support local knowledge transfer;
- avoid creating unnecessary dependency;
- evaluate multi-site operation only after one installation is reproducible.

### Completion evidence

This phase requires demonstrated preservation quality, responsible logistics, reliable traceability and a credible local-production model.

## Work selection rule

When choosing the next task:

1. Check the current project status.
2. Identify the earliest incomplete roadmap phase.
3. Select the smallest change that produces useful evidence.
4. Define the expected result before implementation.
5. Test the change locally.
6. Commit it separately.
7. Deploy only after validation.
8. Update the status or roadmap when the evidence changes.

Urgent safety, data-loss or production failures take priority over this sequence.

## Relationship to other documents

This roadmap should be read together with:

- [Project status](21_project-status.md);
- [Decision framework](23_decision-framework.md);
- [Decision records](24_decision-records.md);
- [Engineering documentation](03_engineering/README.md);
- [Project Foundation](01_project-foundation/README.md).

Detailed implementation tasks belong in controlled work records rather than being accumulated indefinitely in this roadmap.

## Review rule

Review this roadmap when:

- a phase reaches its completion criteria;
- new evidence changes the recommended order;
- a material risk is discovered;
- the project's operational context changes.

Completed work should be reflected in the Project status.

Historical reasoning should be preserved in decision records and Git history.
