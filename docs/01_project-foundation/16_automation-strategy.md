$path = "docs/01_project-foundation/16_automation-strategy.md"

$document = @'
# Automation Strategy

## Purpose

This document defines when and how automation should be used within the Microgreens ERP project.

It establishes the principles, boundaries and responsibilities required for automation to strengthen biological understanding, safe operation and continuous improvement.

Technical implementation is documented separately in the Engineering Documentation.

## Strategic Role

Automation exists to support meaningful work.

It should improve consistency, quality, safety, efficiency, sustainability or understanding.

It should not be introduced only to reduce human involvement or because a technology is available.

The objective is not maximum automation.

The objective is the appropriate level of automation for each process.

## Understanding Before Automation

A process should be observed and understood before it is automated.

Manual operation reveals biological responses, practical variation, failure modes and exceptions that may not be visible in advance.

Measurements should first prove that they are reliable, correctly located and meaningful.

Automation may begin only when the process, evidence and intended outcome are sufficiently understood.

Automation should preserve opportunities for people to learn how the underlying system works.

## Staged Automation

Automation should develop in controlled stages:

1. Operate the process manually and record what happens.
2. Monitor conditions and provide clear warnings or advice.
3. Test assisted control in which a person confirms important actions.
4. Automate limited actions within defined and tested boundaries.
5. Coordinate related systems only after their interactions are understood.
6. Consider predictive or adaptive control only after sufficient evidence and validation.

A process may remain at any stage when further automation provides no meaningful benefit.

Progression should depend on evidence rather than ambition.

## Conditions for Automation

Before a process is automated, the project should be able to answer:

- What problem does the automation solve?
- What biological or operational outcome should improve?
- Which measurements support the decision?
- Are those measurements reliable and current?
- What operating boundaries apply?
- What happens when information is missing or contradictory?
- How can a person intervene?
- How will success and failure be measured?
- Can the system be maintained and understood?
- Does the expected benefit justify the energy, cost and complexity?

If these questions cannot be answered, the process is not ready for automation.

## Human Responsibility

People remain responsible for the purpose, limits and evaluation of automated systems.

Automation may perform repetitive actions.

It must not remove responsibility for biological care, food safety, ethical judgement or important operational decisions.

Operators should be able to understand:

- what the system is observing;
- why an action is proposed or performed;
- which limits apply;
- how to override the system;
- how to return to manual operation;
- where decisions and results are recorded.

Human knowledge should grow alongside automation rather than disappear because of it.

## Safety and Graceful Failure

Every automated function requires a defined failure strategy.

Where relevant, this includes:

- manual override;
- a context-appropriate safe state;
- limits on continuous operation;
- response to missing, stale, invalid or contradictory measurements;
- detection of disconnected or unresponsive equipment;
- protection against leakage, overflow, dry running and uncontrolled switching;
- clear warnings;
- a documented recovery procedure;
- preservation of data during failure.

A safe state does not always mean switching everything off.

It means moving the system towards the least harmful predictable condition for people, plants, food, equipment and the environment.

Automation must not continue on false confidence when its evidence has failed.

## Coordinated Control

Cultivation systems influence one another.

Lighting changes heat.

Watering changes humidity.

Ventilation changes temperature, humidity and carbon dioxide.

Heating, cooling, humidification and dehumidification may work against one another if they are controlled independently.

Coordinated automation therefore requires:

- clear priorities;
- biologically meaningful operating ranges;
- appropriate deadbands;
- defined timing and duration limits;
- awareness of interactions between systems;
- rules for resolving conflicting objectives.

Coordination should only be introduced after individual functions have been validated.

## Transparency and ERP Records

The ERP should preserve the complete history of meaningful automated actions.

Where practical, records should distinguish:

- observation;
- data quality and age;
- interpretation;
- rule or model version;
- decision;
- proposed action;
- command;
- responsible system or person;
- equipment response;
- measured outcome;
- override;
- warning or failure.

Separating these stages makes it possible to understand whether an intervention actually improved the process.

Historical records should not be overwritten.

Changes to automation rules, boundaries and priorities should remain traceable.

## Relationship with Artificial Intelligence

Artificial intelligence and automation have different roles.

AI may analyse information, identify patterns and suggest explanations or actions.

Automation may perform actions only within tested and approved boundaries.

An AI recommendation does not automatically authorise physical action.

AI-supported decisions should remain linked to source data, model version, confidence and human review where appropriate.

Greater autonomy must be earned through evidence, reliability and demonstrated safety.

## Open and Maintainable Systems

Automation should use clear interfaces, documented rules and replaceable components whenever practical.

Systems should remain modular so that one component can be repaired, improved or replaced without rebuilding the whole system.

Open formats and understandable operation reduce unnecessary dependence on one supplier, platform or hidden model.

Complexity should only be introduced when it creates measurable and lasting value.

Manual operation should remain possible when automation is unavailable.

## Measurement and Continuous Improvement

Automation should be evaluated after implementation.

Relevant measures may include:

- product quality;
- biological stability;
- consistency;
- energy use;
- water use;
- material use;
- labour;
- equipment runtime;
- warning frequency;
- failure frequency;
- manual overrides;
- maintenance requirements;
- operational cost.

An automated system is not successful merely because it performs an action.

It is successful when the intended outcome improves without creating unacceptable risks, waste, dependency or complexity.

Unsuccessful trials should also be documented so that weak approaches are not repeated without new evidence.

## Long-Term Direction

The long-term direction is an automation system that strengthens observation, understanding and responsible action throughout the food chain.

Automation should help people recognise change earlier, respond more consistently and learn from every intervention.

As the project grows, automation may support cultivation, preservation, storage, logistics, maintenance and reporting.

Human responsibility, biological understanding and safe operation must remain central at every scale.

## Related Documents

- [Vision](01_vision.md)
- [Mission](02_mission.md)
- [Core Values](03_core-values.md)
- [Thinking Framework](04_thinking-framework.md)
- [Design Philosophy](05_design-philosophy.md)
- [Guiding Principles](08_guiding-principles.md)
- [Long-Term Strategy](10_long-term-strategy.md)
- [Food Chain Strategy](12_food-chain-strategy.md)
- [Safety and Resilience Strategy](13_safety-and-resilience-strategy.md)
- [Sensor Strategy](14_sensor-strategy.md)
- [Data Strategy](17_data-strategy.md)
- [Research Strategy](18_research-strategy.md)
- [AI Strategy](20_ai-strategy.md)
'@

$document = $document -replace "`r?`n", [Environment]::NewLine
$document = $document.TrimEnd("`r", "`n") + [Environment]::NewLine

$utf8WithoutBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($path, $document, $utf8WithoutBom)