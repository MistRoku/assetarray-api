# Audit Trail

Every important create, update, and delete action is logged via
`AuditLogService`, always inside the mutating transaction — the audit row
rolls back together with the change it describes, so no orphan entries.

```http
GET /api/v1/audit-logs
```

Super-admin only: rows contain before/after snapshots that may include
sensitive values. Optional `entity_type`, `entity_id`, `user_id`, `from`,
`to`, `per_page` filters combine with AND, newest first.

Stored data:

- user_id (null for system/queue actions)
- action (`created`, `updated`, `deleted`)
- entity_type
- entity_id
- old_values
- new_values
- ip_address (null outside HTTP)
- created_at (single timestamp — no `updated_at` by design)

Audit logs are immutable. Update or delete attempts throw
(`AuditLogObserver`), even for admins — history can only be appended.
