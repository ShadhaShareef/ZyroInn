## 1. Project Context



- **Client:** small property, currently 2 rooms, operating without software — requirements

  currently handled via staff notes, verbal explanation to guests, and manual lists sent to

  customers

- **Current status:** an application is already live in trial (7 days in as of the last update),

  being used and tested; not yet a sales conversation; a demo to showcase to the resort/hotel has

  been suggested

- **Known active bug:** agency and commission details occasionally mismatch/mix up in the current

  trial build — being worked on, worth re-checking once the frontend touches that data

- **Long-term intent:** the requirements report frames this as a scalable, multi-property

  platform (marketplace-capable, referencing OYO/Booking.com-style feature sets) even though the

  only confirmed real client today is a 2-room property — build the data model to scale, but

  don't gold-plate the UI with marketplace-only screens before Phase 1 is solid (see Section 8)



---



## 2. Source Map (what came from where)



| Source | Covers |

|---|---|

| Original requirements report (client-provided PDF) | 8 user roles, per-role modules/features, problem/solution scenarios |

| ZyrOps brand guidelines (client-provided) | Colors, typography, application focus notes |

| Direct trial-client feedback (site visit / call notes) | Real-world gaps: adaptive amenities, offline-to-online feature listing, room categorization, filters |

| Referred competitor sites (OYO, eZee Absolute, Hotelogix, Booking.com, Hotel.com, Cloudsbed) | Extended feature backlog — explicitly future/marketplace scope, not immediate build targets |

| This document, Section 9 | Production-grade non-functional standards — recommended, not client-stated; flagged as such |



---



## 3. Design & Brand Requirements



- **Primary accent:** ZyrOps Purple `#6C2BD9`

- **Primary typography color:** Black `#0F1115`

- **Primary background:** White `#FFFFFF`

- **Headings:** Poppins, Bold — applied strictly across primary product heroes, section headings,

  dashboard titles, presentation layouts

- **Body:** Inter, Regular — same application scope as above

- **Minimum body text size:** 9.5pt, maintained across code and documentation-style designs for

  readability

- **Stated application focus:** headings must stand out crisp and clean; typographic contrast

  must be maintained across team layouts

- **Gap flagged in the earlier frontend plan, still open:** the brand guide only defines 3 raw

  colors — no semantic colors (success/warning/error/info), no tint/shade ramp, no gray scale.

  These were left to be derived (see the agent build prompts doc) — confirm against current work

  whether a consistent derived palette actually exists yet or whether colors have drifted per

  screen.



---



## 4. User Roles



1. **Property Owner** — oversees revenue, rooms, staff

2. **Front Office Staff** — check-ins, check-outs, guest requests

3. **Guest / Customer** — books rooms, makes requests, checks out

4. **Housekeeping** — room cleanliness & status updates

5. **F&B Manager** — dining, room service, bar orders

6. **Maintenance Staff** — fixes issues, tracks room conditions

7. **Security Officer** — safety, access, incidents

8. **Finance / Accounts** — payments, invoices, payroll



---



## 5. Functional Requirements by Role



Use these as literal checklists against the current build.



### 5.1 Property Owner

- [ ] Real-time occupancy & revenue dashboard

- [ ] Room rate & inventory management

- [ ] Staff schedules & role access control

- [ ] Expense tracking & profit reports

- [ ] Multi-property view (if applicable)

- [ ] Automated daily summary reports

- [ ] Alert for low occupancy / overbooking

- [ ] Owner-only secure login

- [ ] Export reports (PDF, Excel)



### 5.2 Front Office Staff

**Check-In / Check-Out**

- [ ] Instant room assignment

- [ ] ID & document capture

- [ ] Payment at check-in

- [ ] Express check-out option



**Reservations**

- [ ] View, edit, cancel bookings

- [ ] Waitlist & upgrade options

- [ ] Group booking support



**Guest Management**

- [ ] Guest history & preferences

- [ ] Special requests tracker

- [ ] Complaint logging & resolution



**Advanced**

- [ ] CCTV feed access (assigned zones)

- [ ] Key card encoding

- [ ] Shift handover notes



### 5.3 Guest / Customer

**Booking**

- [ ] Search by date, room type

- [ ] Instant confirmation

- [ ] Secure payment



**Pre-Arrival**

- [ ] Online check-in option

- [ ] Room preference selection

