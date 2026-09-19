# Transfer Workflow

Stock transfers move inventory between branches. Approval rights sit with
the **destination** branch's manager — the receiver accepts the stock.

## States

```txt
pending -> approved -> received
pending -> rejected
```

## Create Transfer

```http
POST /api/v1/inventory/transfers
```

Reserves nothing: source stock is re-checked and decremented at approval,
so request against live availability. Same-branch requests are rejected.

## Approve Transfer

```http
PUT /api/v1/inventory/transfers/{id}/approve
```

Deducts stock from the source branch under row lock and writes a
`transfer_out` movement. Fails when source stock dropped since the request.

## Receive Transfer

```http
PUT /api/v1/inventory/transfers/{id}/receive
```

Adds stock to the destination branch (creating the level on first receipt)
and writes a `transfer_in` movement. Stock is never double-counted:
source decrements at approval, destination increments at receipt.

## Reject Transfer

```http
PUT /api/v1/inventory/transfers/{id}/reject
```

Requires a `reason`, stored for the audit trail. Terminal state.
