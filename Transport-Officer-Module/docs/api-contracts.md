# API Contracts — Bus Pass Management System
## Branch: `feature/minimal-surface` | Version: `v1.1-feature-only`

All endpoints are PHP pages processing `POST` form submissions or `GET` queries.

---

## Service Method Contracts

### `VerifyApplicationsService`

#### `checkCompleteness(array $studentData): array`
| Parameter    | Type    | Description                    |
|:-------------|:--------|:-------------------------------|
| `$studentData` | `array` | Must contain keys: `prn_number`, `roll_number`, `department`, `class`, `mobile`, `address` |

**Returns**: `['valid' => bool, 'errors' => string[]]`

#### `checkEligibility(array $studentData, array $userData): array`
| Parameter    | Type    | Description                              |
|:-------------|:--------|:-----------------------------------------|
| `$studentData` | `array` | Must contain: `prn_number`, `mobile`   |
| `$userData`    | `array` | Must contain: `email`                  |

**Returns**: `['valid' => bool, 'errors' => string[]]`

---

### `DocumentVerificationService`

#### `validateDocumentFile(string $filepath, array $allowedExtensions, int $maxSizeBytes): array`
| Parameter           | Type     | Description                          |
|:--------------------|:---------|:-------------------------------------|
| `$filepath`         | `string` | Absolute path to the document file   |
| `$allowedExtensions`| `array`  | e.g. `['png','jpg','jpeg','pdf']`    |
| `$maxSizeBytes`     | `int`    | e.g. `2097152` (2MB)                 |

**Returns**: `['valid' => bool, 'message' => string]`

#### `recordVerificationStatus(PDO, int $appId, string $docType, string $status, string $comments, int $officerId): bool`
| Parameter   | Type     | Allowed Values                                    |
|:------------|:---------|:--------------------------------------------------|
| `$docType`  | `string` | `college_id`, `photograph`, `address_proof`       |
| `$status`   | `string` | `verified`, `invalid`, `pending`                  |

**Throws**: `InvalidArgumentException` for invalid `$docType` or `$status`

---

### `RouteValidationService`

#### `validateDistance(float $distance, float $maxDistance): bool`
Returns `true` if `$distance <= $maxDistance`, otherwise `false`.

#### `determineRoutingQueue(float $distance, float $maxDistance): string`
Returns `'Transport Dept'` or `'Admin Exception'`.

#### `recordValidationLog(PDO, int $appId, int $routeId, bool $isValid, string $notes): bool`
Inserts into `route_validation_logs`.

#### `dispatchWorkflowRoute(PDO, int $appId, string $newDept, string $comments, int $officerId): bool`
| Parameter  | Allowed Values                                              |
|:-----------|:------------------------------------------------------------|
| `$newDept` | `Transport Dept`, `Academic HOD`, `Admin Exception`        |

**Throws**: `InvalidArgumentException` for disallowed `$newDept`

---

### `ApproveRejectService`

#### `approveApplication(PDO, int $appId, int $validityMonths, string $notes, int $officerId): array`
**Returns**: `['success' => bool, 'pass_number' => string, 'error' => string]`

**Guard**: Returns `success = false` if `status` is already `approved` or `rejected`.

#### `rejectApplication(PDO, int $appId, string $reason, int $officerId): array`
**Returns**: `['success' => bool, 'error' => string]`

**Guard**: Returns `success = false` if `reason` is empty or application already finalized.

---

### `RequestCorrectionsService`

#### `createCorrectionRequests(PDO, int $appId, array $corrections, int $officerId): bool`
| Parameter      | Type    | Description                                                                  |
|:---------------|:--------|:-----------------------------------------------------------------------------|
| `$corrections` | `array` | Assoc map of `field_name => instruction`. Valid fields: `address`, `college_id_doc`, `photograph_doc`, `address_proof_doc`. Empty instructions are skipped. |

**Side effect**: Sets application `status = 'correction_required'` (blocks routing + approval).

#### `resolveFieldCorrection(PDO, int $appId, string $field): bool`
**Side effect**: If all corrections resolved, sets `status = 'under_verification'`.

#### `hasPendingCorrections(PDO, int $appId): bool`
Returns `true` if any `correction_requests` row with `status = 'pending'` exists for the application.

---

## HTTP Form Endpoints (src/ pages)

### `POST verify-application.php?id={appId}`

| `action` value        | Required POST fields                                      | Feature |
|:----------------------|:----------------------------------------------------------|:--------|
| `reroute`             | `routing_dept`, `routing_comments`                        | 4.3     |
| `verify_doc`          | `doc_type`, `doc_comments`                                | 4.2     |
| `invalidate_doc`      | `doc_type`, `doc_comments`                                | 4.2     |
| `request_corrections` | `corrections[field_name]` (one or more)                   | 4.5     |
| `approve`             | `validity_duration` (1/6/12), `decision_notes`            | 4.4     |
| `reject`              | `rejection_reason` (required, non-empty)                  | 4.4     |

### `POST student-dashboard.php`

| `action` value       | Required POST fields                                      | Feature |
|:---------------------|:----------------------------------------------------------|:--------|
| `resolve_corrections` | `application_id`, file uploads or `address` text field   | 4.5     |
