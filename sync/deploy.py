import os
import subprocess
import ftplib
import re

# ==============================================================================
# CANONICAL SINGLE WORKING LIVE DEPLOYMENT SCRIPT FOR MPSCABHYAS.IN
# ==============================================================================
FTP_HOST = '93.127.208.183'
FTP_USER = 'u122332128.mpscftp'
FTP_PASS = 'Mpscabhyas#293330'

# Hostinger Web Root Target Directories
TARGET_DIRS = [
    '/domains/mpscabhyas.in/public_html/search',
    '/domains/mpscabhyas.in/public_html',
    '/search'
]

def deploy():
    cache_file = os.path.join(os.path.dirname(__file__), 'hierarchy_cache.json')
    import sys
    if not os.path.exists(cache_file) or '--refresh-cache' in sys.argv:
        print("=== BUILDING FRESH HIERARCHY CACHE FROM TIDB DB ===")
        subprocess.run(['node', 'build_hierarchy_cache.mjs'], cwd=os.path.dirname(__file__))
    else:
        print("=== USING EXISTING HIERARCHY CACHE (Pass --refresh-cache to rebuild) ===")

    print(f"\n=== CONNECTING TO HOSTINGER FTP ({FTP_HOST}) ===")
    files_to_deploy = ['search.php', 'subject.php', 'config.php', '.htaccess', 'hierarchy_cache.json']

    for target_dir in TARGET_DIRS:
        print(f"\n---> Uploading files to target directory: {target_dir}")
        for filename in files_to_deploy:
            if os.path.exists(filename):
                success = False
                for attempt in range(1, 4):
                    print(f"Uploading {filename} via curl to {target_dir} (Attempt {attempt})...")
                    cmd = f'curl.exe -s --ftp-pasv -u "{FTP_USER}:{FTP_PASS}" -T "{filename}" "ftp://{FTP_HOST}{target_dir}/{filename}"'
                    res = subprocess.run(cmd, shell=True, capture_output=True, text=True)
                    if res.returncode == 0:
                        print(f"[SUCCESS] {filename} deployed to {target_dir}/{filename}")
                        success = True
                        break
                    else:
                        import time
                        time.sleep(1)
                if not success:
                    print(f"[ERROR] Failed to upload {filename} to {target_dir} after 3 attempts!")

    print("\n[SUCCESS] LIVE DEPLOYMENT COMPLETE FOR ALL TARGET DIRECTORIES!")

if __name__ == '__main__':
    deploy()
