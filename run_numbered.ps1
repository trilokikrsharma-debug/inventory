$files = Get-ChildItem -Path database -Filter "0*.sql" | Sort-Object Name
foreach ($f in $files) {
    Write-Host "Running $($f.Name)"
    cmd.exe /c "F:\xampp\mysql\bin\mysql.exe -u root inventory < database\$($f.Name)"
}
