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

The application uses a separated runtime structure. Public web entry points are stored in `public/`, while application logic, configuration, database resources and runtime data remain outside the public web layer.

On the Raspberry Pi, Apache serves the existing `/microgreens/PHP/` URL from `public/`. Private application paths are not directly accessible through the web server. Scheduled processes run through `scripts/`, and generated state and log files are stored in `storage/`.

## Documentation

The documentation explains the project's purpose, scientific foundation and technical implementation.

| Documentation | Purpose |
|---------------|---------|
| [Documentation Index](docs/README.md) | Central entry point for project documentation. |
| [Project Foundation](docs/01_project-foundation/README.md) | Defines the project's origin, values, ethics and long-term direction. |
| [Preservation Energy Architecture](docs/02_understanding-how-things-work/README.md) | Explains preservation by beginning with biology. |
| [Engineering](docs/03_engineering/README.md) | Translates biological understanding into practical systems and measurements. |
| [Glossary](docs/17_glossary.md) | Defines shared project terminology. |
| [Documentation Style Guide](docs/18_documentation-style-guide.md) | Defines documentation language and formatting standards. |

## Repository Structure

| Path | Purpose |
|------|---------|
| `app/` | Private application services, shared components, language files and hardware integration. |
| `config/` | Environment-aware application configuration. |
| `database/` | Database definitions, migrations and controlled database resources. |
| `docs/` | Project, scientific and engineering documentation. |
| `public/` | Web-accessible PHP entry points, API endpoints, settings pages and assets. |
| `scripts/` | Command-line and scheduled application entry points. |
| `sensors/` | Sensor-reading services and related operational resources. |
| `storage/` | Generated runtime state and log files; runtime contents are excluded from Git. |

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
