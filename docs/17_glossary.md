# Glossary

> Official terminology and definitions for the Microgreens ERP project.

---

## Document Information

| Property | Value |
|----------|-------|
| Document ID | DOC-017 |
| Document | Glossary |
| Version | 1.0 |
| Status | 📋 Planned |
| Author | Joep Kuijpers |
| Project | Microgreens ERP |
| Location | /docs/17_glossary.md |
| Last Updated | 2026-07-12 |
| Target Audience | Developers, administrators and project members |

---

# Purpose

The Glossary defines the official terminology used throughout the Microgreens ERP project.

All documentation, source code, database objects and user interfaces should use the terminology defined in this document as the project standard.

---

# Contents

- ERP Terminology
- Technical Terminology
- Hardware
- Database
- Programming Languages
- Development Tools
- Abbreviations
- Naming Conventions
- Definitions
- Standard Terminology

---

# Usage

Before introducing new functionality, always verify whether a term already exists in this glossary.

If a term does not yet exist, add it to the Glossary before using it elsewhere in the project.

---

# Project Terminology

## ERP Modules

| Official Term | Do Not Use | Notes |
|--------------|------------|-------|
| Crop Profiles | Cultivation Profiles | Module name and database table |
| Grow Batches | Grows | Module name |
| Harvests | Harvesting (as module name) | Module name |
| Inventory | Stock | Module name |
| Inventory Transactions | Stock Movements | Module name |
| Finished Inventory | Final Inventory | Module name |
| Products | Items | Module name |
| Customers | Customer List | Module name |
| Suppliers | Supplier List | Module name |
| Sales | Orders | Module name |
| Labor | Work | Module name |
| Equipment | Devices | Module name |
| Energy Dashboard | Energy Overview | Module name |
| Production Planner | Production Planning | Module name |
| Scheduler | Planning | Module name |
| Settings | Configuration | Module name |

---

## Traceability

| Official Term | Do Not Use |
|--------------|------------|
| Traceability | Product Tracking |
| Batch | Lot |
| Harvest | Crop Harvest (as technical object) |
| Crop Profile | Cultivation Profile |
| Grow Batch | Cultivation Batch |

---

## Hardware

| Official Term | Do Not Use |
|--------------|------------|
| Raspberry Pi | RasPi |
| GPIO | General IO |
| BH1750 | Lux Sensor |
| BME280 | Climate Sensor |
| Relay | Switch |
| Water Pump | Pump |
| Grow Light | Lamp |
| Fan | Ventilator (as object name) |

---

## Database

Always use the same names as defined in the database schema.

| SQL Table | Description |
|-----------|-------------|
| crop_profiles | Crop Profiles |
| grow_batches | Grow Batches |
| harvests | Harvests |
| inventory | Inventory |
| inventory_transactions | Inventory Transactions |
| finished_inventory | Finished Inventory |
| products | Products |
| customers | Customers |
| suppliers | Suppliers |
| sales | Sales |
| labor_entries | Labor |
| equipment | Equipment |
| settings | Settings |

---

## Programming Languages

| Always Use | Do Not Use |
|------------|------------|
| PHP | Php |
| JavaScript | JS Script |
| HTML | Html |
| CSS | Css |
| SQL | Sql |

---

## Development Tools

| Always Use | Do Not Use |
|------------|------------|
| Git | GIT |
| GitHub | Github |
| VS Code | Visual Studio Code Editor |
| PowerShell | Powershell |
| SQLite | SqlLite |
| Apache | Apache2 (in documentation) |

---

## Abbreviations

| Abbreviation | Meaning |
|--------------|---------|
| ERP | Enterprise Resource Planning |
| AI | Artificial Intelligence |
| GPIO | General Purpose Input/Output |
| EC | Electrical Conductivity |
| pH | Potential of Hydrogen |
| HACCP | Hazard Analysis and Critical Control Points |
| SOP | Standard Operating Procedure |
| KPI | Key Performance Indicator |

---

## Project Vocabulary

The following terminology is standardized throughout the Microgreens ERP project.

Use these terms consistently in all documentation, source code, user interfaces and project communication.

| Preferred Term | Description |
|----------------|-------------|
| Food Chain | Standard term for the complete chain from cultivation to consumption. |
| Project Foundation | Collection of documents defining the project's long-term direction, principles and philosophy. |
| Vision | Defines the long-term future the project strives to help create. |
| Mission | Defines how the vision is translated into action. |
| Core Values | Fundamental values that guide every decision within the project. |
| Guiding Principles | Principles used to support technical, operational and strategic decisions. |
| Design Philosophy | The design approach used throughout the project. |
| Long-Term Strategy | Defines the long-term development and growth of the project. |
| Sustainability | Responsible use of resources while balancing environmental, social and economic considerations. |
| Transparency | Openness in processes, decisions, documentation and communication. |
| Continuous Improvement | Ongoing optimization of processes, software, hardware and documentation. |
| Knowledge Sharing | Openly sharing knowledge, experience and research to encourage collaboration and innovation. |
| Food Production and preservation | The complete process of cultivating, processing and distributing food. |
| Cultivation Based on Biological Principles | Standard description of the cultivation method until certified organic production is achieved. |
| Certified Organic Production | Use only when referring to the long-term objective after official certification has been obtained. |
| Freeze Drying | The preservation process that removes moisture while maintaining product quality. |
| Freeze Dryer | The machine used to perform freeze drying. |
| Artificial Intelligence (AI) | Use the full term on first reference, then use AI thereafter. |
| Single Source of Truth | The authoritative location for information that should not be duplicated elsewhere. |
| Documentation Architecture | The overall structure and organization of the project's documentation. |
| Scalable | Designed to support future growth without requiring major redesign. |
| Modular | Designed so that components can be developed, replaced or extended independently. |
| Maintainable | Designed to simplify long-term maintenance, updates and continuous improvement. |

---

## GreenScope

The project's framework for evaluating, implementing and continuously improving environmental practices throughout the Food Chain.

---

## Plant Biotope

The complete environmental conditions required to reproduce the crop's natural biological habitat.

A Plant Biotope includes:

- Light
- Temperature
- Relative Humidity
- Carbon Dioxide
- Airflow
- Root Environment
- Water
- Nutrient Availability
- Biological Interactions

The Plant Biotope may be either controlled or dynamic depending on the crop and cultivation method.

---

## Thermal Zoning

The intentional separation of warm and cold operational environments to reduce energy consumption and improve overall system efficiency.

---

## Adaptive Energy Control

The continuous adjustment of energy consumption to match the requirements of the Plant Biotope and operational processes.

---

## Sourcefulness

Making the most effective use of existing knowledge, materials, equipment, infrastructure and natural resources before introducing additional resources.

---

## Systems Integration

Designing systems so that individual processes strengthen one another instead of operating independently.

---

## Sustaindoity

The stage at which sustainability becomes an integrated way of thinking rather than an objective.

Sustaindoity is achieved when sustainable principles naturally influence every decision throughout the Food Chain.

It represents the transition from measuring sustainability to living sustainability.