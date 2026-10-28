# Engineering Principles

## Purpose

Engineering principles define the general rules used to turn understanding into practical systems.

They form the bridge between the educational knowledge presented in the book and the actual engineering of equipment, infrastructure, monitoring systems, automation, and operations.

Engineering principles do not describe one specific machine or installation. They describe how engineering decisions should be approached.

## From Understanding to Engineering

The book explains how biological, physical, and chemical processes can be understood.

Engineering begins when that understanding is used to answer a practical question:

> How can the desired biological process be supported, measured, controlled, protected, and reproduced?

The general engineering relationship is:

```text
Understanding
    |
    v
Engineering question
    |
    v
Design principle
    |
    v
System design
    |
    v
Measurement
    |
    v
Validation
    |
    v
Improvement
```

## General Engineering Principles

### Understand Before Designing

A system should be designed from an understanding of the process it is intended to support.

Engineering should therefore begin with the biological, physical, chemical, or operational question rather than with a preferred technology.

### Measure What Matters

Measurements should be connected to a meaningful process or decision.

A sensor is useful when its measurement helps explain a process, detect a change, validate an assumption, or support a decision.

### Separate Observation From Interpretation

A measurement is an observation. An interpretation is an explanation of that observation.

Engineering systems should preserve this distinction so that assumptions are not accidentally treated as measured facts.

### Design for Verification

Important engineering behaviour should be observable and testable.

Where possible, systems should provide a way to determine whether the intended result actually occurred.

## Purpose

Engineering Principles define the general rules used to transform understanding into practical systems.

They connect the accessible knowledge developed in the book with the concrete engineering documentation in the project.

## Relationship to the Educational Book



Its purpose is to build understanding by connecting observations, explanations, questions, and further explanations.
The educational book provides an accessible pathway for understanding biological, physical, chemical, and preservation processes.

Engineering Principles begin where this understanding becomes a practical design question.

The book may explain what happens when temperature changes at a molecular or cellular level. An Engineering Principle asks what that understanding means when designing a system that must create, maintain, measure, or control that temperature.

### Design for the Real Process

Engineering should support the real biological or physical process rather than forcing the process to fit the technology.

The design should consider the conditions, variation, limitations, and interactions that actually occur in the system.

### Keep Systems Understandable

A system should remain understandable to the people who operate, maintain, test, and improve it.

Complexity should be introduced only when it provides a meaningful benefit.

### Design for Failure

Engineering systems should assume that components, measurements, power, communication, or biological conditions can fail.

Important functions should therefore have appropriate safeguards, monitoring, recovery procedures, or safe failure behaviour.

### Prefer Reversible Decisions

When uncertainty is high, decisions should preferably be made in a way that allows testing, modification, or reversal.

This reduces the cost of learning from incorrect assumptions.

### Validate Before Scaling

A system should be tested at an appropriate scale before major expansion or investment.

A successful small-scale experiment provides evidence for the next step, but does not automatically prove large-scale performance.

### Document Assumptions

Engineering decisions often depend on assumptions about biological behaviour, environmental conditions, equipment performance, costs, or future use.

Important assumptions should be identified explicitly so that they can later be tested or replaced by evidence.

### Document Reasoning

Important engineering decisions should preserve the reasoning behind the decision, not only the final result.

This includes relevant evidence, assumptions, limitations, alternatives, and reasons for selecting the chosen approach.

## Relationship to Engineering Documentation

Engineering Principles are general rules. They are not specifications for the Microgreens ERP.

The documents in 03_engineering apply these principles to specific engineering domains.

These domains include monitoring, measurement, sensors, rack architecture, connectors, power distribution, sensor networks, climate control, lighting, water systems, experimental inputs, freeze-drying, energy, maintenance, and future technologies.

The relationship is therefore:

```text
Educational Book
       |
       v
Understanding How Things Work
       |
       v
Engineering Principles
       |
       v
Engineering Documentation
       |
       v
Actual Systems and Specifications
       |
       v
ERP / Hardware / Operations
```
