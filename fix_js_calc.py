import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()

for file_path in ['/var/www/tsalegacy/views/sales/create.php', '/var/www/tsalegacy/views/purchases/create.php']:
    try:
        with sftp.open(file_path, 'r') as f:
            c = f.read().decode('utf-8')
            
            # Fix parse float errors
            c = c.replace(
                "parseFloat(document.getElementById('discountInput').value)",
                "parseFloat(document.getElementById('discountInput')?.value || document.querySelector('input[name=\"discount\"]')?.value)"
            )
            # Add safe check for inputs in the event listener section too
            c = c.replace(
                "document.getElementById('discountInput').addEventListener('input', calc);",
                "if(document.getElementById('discountInput')) document.getElementById('discountInput').addEventListener('input', calc); else if(document.querySelector('input[name=\"discount\"]')) document.querySelector('input[name=\"discount\"]').addEventListener('input', calc);"
            )
            
            # Transport input fallback
            c = c.replace(
                "const ship = freight + loading;",
                "const transport = parseFloat(document.getElementById('transportInput')?.value) || parseFloat(document.querySelector('input[name=\"shipping_cost\"]')?.value) || 0; const ship = freight + loading + transport;"
            )
            c = c.replace(
                "if (document.getElementById('transportInput')) document.getElementById('transportInput').addEventListener('input', calc);",
                ""
            )
            # append transportInput listener
            if "addItem();" in c:
                c = c.replace(
                    "addItem();",
                    "if (document.getElementById('transportInput')) document.getElementById('transportInput').addEventListener('input', calc);\nelse if(document.querySelector('input[name=\"shipping_cost\"]')) document.querySelector('input[name=\"shipping_cost\"]').addEventListener('input', calc);\naddItem();"
                )

            with sftp.open(file_path, 'w') as fw:
                fw.write(c.encode('utf-8'))
            print("Fixed JS calc exceptions in " + file_path)
    except Exception as e:
        print("Err parsing " + file_path + ":", e)

sftp.close()
ssh.close()
