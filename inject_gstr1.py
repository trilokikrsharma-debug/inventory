import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
try:
    with sftp.open('/var/www/tsalegacy/controllers/SalesController.php', 'r') as f:
        c = f.read().decode('utf-8')
        print("Length of SalesController:", len(c))
        
        # We need to insert a new method `export_gstr1`
        # Let's find the `protected $allowedActions` and append it there.
        if 'export_gstr1' not in c:
            c = c.replace('protected $allowedActions = [', 'protected $allowedActions = [\'export_gstr1\', ')
            
            # Put the method at the end of the file before last }
            new_method = """
    public function export_gstr1() {
        $this->requirePermission('sales.view');
        $db = Database::getInstance();
        $cid = Tenant::id();
        
        $type = $_GET['type'] ?? 'b2b';
        $month = $_GET['month'] ?? date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        if ($type === 'b2b') {
            // Customers with Tax Number (GSTIN) >= 15 chars
            $sql = "SELECT s.invoice_number, s.sale_date, s.grand_total, s.tax_amount, c.name, c.tax_number, c.state 
                    FROM sales s 
                    JOIN customers c ON s.customer_id = c.id 
                    WHERE s.company_id = ? AND s.sale_date BETWEEN ? AND ? 
                    AND c.tax_number IS NOT NULL AND LENGTH(c.tax_number) >= 15
                    AND s.status != 'cancelled' AND s.deleted_at IS NULL";
            $data = $db->query($sql, [$cid, $startDate, $endDate])->fetchAll();
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="GSTR1_B2B_' . $month . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['GSTIN/UIN of Recipient', 'Receiver Name', 'Invoice Number', 'Invoice date', 'Invoice Value', 'Place Of Supply', 'Reverse Charge', 'Invoice Type', 'Rate', 'Taxable Value']);
            foreach ($data as $row) {
                // Simplified B2B structure
                fputcsv($out, [
                    $row['tax_number'], $row['name'], $row['invoice_number'], 
                    date('d-M-y', strtotime($row['sale_date'])), 
                    $row['grand_total'], $row['state'], 'N', 'Regular', 
                    '', ($row['grand_total'] - $row['tax_amount'])
                ]);
            }
            fclose($out);
            exit;
        } else if ($type === 'b2c') {
            // Customers without proper GSTIN
            $sql = "SELECT c.state, SUM(s.grand_total) as tot_val, SUM(s.tax_amount) as tot_tax 
                    FROM sales s 
                    JOIN customers c ON s.customer_id = c.id 
                    WHERE s.company_id = ? AND s.sale_date BETWEEN ? AND ? 
                    AND (c.tax_number IS NULL OR LENGTH(c.tax_number) < 15)
                    AND s.status != 'cancelled' AND s.deleted_at IS NULL
                    GROUP BY c.state";
            $data = $db->query($sql, [$cid, $startDate, $endDate])->fetchAll();
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="GSTR1_B2C_' . $month . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Place Of Supply', 'Rate', 'Taxable Value', 'Cess Amount', 'E-Commerce GSTIN']);
            foreach ($data as $row) {
                $pos = empty($row['state']) ? 'Other Territory' : $row['state'];
                fputcsv($out, [
                    $pos, '', ($row['tot_val'] - $row['tot_tax']), '', ''
                ]);
            }
            fclose($out);
            exit;
        }
    }
}"""
            last_brace = c.rfind('}')
            c = c[:last_brace] + new_method
            
            with sftp.open('/var/www/tsalegacy/controllers/SalesController.php', 'w') as f:
                f.write(c.encode('utf-8'))
            print("GSTR-1 Controller methods injected.")
        else:
            print("GSTR-1 methods already exist.")
except Exception as e:
    print(e)
    
sftp.close()
ssh.close()
