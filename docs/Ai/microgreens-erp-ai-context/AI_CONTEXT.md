{\rtf1}# MICROGREENS ERP — AI CONTEXT

VERSION: 1.0.0
LAST VERIFIED: 2026-08-24

IMPORTANT RULES
- Repository code is the source of truth.
- Never assume missing functionality.
- Separate FACT / INFERENCE / UNKNOWN.
- Do not modify code unless explicitly instructed.
- Always verify against actual files.

REPOSITORY
C:\Users\joepk\Downloads\microgreens-erp

CORE ARCHITECTURE

Sensor Data Layer:
- public/api/sensors.php
  → returns raw latest sensor_log record
  → NO status logic

Status Layer:
- public/api/status.php
  → determines online/offline based on timestamp age
  → uses refresh_seconds * 3

Sensor Health Layer:
- app/includes/sensor_health.php
  → get_sensor_health()
  → evaluates:
     - timestamp age (stale detection)
     - individual sensor validation (check_sensor_value)
     - crop profile limits
  → defines:
     - ok / alarm
     - missing = null values
     - stale = timestamp too old

Watchdog Layer:
- app/includes/watchdog_engine.php
  → heartbeat system
  → status:
     - ok
     - timeout
     - unknown
  → triggers fail_safe_required when unhealthy

Hardware Health Layer:
- app/includes/hardware_health_engine.php
  → combines:
     - GPIO status
     - watchdog
     - emergency stop
     - maintenance mode
     - manual override
     - disk space

Dashboards:

public/dashboard.php
- uses:
  - sensor_health.php API
  - status.php API
- shows:
  - temperature
  - humidity
  - light
  - online/offline
- DOES NOT include:
  - watchdog
  - GPIO
  - hardware safety

public/operations_dashboard.php
- uses:
  - hardware_health.php
  - watchdog.php
  - gpio.php
  - override.php
  - scheduler.php
  - automation.php
- full system control view

KNOWN LIMITATIONS
- dashboard.php lacks hardware + watchdog integration
- sensor "pressure" exists in DB but not displayed
- no unified status model across dashboards

CURRENT STATE
- sensor logic split into:
  1. raw data layer
  2. status layer
  3. health layer
  4. hardware layer

NEXT STEP (NOT EXECUTED YET)
- unify status model OR keep separation explicitly documented