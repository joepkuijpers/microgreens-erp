# Project status

## Purpose

This document provides an evidence-based overview of the current state of the Microgreens ERP project.

It distinguishes between functionality that is working, partially implemented, designed, or intended for the future. It does not treat the existence of a file as proof that a feature is complete.

This status was reviewed on 27 July 2026 after the repository restructuring and Raspberry Pi deployment validation.

## Current phase

The project has moved beyond documentation architecture and initial prototyping.

Its current phase is:

> Operational foundation and implementation validation

The ERP now provides a working technical and operational foundation for one Raspberry Pi-based microgreens production environment.

The next objective is to validate the existing business processes, improve reliability and security, and connect implementation progress more closely to the wider biological and engineering documentation.

## Status definitions

| Status | Meaning |
|--------|---------|
| Working | Present and operationally tested locally or on the Raspberry Pi. |
| Partial | A usable foundation exists, but coverage, validation or integration remains incomplete. |
| Designed | Described or technically prepared, but not demonstrated as an operational feature. |
| Future | Part of the long-term direction without a current implementation. |

## Verified technical foundation

The following foundation was tested during the repository migration:

- local development on Windows;
- deployment to the Raspberry Pi through Git;
- PHP syntax validation;
- SQLite connectivity;
- environment-aware database selection;
- Apache serving the application from `public/`;
- separation of public and private application paths;
- inaccessible private application, configuration and database paths;
- scheduled execution through `scripts/`;
- runtime state and logging through `storage/`;
- shared write access for the scheduler and web server;
- working dashboard, settings pages, assets and system API;
- clean Git working tree on the Raspberry Pi.

## Implementation status

### Architecture and operation

| Area | Status | Current evidence | Main remaining work |
|------|--------|------------------|---------------------|
| Repository structure | Working | Public, private, configuration, script and runtime layers are separated. | Maintain the boundaries during future development. |
| Raspberry Pi deployment | Working | The active branch deploys through a fast-forward Git pull. | Document and automate the complete deployment procedure. |
| Public web layer | Working | Apache serves the existing application URL from `public/`. | Add repeatable Apache configuration to deployment documentation. |
| Private path protection | Working | Application, configuration and database URLs return HTTP 404. | Include the protection checks in automated deployment tests. |
| Database connection | Working | Windows and Linux select appropriate databases; an environment override is available. | Add clearer configuration and connection failure reporting. |
| Runtime storage | Working | Logs and state files are written to `storage/` with controlled permissions. | Add retention, rotation and backup rules. |
| Scheduler | Working | Cron executes `scripts/scheduler_runner.php` every minute. | Monitor failures and resolve remaining runtime warnings. |
| Watchdog | Partial | Heartbeat state is created and updated. | Validate recovery behaviour during actual failures. |
| Automated tests | Future | Validation currently uses syntax checks and manual HTTP checks. | Add repeatable integration and regression tests. |
| Authentication and roles | Designed | No operational user authentication or permission model has been demonstrated. | Add login, sessions, roles and access control before wider use. |
| Audit trail | Designed | Operational logs exist, but user and record changes are not comprehensively audited. | Record who changed important data, what changed and when. |
| Backup and recovery | Partial | Database backup files exist. | Establish automated backups and test restoration. |
| Database migrations | Partial | Database resources are present, but schema evolution is not yet fully controlled. | Introduce reproducible and versioned migrations. |

### Operational ERP functions

| Area | Status | Current evidence | Main remaining work |
|------|--------|------------------|---------------------|
| Dashboard | Working | Main and operational dashboards are accessible on the Raspberry Pi. | Validate every card and metric against its source data. |
| Crop profiles | Partial | Profile lists, details and crop-related engines exist. | Complete management functions and validate biological parameters. |
| Grow batches | Partial | Batch creation, editing, details, rotation and harvesting exist. | Test the complete lifecycle and all exceptional paths. |
| Inventory | Partial | Inventory creation, editing, deletion and transactions exist. | Add stronger lot identity, minimum-stock control and auditing. |
| Finished inventory | Partial | A finished inventory overview exists. | Validate conversion from harvest through storage to sale. |
| Harvests | Partial | Harvest processing, lists and details exist. | Add quality, loss and measured-yield registration. |
| Sales | Partial | Sales entry, listing and traceability detail exist. | Add formal order, invoice, payment and cancellation handling. |
| Customers | Partial | Customer creation and listing exist. | Add complete editing, validation and history. |
| Suppliers | Partial | Supplier information and listings exist. | Complete supplier management and purchasing traceability. |
| Products | Partial | Product listings and product reports exist. | Add complete product management and validation. |
| Labour | Partial | Hours, activities, batch links and labour-cost calculations exist. | Add correction controls, permissions and report validation. |
| Production planning | Partial | Planning engines and a production planner page exist. | Validate orders, capacity, materials and timing as one process. |
| Reports | Partial | Revenue, customer, product, inventory, yield and profit reports exist. | Validate accounting assumptions and data completeness. |
| PDF reporting | Future | No operational PDF reporting has been demonstrated. | Define report requirements before implementation. |
| Settings | Partial | General, sensor, climate, lighting and water settings exist. | Replace remaining placeholder options and validate saved values. |
| Languages | Partial | Dutch and English contain translations and the language helper works. | Complete German, Spanish, French and Italian translations. |

