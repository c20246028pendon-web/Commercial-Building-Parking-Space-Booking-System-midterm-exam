# Parking domain database design

The raw `parking_raw_slots` dataset mixes driver, zone, slot, ticket, and payment facts in one row. Repeating driver and zone details for every visit creates redundancy; changing a driver's name or a zone's rate would require updating multiple rows. Ticket values such as entry and exit times belong to a single visit, while payment status and fee belong to that visit's payment.

The schema separates those facts into five tables:

| Table | Stored facts | Key relationships |
|---|---|---|
| `drivers` | Driver name and unique vehicle plate | One driver has many parking tickets |
| `parking_zones` | Zone name and current hourly rate | One zone has many parking slots |
| `parking_slots` | Slot code and current availability | One slot belongs to a zone and has many historical tickets |
| `parking_tickets` | Driver, slot, entry/exit, rounded hours, and the rate agreed for this visit | Each ticket belongs to one driver and one slot |
| `payments` | Total fee and payment status for a ticket | One payment per ticket |

Non-key attributes describe their table's key, the unique vehicle plate determines the driver's name, and a zone determines its current rate. Ticket `hourly_rate` is retained as a per-visit snapshot so later rate changes do not rewrite historical charges. The calculated fee is stored with its payment record; it is computed at checkout as `total_hours * hourly_rate`.

The `ParkingService` transaction reserves a vacant slot when opening a ticket. On checkout it rounds elapsed seconds up to whole hours, creates a pending payment, and marks the slot vacant. `ParkingSlot` and `ParkingTicket` endpoints are authenticated and return JSON, including their required create-form dependency data.
