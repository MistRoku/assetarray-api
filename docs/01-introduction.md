# Introduction

AssetArray API is a production-grade Laravel REST API for multi-branch asset and inventory management.

## Base URL

```txt
http://localhost:8000/api/v1
```

## Authentication

All protected endpoints require a Sanctum bearer token.

```http
Authorization: Bearer YOUR_TOKEN
```

See [Authentication Flow](02-authentication-flow.md) for login, logout, token refresh and password reset.

## Key Modules

- Authentication
- Branch management
- Product and asset catalogue
- Inventory control
- Stock transfers
- Stock takes
- Suppliers
- Purchase orders
- Reporting
- Notifications
- Audit logs

## Purpose

AssetArray API demonstrates enterprise Laravel backend engineering, including:

- clean service-layer architecture
- role-based authorisation
- transactional inventory logic
- queue-driven notifications
- audit trails
- API documentation
- automated testing
