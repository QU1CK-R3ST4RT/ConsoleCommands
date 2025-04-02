# Product Cleanup for magento2

## **Required Product Attributes**
  - supplier
  - last_import_date
## **example**
```bash
bin/magento clean:supplier "SupplierName" "disable" "2025-03-26" --dry-run
bin/magento clean:supplier "SupplierName" "storeview" --dry-run
bin/magento clean:supplier "SupplierName" "delete" --dry-run
```

## **Params:**

- Supplier Name
- method : disable, storeview, delete
  - disable: disable found products based on last_import_date
  - storeview: remove found products from all storeviews based on last_import_date
  - delete: delete products from database based on last_import_date
- Date (Optional): format Y-m-d, default: older then today

## **Options:**

- dry-run, run without making changes
