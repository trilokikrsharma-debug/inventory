$files = @(
    "schema.sql",
    "rbac_migration.sql",
    "multi_tenant_migration.sql",
    "super_admin_migration.sql",
    "enterprise_hardening.sql",
    "enterprise_platform.sql",
    "performance_indexes.sql"
)

foreach ($file in $files) {
    Write-Host "Importing $file..."
    cmd.exe /c "F:\xampp\mysql\bin\mysql.exe -u root inventory < database\$file"
}
