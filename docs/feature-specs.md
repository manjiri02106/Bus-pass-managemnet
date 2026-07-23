# Feature Specifications — Bus Pass Management System
## Branch: `feature/minimal-surface` | Version: `v1.1-feature-only`

---

## Feature Matrix

| ID  | Feature                   | Trigger                               | Outcome                                    | Status Transitions                              |
|:----|:--------------------------|:--------------------------------------|:-------------------------------------------|:------------------------------------------------|
| 4.1 | Verify Applications       | Officer opens application             | Completeness + eligibility checks run      | `submitted` → `under_verification`              |
| 4.2 | Document Verification     | Officer toggles document status       | Files validated against size/format rules  | Any state → `under_verification` on flag        |
| 4.3 | Route Validation          | On application review                 | Distance rule checked; dept determined     | Routing dept column updated, log inserted       |
| 4.4 | Approve/Reject            | Officer clicks Approve or Reject      | Decision + pass recorded or reason logged  | `under_verification` → `approved` / `rejected`  |
| 4.5 | Request Corrections       | Officer flags fields                  | Correction tasks created; workflow paused  | Any state → `correction_required` (paused)      |

---

## 4.1 Verify Applications

**Purpose**: Ensure submitted applications are complete and the student is eligible before further processing.

**Automated Checks** (via `VerifyApplicationsService`):
- All profile fields present: `prn_number`, `roll_number`, `department`, `class`, `mobile`, `address`
- PRN format valid: must match `/^[A-Z]{3}\d{4,10}$/i`
- Mobile number: numeric, 10–15 digits
- Email format: RFC 5321 compliant

**Manual Checklist** (officer):
- Profile completeness confirmed
- Documents reviewed
- Route matches residential area

---

## 4.2 Document Verification

**Purpose**: Validate authenticity of uploaded documents against predefined system standards.

**Automated Checks** (via `DocumentVerificationService`):
- File existence on disk
- File extension in `ALLOWED_DOC_EXTENSIONS` = `['png','jpg','jpeg','pdf']`
- File size ≤ `MAX_DOC_SIZE_BYTES` = 2 MB

**Manual Review**:
- Officer toggles each document `Verified` or `Invalid`
- Comments recorded alongside status in `document_verifications` table

**Trigger for 4.5**: If any document is flagged `invalid`, officer may open a Correction Request.

---

## 4.3 Route Validation

**Purpose**: Ensure application is routed to the correct department based on workflow rules.

**Distance Rule** (via `RouteValidationService`):
- Distance ≤ `MAX_ROUTE_DISTANCE` (50 km) → `Transport Dept`
- Distance > 50 km → `Admin Exception` (exception approval required)

**Routing Dispatcher**:
- Available queues: `Transport Dept`, `Academic HOD`, `Admin Exception`
- Every dispatch logs to `route_validation_logs` with notes

---

## 4.4 Approve / Reject Applications

**Purpose**: Enable authorized officers to approve or reject applications, recording decision and reason.

**Approve** (via `ApproveRejectService::approveApplication`):
- Requires: `validity_duration` (1, 6, or 12 months), optional notes
- Generates pass number: `BP-YYYY-XXXXXX`
- Creates record in `passes` with QR code content
- Sets application `status = 'approved'`

**Reject** (via `ApproveRejectService::rejectApplication`):
- Requires: non-empty `rejection_reason`
- Creates record in `decisions` with `decision = 'reject'`
- Sets application `status = 'rejected'`

**Guard**: Both actions are blocked if application is already `approved` or `rejected`.

---

## 4.5 Request Corrections

**Purpose**: Allow officers to request corrections from students when errors are identified. Pause routing and decision until resolved.

**Create Correction** (via `RequestCorrectionsService::createCorrectionRequests`):
- Accepts map of `field_name → instruction`
- Valid fields: `address`, `college_id_doc`, `photograph_doc`, `address_proof_doc`
- Creates rows in `correction_requests` with `status = 'pending'`
- Sets application `status = 'correction_required'` (blocks routing + approval)

**Resolve Correction** (via `RequestCorrectionsService::resolveFieldCorrection`):
- Called when student re-uploads a file or corrects a text field
- Marks individual correction `status = 'resolved'`
- When all pending corrections resolved → application returns to `status = 'under_verification'`