- [ ] Special request form



**During Stay**

- [ ] Room service ordering

- [ ] Spa & activity booking

- [ ] Digital concierge



**Check-Out**

- [ ] E-bill via WhatsApp / Email

- [ ] Feedback form

- [ ] Loyalty points update



### 5.4 Housekeeping

- [ ] Room status board (clean / dirty / inspect)

- [ ] Task assignment by floor

- [ ] Mobile app / mobile-friendly for real-time updates

- [ ] Lost & found logging

- [ ] Linen & amenity inventory



### 5.5 Maintenance

- [ ] Issue reporting from any staff role

- [ ] Priority tagging (urgent / normal)

- [ ] Work order tracking

- [ ] Preventive maintenance schedule

- [ ] Vendor contact directory



### 5.6 F&B Manager

- [ ] Restaurant POS integration

- [ ] Room service order routing

- [ ] Menu & pricing updates

- [ ] Stock & waste tracking



### 5.7 Security Officer

- [ ] CCTV live feed access

- [ ] Visitor log management

- [ ] Incident reporting

- [ ] Emergency alert system



### 5.8 Finance / Accounts

- [ ] Invoice generation & tracking

- [ ] Payroll management

- [ ] Tax reports & compliance

- [ ] Audit trail for all transactions



---



## 6. Client-Specific Requirements (from real trial-client feedback)



These came directly from a site conversation with the actual client and carry more real-world

weight than the general requirements report — they describe the gap between how the client

currently operates (manual/offline) and what the software needs to cover.



- [ ] **Online feature listing** — for any service the property offers online, it should be

  listed and browsable in the app (not just implied)

- [ ] **Offline-to-formal-list workflow** — currently, when a customer asks staff about

  requirements/features in person or by phone, staff manually lists them and sends that list to

  the customer. The system should be able to generate/send that same kind of feature list

  digitally (e.g. shareable property summary) rather than staff doing this by hand

- [ ] **Room categorization** — AC / Non-AC, indoor games / outdoor games, and similar category

  splits need to be modeled explicitly, not lumped into a generic "amenities" blob

- [ ] **Named property-specific features** — things like "Pillar Cottages," "24hr hot water,"

  and similar distinct facility names need to be representable, not forced into a generic

  checkbox list that loses the property's actual character

- [ ] **Adaptive/optional amenity system (explicitly requested by client)** — not every property

  has a pool, a bathtub, etc. The system must work equally well for a property that has these

  and one that doesn't, and must let guests filter for OR against any given amenity. This was

  called out specifically as a sorting/filtering requirement, not just a display requirement —

  see Section 3 of the earlier frontend plan for the full data-model implication

- [ ] **Search filters aligned to real guest interests** — beyond basic date/room-type search

- [ ] **Room facility detail** — bed count, number of people accommodated, explicitly required

  fields

- [ ] **Additional service listings** — gym, play areas, turfs, jacuzzi called out by name as

  examples of services that need to be supported

- [ ] **Support for varied property types** — client explicitly suggested studying multiple

  property types in person (resorts, homestays, lodges, low-budget properties, luxury

  properties, different area types) because their booking flows differ meaningfully and "calling

  and asking" is less effective than observing the real flow. This is a research action item as

  much as a feature requirement — worth confirming whether this research has actually happened

  yet and whether it changed any assumptions in the data model.



**Known issue to re-verify against frontend work:**

- [ ] Agency and commission detail mismatch bug (reported in the live trial) — if the frontend

  touches agency/commission data entry or display anywhere, confirm this specific bug isn't

  being reintroduced or masked



---



## 7. Scenario-Based Requirements



The requirements report frames several operational problems the system needs to solve — these

are useful as end-to-end test cases, not just feature line items.



| Problem | Required Solution |

|---|---|

| Overbooking | System blocks double bookings; auto-waitlist + upgrade suggestion |

| Slow check-in | Online pre-check-in option; ID scan + auto-fill |

| Missed room service | Orders route to kitchen + staff app; auto-alert if delayed |

| Maintenance delay | Staff logs issue → auto-assign; owner notified if unresolved |



- [ ] Each of these four scenarios should be walkable end-to-end in the current build as a test,

  not just individually-present features



---



## 8. Extended Feature Backlog (Future / Marketplace Scope — Not Immediate)