### Monitoring and automation

| Area | Status | Current evidence | Main remaining work |
|------|--------|------------------|---------------------|
| Sensor logging | Partial | Sensor services, API endpoints, database logging and a cron reader exist. | Reconnect sensors and validate continuous operation. |
| Sensor health | Partial | Missing and stale readings can produce an alarm state. | Test disconnection, reconnection and recovery behaviour. |
| Climate monitoring | Partial | Climate engine, API and dashboard components exist. | Resolve missing timestamp warnings and validate thresholds. |
| Lighting monitoring | Partial | Lighting engine, settings and BH1750 support exist. | Calibrate measurements and validate biological thresholds. |
| Water monitoring | Partial | Water engine and settings exist. | Connect real measurements and irrigation decisions. |
| GPIO control | Partial | Raspberry Pi and simulation drivers, APIs and logging exist. | Test real outputs safely under representative loads. |
| Relay control | Partial | Relay, priority and state management exist. | Validate physical fail-safe behaviour. |
| Override control | Partial | Override logic and runtime state exist. | Test conflicts, expiry and emergency scenarios. |
| Safety engine | Partial | Safety logic is present. | Define, test and document formal safety rules. |
| Hardware health | Partial | Health checks and runtime logging exist. | Connect checks to alerts and recovery procedures. |
| Energy monitoring | Partial | Energy logic and a dashboard exist. | Measure real consumption and validate optimisation decisions. |

### Documentation-led development

| Area | Status | Relationship to implementation |
|------|--------|--------------------------------|
| Biological monitoring | Designed | Documentation is considerably more developed than operational measurement. |
| Indicator plants | Designed | Biological use is documented, but no structured ERP registration exists. |
| QR identification | Designed | Position and identifier concepts exist, but scanning and database integration are not implemented. |
| Computer vision | Designed | Future monitoring uses are described, but no validated camera workflow exists. |
| Water experimentation | Designed | Candidate methods and measurements are documented but remain experimental. |
| Lighting innovation | Designed | Energy-saving concepts are documented but not yet operationally tested. |
| Preservation research | Designed | Biological and engineering foundations are extensive, but no ERP experiment module exists. |
| Freeze drying | Future | It is a core strategic technology but is not yet integrated into the ERP or production system. |
| Packaging and shelf life | Designed | Documentation exists; controlled trials and ERP records are still required. |
| Humanitarian nutrition | Future | The long-term purpose is documented, but depends on proven local production, preservation and logistics. |
| Multi-site scaling | Future | The architecture anticipates growth, but only one installation is currently validated. |

## Overall assessment

The ERP is no longer only a prototype or documentation concept.

It is a working operational foundation containing cultivation, inventory, harvest, sales, labour, planning, reporting, sensor and hardware components.

However, the project is not yet a completed production-grade ERP.

The strongest part is the technical foundation for one controlled Raspberry Pi installation.

The largest gap is between the broad, mature documentation and the level of validation in the implemented software. Many documented ideas are deliberately ahead of the code and should remain labelled as designed or future until evidence demonstrates otherwise.

## Current priorities

The recommended order of work is:

1. Validate the complete daily workflow from supplier and inventory through cultivation, harvest and sale.
2. Add authentication, roles and access control.
3. Establish automated backup and tested recovery.
4. Resolve current runtime warnings and incomplete error handling.
5. Add automated integration tests for critical pages, APIs and scheduled processes.
6. Reconnect and validate sensors over a sustained period.
7. Add complete record-level traceability and auditing.
8. Implement QR-based identification as the first major documentation-led extension.
9. Begin controlled preservation experiments only after operational data quality is reliable.

## Documentation status

The main documentation index correctly identifies this file as the current project status.

`docs/11_roadmap.md` currently contains an invalid historical placeholder and must not be treated as an authoritative roadmap until it is deliberately reconstructed.

Unapproved drafts and editorial files remain outside the authoritative reading sequence until reviewed.

## Review rule

This document should be reviewed after a significant operational milestone, architectural migration or validated feature release.

Status must be based on observable evidence.

A planned feature must not be labelled as working merely because documentation or a placeholder file exists.
