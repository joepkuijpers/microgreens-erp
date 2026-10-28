# Monitoring architecture

> **Engineering principle**
> Technology should adapt to biology, not biology to technology.

## Why monitoring exists

Monitoring helps us understand what plants experience and how the production system behaves. It supports daily care, research, traceability and timely action. Its purpose is not to collect as much data as possible, but to preserve useful evidence.

The plants remain the main biological source of information. Sensors, cameras and software can extend human observation, but they cannot replace attention to colour, posture, germination, growth, roots, smell or other living responses.

## What counts as an observation

Observations may come from:

- crops and their biological responses;
- indicator plants;
- growers and other people;
- sensors and equipment;
- cameras;
- laboratory measurements;
- operational records;
- AI-assisted analysis.

An indicator plant may reveal stress before it becomes obvious in the production crop. Its response is evidence, not a diagnosis. It should be compared with the crop, environmental measurements and human judgement.

## From observation to learning

The architecture keeps five steps separate:

1. **Observe** — something is seen, measured or recorded.
2. **Interpret** — the observation receives meaning in context.
3. **Decide** — a person or validated rule selects a response.
4. **Act** — an intervention is made.
5. **Evaluate** — the later plant or system response is reviewed.

This separation prevents a sensor value, an alarm and an action from being treated as the same thing.

## The information flow

Every useful observation should carry enough context to answer:

- What was observed?
- Where and when did it happen?
- Which batch, rack or shelf was involved?
- Which person, instrument or method produced it?
- Was the source calibrated or reviewed?
- Is the value current, uncertain or incomplete?

The ERP stores this context with the observation. Raw evidence should remain available when a corrected value, image classification or calculated result is added later.

## Fixed and portable monitoring

The project combines two forms of monitoring:

- **Fixed monitoring** follows essential conditions continuously.
- **The Portable Biological Laboratory** moves between measurement-ready racks for deeper checks, comparisons and experiments.

This avoids placing expensive instruments on every shelf while still allowing professional investigation.

## Imaging and computer vision

Fixed or portable cameras may support visual monitoring of:

- germination;
- plant spacing and distribution;
- canopy coverage;
- colour changes;
- visible growth;
- uneven or fallen plants;
- visible contamination.

Images should use a consistent viewpoint, distance and lighting when results are compared over time. The ERP links each image to its batch, rack, shelf and capture time.

AI-assisted results must remain linked to the original image and model version. They should include confidence and human-review status.

## Failure must remain visible

A disconnected sensor must not make an old value appear current. Missing, stale, uncertain and conflicting data require clear labels. Monitoring failure should reduce confidence, not create false certainty or uncontrolled automation.

## Growth path

The architecture begins with one Raspberry Pi and one rack. It can later support measurement-ready racks, portable instruments, multiple rack nodes and research across locations without changing the meaning of the stored observations.

## Related documents

- [Measurement strategy](02_measurement-strategy.md)
- [Strategic monitoring](04_strategic-monitoring.md)
- [Portable biological laboratory](05_portable-biological-laboratory.md)
- [Sensor network](09_sensor-network.md)