Pulled from competitor research (OYO, eZee Absolute, Hotelogix, Booking.com, Hotel.com,

Cloudsbed) and included in the original report as aspirational/marketplace-scale scope. **These

are reference material for where the platform could go, not Phase 1 requirements.** Use this

list to make sure Phase 1 architecture doesn't paint the project into a corner, not to build

against right now.



**Customer / Guest**

Saved hotels/wishlist, recently viewed properties, map-based search, nearby landmarks, flexible

date search, price alerts, property comparison, personalized recommendations, travel history,

rewards redemption, promo codes, multi-currency, mobile wallet payments, contactless check-in/

out, unified guest messaging (WhatsApp/SMS/email), auto upselling/add-on recommendations



**Search & Discovery**

Smart search suggestions, destination-based browsing, similar-property recommendations, trending

destinations, AI recommendation engine, search ranking, traveler-type filters (family/couple/

business/solo)



**Property Partner**

Self-registration portal, approval/verification workflow, Central Reservation System,

centralized distribution management, occupancy prediction, unified guest CRM, workflow

automation, property performance dashboard, third-party app marketplace



**Revenue & Pricing**

Revenue Management System, AI pricing recommendations, competitor rate intelligence, yield

management, revenue KPI dashboards



**Marketing & CRM**

Guest segmentation, email/WhatsApp marketing campaigns, automated promotions, loyalty campaigns,

SEO tools, AI marketing insights, affiliate/referral programs, reputation management



**Reviews & Reputation**

Verified guest reviews, review moderation, review monitoring, guest sentiment analysis,

automated review collection



**Admin & SaaS Management**

Website builder, SaaS subscription management, commission management, global analytics, API

management, audit/compliance monitoring, AI business insights, centralized notifications,

customer support ticketing, dispute resolution, fraud detection, content moderation



**Integrations**

Open API ecosystem, Google Maps, Google Hotel Ads, meta search, accounting software, CRM, smart

locks, third-party marketplaces



**Trust & Security**

Guest identity verification, property verification badges, advanced fraud prevention



**Travel Ecosystem Expansion**

Flight booking, car rentals, airport taxi booking, attractions/activities booking, vacation

rentals, apartments, villas, resorts marketplace, hostels marketplace



---



## 9. Production-Grade Standards (Recommended — Not Explicitly Client-Stated)



The client and requirements report did not spell these out, but a system handling real payments,

real guest ID documents, and real staff operations needs them regardless. Flagging these clearly

as standards being layered on, so nothing here gets mistaken for a client request that was

missed.



**Security**

- [ ] All database access via parameterized queries (PDO prepared statements) — no string-built

  SQL anywhere

- [ ] Passwords hashed (`password_hash`/`password_verify`), never stored plain

- [ ] CSRF protection on every state-changing form

- [ ] Server-side role checks on every protected page — client-side/JS-only gating is not

  sufficient

- [ ] Guest ID documents and payment data handled with explicit access controls and, ideally,

  encryption at rest — this data is sensitive by nature

- [ ] Session handling with reasonable timeout and secure cookie flags



**Performance**

- [ ] Mobile pages usable on average 3G/4G conditions, not just on office wifi — staff app

  especially, per the mobile-first/offline-tolerance requirement already noted

- [ ] Images optimized/responsive, not full-resolution uploads served directly



**Accessibility**

- [ ] Color contrast meets WCAG AA at minimum, especially for status badges (room status, task

  priority) where color alone shouldn't be the only signal — pair color with text/icon

- [ ] Keyboard-navigable forms, visible focus states

- [ ] Form errors announced clearly, not just color-coded



**Data Integrity & Operations**

- [ ] Database backups in place before this goes anywhere near real guest data

- [ ] Audit trail on financial transactions (already a stated requirement in Section 5.8 — worth

  reinforcing it actually gets built, not just listed)

- [ ] Error logging that a developer can actually use to debug production issues, not just

  silent failures



**Compliance**

- [ ] Basic data privacy handling for guest personal data and ID documents (retention policy,

  who can access it) — even without a specific regulatory framework named, this is standard

  practice for any system storing ID documents and payment info



**Browser/Device Support**

- [ ] Confirm and document the actual supported browser/device range, since "mobile-first" was

  specified but no explicit minimum device/OS version was — recommend picking a floor (e.g. last

  2 major iOS/Android versions, evergreen desktop browsers) and testing against it deliberately