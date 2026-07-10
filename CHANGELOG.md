# Changelog

## 2026-07-07

### 🐛 Bug Fixes

#### Role Normalization (access_helper, role_helper)
- **`normalize_role()`** — fungsi baru untuk normalisasi role (`Admin` → `admin`, `Read Only` → `read_only`)
- **`isReadOnlyAccess()`** — sekarang gunakan `normalize_role()` sehingga role kapital/casing campuran tidak salah dibandingkan
- **`canAccessPage()`**, **`canShowMenuPage()`**, **`resolve_default_landing_url()`** — normalisasi sebelum compare
- **`hasRole()`** — normalisasi role session DAN parameter `$roles` agar konsisten
- **`it_assets/_table.php`** — hapus fallback verbose `function_exists('hasWriteAccess')`, sekarang pakai `hasWriteAccess()` langsung

#### WriteFilter
- Tidak ada perubahan — sudah aman; `(string) null` menghasilkan `""`, tidak error

---

### ✨ Feature: Comprehensive Audit Log System

#### Database
| File | Deskripsi |
|------|-----------|
| `app/Database/Migrations/2026-07-07-000001_CreateAuditLogsTable.php` | Migration: tambah kolom `ip_address` (VARCHAR 45) dan `user_agent` (TEXT) jika belum ada; buat tabel baru jika belum ada |

#### Helpers
| File | Deskripsi |
|------|-----------|
| `app/Helpers/audit_helper.php` | `audit_log($action, $description)` — simpan aktivitas dengan `user_id`, `ip_address`, `user_agent`; `build_query_params()`, `build_sort_url()`, `sort_icon()`, `build_page_url()` untuk UI |

#### Controller
| File | Deskripsi |
|------|-----------|
| `app/Controllers/AuditLogController.php` | Rewrite: search by description/action/user, filter by action type, user ID, date range, sortable columns, manual pagination (10-100 per page) |

#### View
| File | Deskripsi |
|------|-----------|
| `app/Views/audit_logs/index.php` | Redesign: search bar, action dropdown, user dropdown, date range picker, sortable table headers, pagination, responsive (IP column hide on mobile), color-coded action badges |

---

### 🔒 Audit Points — 20 Controllers

#### Auth & Users
| Controller | Actions |
|-----------|---------|
| `AuthController` | `login`, `logout` |
| `UserController` | `create_user`, `create_role`, `update_user`, `deactivate_user`, `activate_user` |
| `SettingsController` | `change_password` |

#### Compliance Inventory & Checklist
| Controller | Actions |
|-----------|---------|
| `ComplianceInventoryController` | `inventory_added`, `inventory_updated`, `inventory_deleted`, `photo_updated`, `checklist_submit`, `checklist_cctv`, `checklist_emergency_light`, `checklist_exit_light`, `checklist_first_aid_box`, `checklist_first_aid_content`, `checklist_fire_extinguisher`, `checklist_intrusion_alarm`, `checklist_hydrant`, `checklist_smoke_detector`, `checklist_heat_detector`, `checklist_gate`, `checklist_generic_grid` |
| `ComplianceChecklistMasterController` | `checklist_master_create`, `checklist_master_update`, `checklist_master_delete` |
| `ComplianceItemTypeController` | `item_type_create` |
| `ComplianceQuestionnaireController` | `questionnaire_create/update/delete`, `questionnaire_question_create/update/delete` |
| `ComplianceEvidenceController` | `evidence_update_followup` |

#### IT Asset & Employee
| Controller | Actions |
|-----------|---------|
| `ITAssetController` | `it_asset_create`, `it_asset_update`, `assign_asset` |
| `EmployeeController` | `employee_create`, `employee_update`, `employee_delete`, `employee_status`, `assign_asset`, `unassign_asset` |

#### Utility & Energy
| Controller | Actions |
|-----------|---------|
| `PdamWaterController` | `pdam_water_save`, `pdam_water_delete` |
| `PdamWaterBoilerController` | `pdam_water_boiler_create`, `pdam_water_boiler_delete` |
| `BoilerFuelController` | `boiler_fuel_save`, `boiler_fuel_delete` |
| `IpalController` | `ipal_save` |
| `EmsReportController` | `ems_water_consumption_save`, `ems_electric_consumption_save`, `ems_stationary/mobile_combustion_save` |
| `FdmDataCollectionController` | `fdm_data_collection_save` |
| `ThermalImagingController` | `thermal_imaging_create` |

#### Patrol
| Controller | Actions |
|-----------|---------|
| `PatrolController` | `patrol_session_start`, `patrol_session_scan`, `patrol_session_cancel`, `patrol_layout_save` |

#### Admin
| Controller | Actions |
|-----------|---------|
| `BackupController` | `backup_create`, `backup_delete`, `backup_restore` |
| `HolidayController` | `holiday_create`, `holiday_update`, `holiday_delete` |

---

### 📁 Files Changed
- `app/Helpers/audit_helper.php` — extend: `ip_address`, `user_agent`, helper functions untuk UI
- `app/Helpers/access_helper.php` — tambah `normalize_role()`, konsolidasi role check
- `app/Helpers/role_helper.php` — gunakan `normalize_role()` di `hasRole()`
- `app/Views/it_assets/_table.php` — gunakan `hasWriteAccess()` langsung
- `app/Controllers/AuditLogController.php` — rewrite lengkap dengan search/filter/sort/pagination
- `app/Controllers/AuthController.php` — tambah `audit_log('login')`, `audit_log('logout')`
- `app/Controllers/ComplianceInventoryController.php` — tambah audit_log di store, update, delete, updatePhoto, submitChecklist, 12 grid save methods
- `app/Controllers/ComplianceQuestionnaireController.php` — tambah audit_log di CRUD pertanyaan
- `app/Controllers/HolidayController.php` — tambah audit_log di store, update, delete
- `app/Controllers/ComplianceChecklistMasterController.php` — tambah audit_log di store, update, delete
- `app/Controllers/ComplianceEvidenceController.php` — tambah audit_log di updateFollowUp
- `app/Views/audit_logs/index.php` — redesign lengkap
- `app/Database/Migrations/2026-07-07-000001_CreateAuditLogsTable.php` — new file
