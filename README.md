# Product Cleanup

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
  - disable: disable found products based on laatste_importdatum
  - storeview: remove found products from all storeviews based on laatste_importdatum
  - delete: delete products from database based on laatste_importdatum
- Date (Optional): format Y-m-d, default: older then today

## **Options:**

- dry-run, run without making changes
