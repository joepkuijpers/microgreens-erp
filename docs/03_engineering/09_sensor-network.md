# Sensor network

## Why a network is needed

The sensor network moves observations from instruments to the ERP while preserving identity, time, location and quality. Its success is measured by trustworthy information, not by the number of connected devices.

## Main layers

| Layer | Responsibility |
| --- | --- |
| Instrument | Produces the observation |
| Local acquisition | Reads and checks the instrument |
| Rack node or gateway | Adds context and transfers records |
| ERP | Stores, displays and connects the evidence |

The first implementation may combine acquisition and gateway functions on one Raspberry Pi. The layers should still remain clear in the design.

## Observation records

Each record should include:

- sensor and measurement identity;
- timestamp;
- rack, shelf or portable position;
- value and unit;
- quality or validity state;
- calibration reference where relevant;
- acquisition software version.

A stable identifier such as a documented rack code can be linked through a QR code to its ERP record.

## Communication choices

Short local buses such as I²C can serve nearby instruments, but they are not intended for long, noisy rack networks. Longer distances may require a local rack node, robust wired communication or carefully managed wireless links.

Wired communication is generally easier to make predictable. Wireless communication is useful where cabling is difficult, but requires attention to coverage, interference, power and security.

## Time, buffering and duplicates

The acquisition point should use a reliable clock. If the network or ERP is unavailable, important observations may be buffered locally and transferred later. Each record needs an identity that prevents accidental duplication.

## Stale and disconnected sensors

The network must distinguish:

- current data;
- delayed data;
- stale data;
- missing data;
- invalid data;
- a disconnected device.

An old reading must never be shown as live. Automation must not continue on false confidence when the source has failed.

## Monitoring before control

Sensors should first prove that their readings are stable, understandable and correctly located. Only then may validated observations support limited automatic control. Human override and safe failure remain necessary.

## Portable instruments

Portable equipment joins the network for a defined session. The ERP should record the instrument, location, method, calibration state and time period without pretending it is a permanent sensor.

## Growth path

Start with one Raspberry Pi and a small number of identified sensors. Add rack nodes only when cable length, reliability or scale requires them. Multi-site networking should preserve the same observation structure.

## Related documents

- [Monitoring architecture](01_monitoring-architecture.md)
- [Connectors and cabling](07_connectors-and-cabling.md)
- [Climate control](10_climate-control.md)
