# Microgreens ERP

Microgreens ERP is a long-term project for developing an honest, transparent and sustainable biological food chain.

The project connects cultivation, operational management, engineering, research and food preservation within one traceable system.

> Technology should adapt to biology, not biology to technology.

Engineering exists to support biological understanding, not replace it.

## Purpose

Microgreens ERP is being developed to help observe, understand and improve the complete journey of food.

The system brings together:

- crop profiles and grow batches;
- harvest and inventory records;
- labour and production planning;
- customers, suppliers and sales;
- sensors, equipment and energy use;
- research, traceability and continuous improvement;
- preservation, storage and responsible distribution.

The ERP provides the shared information structure through which these activities can be measured and improved.

## Current Status

The project is under active development.

Development and testing take place locally on Windows. Changes are reviewed, tested and committed before deployment to the Raspberry Pi environment.

The current PHP application uses several entry files in the repository root. This structure remains in place while the application is working and will only be reorganised through a separately tested migration.

## Documentation

The documentation explains the project's purpose, scientific foundation and technical implementation.

| Documentation | Purpose |
|---------------|---------|
| [Documentation Index](docs/README.md) | Central entry point for project documentation. |
| [Project Foundation](docs/01_project-foundation/README.md) | Defines the project's origin, values, ethics and long-term direction. |
| [Preservation Energy Architecture](docs/02_strategic-foundation/README.md) | Explains preservation by beginning with biology. |
| [Engineering](docs/03_engineering/README.md) | Translates biological understanding into practical systems and measurements. |
| [Glossary](docs/17_glossary.md) | Defines shared project terminology. |
| [Documentation Style Guide](docs/18_documentation-style-guide.md) | Defines documentation language and formatting standards. |

## Repository Structure

| Path | Purpose |
|------|---------|
| `index.php` | Main application entry point. |
| `dashboard.php` | Main monitoring and management dashboard. |
| `operations_dashboard.php` | Operational overview and control entry point. |
| `api/` | Application endpoints for data and system functions. |
| `assets/` | Stylesheets, scripts and visual assets. |
| `database/` | Database definitions, migrations and controlled database resources. |
| `docs/` | Project, scientific and engineering documentation. |
| `hardware/` | Hardware integration and control components. |
| `includes/` | Shared PHP services, layouts, components and application logic. |
| `languages/` | Translation files used by the language system. |
| `settings/` | Application configuration pages and handlers. |

## Development Workflow

Changes follow a controlled sequence:

1. Control the current state.
2. Analyse the evidence.
3. Prepare an improvement plan.
4. Obtain approval.
5. Make one small change.
6. Test the result.
7. Commit and push the change.
8. Deploy only after local validation.

This workflow keeps development traceable and reduces the risk of unintended changes.

## Guiding Principles

- Understand before changing.
- Explain why before how.
- Observe before automating.
- Measure before optimising.
- Prefer simple, maintainable systems.
- Preserve traceability.
- Keep knowledge open and understandable.
- Improve the food chain without creating unnecessary dependency.

## Scope

This repository combines software, documentation, engineering and research.

It does not replace validated food-safety procedures, legal requirements, equipment manuals or professional scientific guidance. Operational decisions should remain connected to measured evidence, documented risk and appropriate validation.
