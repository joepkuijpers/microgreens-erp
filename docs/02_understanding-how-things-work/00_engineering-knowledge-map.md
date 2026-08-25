# Understanding How Things Work

> Knowledge synthesis between the Preservation Energy Architecture book and engineering implementation.

## Purpose

This directory forms the knowledge layer between the educational book and practical engineering documentation.

The book introduces fundamental biological, physical, chemical, and preservation concepts in an accessible progressive way.

The Engineering Extensions connect concepts from multiple chapters and explore the engineering questions that arise from those relationships.

This layer therefore answers a different question from both the book and the engineering documentation.

The book asks:

> How can the reader understand what is happening?

The Engineering Extensions ask:

> What new questions appear when these pieces of knowledge are connected?

Engineering Principles ask:

> How should those questions influence the way we design systems?

Engineering documentation asks:

> How do we implement and operate those systems?

---

# Knowledge Architecture

```text
Preservation Energy Architecture
        |
        | Fundamental understanding
        v
Book Chapters 01-28
        |
        | Connections between concepts
        v
Engineering Extensions
        |
        | General engineering reasoning
        v
Engineering Principles
        |
        | Practical application
        v
03 Engineering
        |
        | Implementation
        v
ERP / Hardware / Operations
```

---

# Layer 1 - The Book

The book is an educational foundation.

It is deliberately written at an accessible level so that a reader without university-level biological, chemical, physical, or engineering education can follow the reasoning.

Its purpose is not to function as a scientific reference textbook.

It does not attempt to present every established fact in a field.

Instead, it uses a progressive learning structure:

```text
Observation
    |
    v
Explanation
    |
    v
Question
    |
    v
New question
    |
    v
Further explanation
    |
    v
Deeper understanding
```

The reader is guided through relationships rather than presented with isolated information.

The accessibility of the book is intentional.

A reader with advanced university-level knowledge may already understand many of the underlying concepts and therefore may not need this explanatory pathway.

The book nevertheless provides a common conceptual language for the project.

---

# Layer 2 - Engineering Extensions

Engineering Extensions form the bridge between educational understanding and engineering reasoning.

They are not additional book chapters.

They are also not implementation documents.

Their purpose is to connect knowledge from different chapters and investigate what becomes visible when those concepts are considered together.

A single book chapter may explain one concept.

An Engineering Extension may combine several concepts to investigate a larger system.

For example, understanding cellular structure alone does not explain freeze drying.

Freeze drying requires the relationship between cellular structure, water, temperature, pressure, phase change, energy transfer, mass transfer, and preservation.

The Engineering Extension therefore asks what happens when these separate areas of understanding are connected.

The resulting question may then lead to an engineering principle, a research question, an experiment, or a practical design requirement.

The general progression is:

```text
Book knowledge
      |
      v
Connection between concepts
      |
      v
New engineering question
      |
      v
Engineering Extension
      |
      v
Engineering Principle
      |
      v
Design / Experiment / Implementation
```

## What an Engineering Extension Contains

An Engineering Extension may contain:

- connections between multiple book chapters;
- biological processes;
- physical processes;
- chemical processes;
- energy relationships;
- preservation mechanisms;
- engineering questions;
- observations;
- assumptions;
- hypotheses;
- research questions;
- experimental considerations;
- engineering implications.

The Engineering Extension must distinguish between what is known, what is assumed, what is hypothesised, and what remains unknown.

It should therefore never convert an explanatory model automatically into an established fact.

## Relationship to the Book

The book remains the educational starting point.

Engineering Extensions may refer to several chapters simultaneously.

They should not unnecessarily repeat the educational explanation already provided by the book.

Instead, they should identify the relationship between concepts and develop the question that emerges from that relationship.

This prevents the knowledge system from becoming a collection of duplicate explanations.

---

# Layer 3 - Engineering Principles

Engineering Principles translate understanding into general rules for responsible engineering.

They are derived from the knowledge and questions developed in the educational and Engineering Extension layers.

An Engineering Principle should therefore be more general than a specific component, machine, sensor, process, or software implementation.

The purpose of a principle is to guide decisions when the exact implementation is not yet known.

## Examples of Engineering Principles

### Start with the biological objective

Engineering decisions should begin with the biological or preservation objective rather than with the available technology.

### Understand the complete system

Individual components should be understood as parts of larger biological, physical, energy, information, and operational systems.

### Distinguish measurement from interpretation

A measured value is evidence of an observation. Interpretation is a separate layer of reasoning and should not be presented as the measurement itself.

### Treat energy as a system resource

Energy consumption should be considered together with biological output, preservation quality, resource use, reliability, and system purpose.

### Prefer system-level optimisation

Optimising one component should not be considered successful if it reduces the performance or resilience of the complete system.

### Design for measurement

Important processes should be measurable whenever practical so that assumptions can be tested and decisions can be validated.

### Separate fact, assumption, hypothesis, and intention

Engineering documentation should clearly distinguish demonstrated behaviour from assumptions, hypotheses, planned capabilities, and future possibilities.

### Preserve human understanding

Automation and Artificial Intelligence should strengthen human understanding and capability rather than make the underlying system impossible for people to understand.

### Design for validation

A design should make it possible to determine whether its intended behaviour has actually been achieved.

### Design for resilience

Systems should remain understandable, recoverable, maintainable, and safe when individual components fail or conditions change.

## Principle versus Implementation

An Engineering Principle should not become a hidden implementation specification.

For example:

Principle:

> Important environmental conditions should be measurable.

Implementation:

> Measure temperature and relative humidity using a selected sensor at a defined sampling interval.

The first belongs to the principles layer.

The second belongs to engineering implementation.

This distinction prevents the knowledge architecture from mixing philosophy, scientific understanding, engineering reasoning, and implementation.
