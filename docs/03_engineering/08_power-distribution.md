# Power distribution and efficiency

## Why power design matters

Reliable power protects people, plants, equipment and data. Efficiency also matters, but electrical safety and safe failure come first.

Detailed mains design and installation must follow applicable rules and should be performed or reviewed by a qualified person.

## Start with the loads

List every load before selecting distribution equipment:

- lighting;
- Raspberry Pi and network equipment;
- sensors and low-voltage electronics;
- fans, pumps and valves;
- climate equipment;
- future freeze-drying equipment.

Record normal power, starting current, operating schedule and consequence of failure. This reveals which loads may share a supply and which need separation.

## Power layers

The architecture separates:

1. building supply;
2. rack-level mains distribution;
3. low-voltage conversion and distribution;
4. control and sensor power;
5. justified backup power.

Water and electricity need clearly separated zones. Isolation points should be reachable and labelled.

## Functional separation

Lighting, control electronics and moving equipment should not depend on one undocumented chain. A single failed power supply should not hide the state of the whole system.

The Raspberry Pi requires a stable supply and controlled recovery after interruption. Sensors may need cleaner power than motors or relays. Switching should use components rated for the actual load.

## Safe failure and recovery

After a power loss, equipment should return to a known state. Pumps and heaters must not restart blindly. The ERP should record the interruption, recovery and any missing measurements.

Backup power is justified for functions that protect data, communication or safe shutdown. It should not be used merely to keep every load running.

## Efficiency

Efficiency begins by avoiding unnecessary demand:

- use daylight where it benefits the crop safely;
- schedule light for biological need;
- reduce idle equipment;
- prevent pumps and fans from running without purpose;
- consider heat produced by lamps and power supplies;
- coordinate high loads where practical.

Device efficiency is only part of the picture. A slightly more efficient lamp can still waste energy when spacing or timing is poor.

## Monitoring energy

Measure enough to make decisions. Useful levels include whole-system consumption, major load groups and energy per production batch. The project should avoid adding a meter to every small device unless the result changes action.

## Growth path

Begin with a documented single-rack supply. Add separated branches and energy monitoring as loads become clearer. Backup, renewable energy and multi-rack management should follow measured need rather than assumption.

## Related documents

- [Energy](15_energy.md)
- [Lighting](11_lighting.md)
- [Climate control](10_climate-control.md)
- [Maintenance](16_maintenance.md)
