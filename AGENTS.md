# AI Agents & Automation Specification — HealMe

HealMe leverages specialized AI agent workflows powered by **Google Gemini API** via Laravel AI to automate symptom interpretation, doctor triage, and schedule optimization.

---

## 1. Triage & Summary Agent (Google Gemini)

### Role
Converts unstructured patient symptom notes into structured medical summaries using `gemini-1.5-pro` / `gemini-1.5-flash`.

### Input
Raw free-form text input submitted by the patient during appointment booking.

### System Prompt Directive
```text
You are a certified clinical triage assistant AI.
Analyze the patient's self-reported complaints, symptoms, and medical history.
Produce a structured JSON output with the following schema:
1. chief_complaint (string)
2. duration (string)
3. recommended_specialties (array of strings)
4. urgency_level (Low | Medium | High | Emergency)
5. clinical_summary (string)

Do NOT offer medical diagnosis to the patient. Maintain a neutral clinical tone.
```

### Output Schema
```json
{
  "chief_complaint": "Persistent migraines with aura and light sensitivity",
  "duration": "4 days",
  "recommended_specialties": ["Neurology", "General Practice"],
  "urgency_level": "Medium",
  "clinical_summary": "30yo patient reporting 4-day history of throbbing unilateral headache accompanied by photophobia."
}
```

---

## 2. Doctor Matching Engine

### Architecture
1. **Gemini Extraction Layer:** Parses intake text and outputs target medical specialties.
2. **Filtering Layer:** Queries active doctors matching the extracted specialties.
3. **Availability Layer:** Filters doctors who have open slots within the next year.
4. **Scoring Algorithm:**
   $$\text{Score} = (\text{Specialty Match} \times 0.5) + (\text{Doctor Rating} \times 0.3) + (\text{Availability Proximity} \times 0.2)$$
5. **Output:** Top 3–5 matched doctor profiles returned to patient UI.

---

## 3. External Integration Agents

### Google Meet & Calendar Integration
- Meet links are created by a **shared app Google account** (`GOOGLE_MEET_REFRESH_TOKEN`), not by each doctor.
- On approval, `GoogleMeetLinkGenerator` creates an open Meet space (anyone with the link can join); Calendar Meet is the fallback.
- `GOOGLE_MEET_DRIVER=fake` remains available for tests/local stubs.

### Telegram Notification Bot
- Links patient/doctor user accounts via deep-linking tokens.
- Sends real-time appointment updates, meeting links, and reminder alerts.
