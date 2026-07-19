# Sensor selection

## Start with the question

Sensor selection begins with what the project needs to understand, not with a shopping list. The correct instrument is the simplest one that can answer the question reliably in the real cultivation environment.

Before comparing products, define:

- the variable;
- expected range;
- required accuracy and response time;
- fixed or portable use;
- exposure to moisture, heat, dirt and cleaning;
- communication distance;
- calibration and maintenance capacity.

## Four equipment roles

| Role | Use |
| --- | --- |
| Exploratory | Low-cost investigation and learning |
| Operational indicator | Trends and practical warnings |
| Operational instrument | Decisions that require known performance |
| Reference or research | Verification, calibration and controlled studies |

An inexpensive sensor can be valuable when its limitations are understood. A professional instrument is only justified when better evidence changes a meaningful decision.

## Compare the whole instrument

Selection should consider:

- range, resolution and stated accuracy;
- repeatability, drift and response time;
- resistance to condensation and contamination;
- mounting and cable requirements;
- documentation and supplier support;
- calibration method;
- expected service life;
- repair or replacement options;
- Raspberry Pi compatibility;
- total cost of ownership.

The sensing element must also measure the correct quantity. Lux, for example, describes human-visible light and is useful for trends, but it is not the same as photosynthetically active radiation.

## Budget levels

| Level | Appropriate use | Main limitation |
| --- | --- | --- |
| Low Budget | Prototypes, learning and non-critical trends | More verification and shorter life may be expected |
| Medium Budget | Routine commercial monitoring | Calibration and environmental protection still matter |
| Professional | Reference work, high-consequence control or research | Higher purchase and maintenance cost |

Estimated prices and suppliers change. Record the date, supplier, warranty and exact model whenever a purchasing decision is made.

## Current project examples

The BH1750 light sensor is suitable for learning, trend monitoring and initial lighting comparisons. It should not be presented as a professional plant-light instrument.

Temperature and humidity sensors require placement near the crop zone without direct contact with water or lamp heat. Gas, pH and conductivity instruments need stronger attention to calibration, ageing and sample conditions.

Cameras are selected differently: consistency of viewpoint, lighting, scale and image storage matters more than unnecessary resolution.

## Test before approval

A candidate sensor should be tested for:

- stable communication;
- sensible results in known conditions;
- agreement with direct observation or a reference;
- behaviour after restart or disconnection;
- suitability at its real mounting position;
- clear identification in the ERP.

If old data remains visible after disconnection, the system must mark it as stale rather than current.

## Record the decision

The ERP or equipment register should preserve the model, serial number, role, location, calibration state, purchase date, supplier and reason for selection. This allows future contributors to understand both the device and the decision behind it.

## Related documents

- [Measurement strategy](02_measurement-strategy.md)
- [Connectors and cabling](07_connectors-and-cabling.md)
- [Sensor network](09_sensor-network.md)
