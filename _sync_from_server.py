import paramiko, os

HOST = '34.14.169.146'
USER = 'deploy'
PASS = 'Triloki'

def main():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    print(f"Connecting to {HOST}...")
    client.connect(HOST, username=USER, password=PASS, timeout=15)
    sftp = client.open_sftp()

    files = [
        'assets/brand-mark-mono.svg',
        'assets/favicon.svg',
        'assets/icon.svg',
        'assets/logo-lockup.svg',
        'assets/logo-wordmark.svg',
        'assets/og-default.svg',
        'views/auth/login.php',
        'views/auth/signup.php',
        'views/public/blog_article.php',
        'views/public/blog_index.php',
        'views/public/home.php',
        'views/public/pricing.php',
        'views/public/privacy.php',
        'views/public/refund.php',
        'views/public/seo_page.php',
        'views/public/terms.php',
        'assets/css/public-brand.css',
        'views/public/_partials/brand.php'
    ]

    for f in files:
        remote_path = f'/var/www/tsalegacy/{f}'
        local_path = os.path.join(r'c:\Users\KARSO\Desktop\tsa-server', f.replace('/', '\\'))
        
        # ensure local dir exists
        os.makedirs(os.path.dirname(local_path), exist_ok=True)
        
        try:
            sftp.get(remote_path, local_path)
            print(f'Downloaded {f}')
        except Exception as e:
            print(f'Failed to download {f}: {e}')

    sftp.close()
    client.close()
    print('Sync from server complete.')

if __name__ == '__main__':
    main()
