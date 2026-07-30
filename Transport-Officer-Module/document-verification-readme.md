# Document Verification Feature

## Overview
This document describes the Document Verification feature in the Bus Pass Management System. It covers the admin workflow, implementation details, validation rules, and API behavior for verifying uploaded student documents.

## Purpose
Document Verification ensures that uploaded documents are reviewed by an administrator before moving the application forward in the bus pass approval workflow.

## User Flow
1. Admin navigates to `admin/document_verification.php`.
2. The page lists applications with pending or incomplete document verification.
3. Admin clicks `Review Documents` for a specific application.
4. The feature displays each uploaded document with:
   - preview support for image/PDF documents
   - verification status badge
   - notes field for comments
   - `Verify` and `Reject` buttons
5. Admin selects `Verify` or `Reject` for each document.
6. If all documents are verified, the application status transitions to `docs_verified`.

## Key Files
- `admin/document_verification.php` — UI for listing applications and reviewing documents.
- `api/verify_documents.php` — API endpoint used to mark documents as `verified` or `rejected`.
- `verify_application.php` — contains application-level workflow logic, including document status checks.
- `docs/feature-specs.md` — feature specification referenced by this implementation.

## Validation and Rules
- Document status can be set to:
  - `verified`
  - `rejected`
- Rejecting a document requires admin notes.
- Each status update is recorded with:
  - `verified_by`
  - `verified_at`
  - `admin_notes`
- When all documents for an application are verified, the system updates the application to `docs_verified` if the current status is one of `pending`, `under_review`, or `correction_requested`.

## Data Model
The feature relies on the following tables/columns:
- `documents`
  - `id`
  - `application_id`
  - `doc_label`
  - `file_name`
  - `mime_type`
  - `file_size`
  - `status`
  - `admin_notes`
  - `verified_by`
  - `verified_at`
- `applications`
  - `id`
  - `status`
  - `application_number`

## API Contract
### POST `/api/verify_documents.php`
Request body:
- `doc_id` (integer)
- `application_id` (integer)
- `action` (`verified` or `rejected`)
- `notes` (required when rejecting)
- `csrf_token`

Successful response:
- `success: true`
- `message: 'Document status updated successfully.'`

Failure response:
- `success: false`
- `message` with the reason for failure

## Notes
- Document previews are supported for image and PDF mime types.
- Application list view displays progress, counts for verified/rejected/pending documents, and a review button.
- The feature is part of the broader approval workflow that includes application verification, route validation, approvals, and correction requests.
