# System Architecture Document (SAD) — HealMe

## 1. Context & Business Goals
HealMe modernizes patient intake by using Google Gemini AI NLP to interpret patient complaints, auto-summarize intake notes, and match patients with specialized healthcare providers.

## 2. Architectural Pattern: Domain-Driven Design (DDD)
The backend follows DDD principles to separate business logic from technical infrastructure.

```
+-----------------------------------------------------------------------+
|                             HTTP Layer                                |
|   Controllers  -->  FormRequests  -->  API Resources / Responses     |
+----------------------------------+------------------------------------+
                                   | DTOs
                                   v
+-----------------------------------------------------------------------+
|                            Domain Layer                               |
|   Entities / Models  <-->  Actions / Services  <--> Domain Events     |
+----------------------------------+------------------------------------+
                                   | Contracts / Interfaces
                                   v
+-----------------------------------------------------------------------+
|                        Infrastructure Layer                           |
|   Repositories (MySQL) | Gemini AI Service | Google & Telegram APIs   |
+-----------------------------------------------------------------------+
```

### Bounded Contexts / Domains
1. **Users Domain:** Authentication (Google OAuth), roles (SuperAdmin, Doctor, Patient), session management.
2. **Doctors Domain:** Profiles, medical degrees, verification status, specializations, recurring slot management.
3. **Patients Domain:** Medical history, personal attributes, linked Telegram accounts.
4. **Appointments Domain:** Intake analysis, doctor matching, slot reservation, Google Meet generation, notification dispatch.
5. **EHR Domain (Electronic Health Record):** Chart entries, secure access control policies, diagnostic notes.
6. **AI Domain:** Google Gemini API integration via Laravel AI SDK, symptom extraction, embedding/ranking matching logic.

## 3. Data Flow & Sequence Diagram
```
[Patient] ---> Submit Symptoms (Text) ---> [API / App]
                                               |
                                               v
                                      [Laravel AI / Gemini Domain]
                                     (Summarize & Extract Tags)
                                               |
                                               v
                                   [Doctor Matching Engine]
                                (Queries Top 3-5 Doctors)
                                               |
[Patient] <--- Selects Doctor & Slot <----------+
    |
    +---> Confirms Booking
               |
               +---> [Google Meet Spaces API (OPEN)] --> Public Meet link
                         (Calendar Meet fallback if Spaces unavailable)
               +---> [Mail Service]   --> Sends Email + ICS
               +---> [Telegram Bot]   --> Dispatches Telegram Alert
               +---> [EHR Domain]     --> Grants Chart Access to Doctor
```

## 4. Local & Production Infrastructure
- **Local Infrastructure:** Docker Compose / Laravel Sail running PHP 8.3, MySQL 8.0, Redis, and Mailpit.
- **Production Infrastructure (Free-Tier Strategy):**
  - **Frontend SPA:** Vercel / Netlify
  - **Backend API:** Render / Fly.io (Containerized Laravel app)
  - **Database:** Aiven for MySQL
  - **Caching & Queues:** Redis (Upstash Free Tier)
  - **Storage:** Cloudflare R2
