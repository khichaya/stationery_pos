import sys
import subprocess
import os
import time
import json
from datetime import datetime

# 1️⃣ إجبار البايثون على استخدام ترميز UTF-8 في الإدخال والإخراج لمنع انهيار السكربت في الويندوز
sys.stdout.reconfigure(encoding='utf-8')
sys.stderr.reconfigure(encoding='utf-8')

# 🛡️ محرك التثبيت الذاتي للمكتبات المفقودة
try:
    import mysql.connector
except ModuleNotFoundError:
    print("Warning: mysql-connector missing, installing now...")
    subprocess.check_call([sys.executable, "-m", "pip", "install", "mysql-connector-python"])
    import mysql.connector
    print("database connector installed successfully!")

# إعدادات الاتصال بقاعدة البيانات (تطابق ملف .env الخاص بك)
DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': '',
    'database': 'stationery_pos'
}

# المسار الذي يحفظ فيه لارافل ملفات النسخ
BACKUP_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), '../storage/app/backups'))

def get_backup_settings():
    """جلب الإعدادات الحية من جدول settings الخاص بلارافل"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT value FROM settings WHERE `key` = 'backup_config' LIMIT 1")
        row = cursor.fetchone()
        cursor.close()
        conn.close()
        
        if row:
            return json.loads(row['value'])
    except Exception as e:
        print(f"[{datetime.now()}] Error reading settings: {e}")
    return None

def log_backup_to_db(filename, status):
    """تسجيل سطر النسخة الاحتياطية في جدول backups لكي يظهر في لارافل"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        query = "INSERT INTO backups (filename, status, created_at, updated_at) VALUES (%s, %s, %s, %s)"
        cursor.execute(query, (filename, status, now, now))
        conn.commit()
        cursor.close()
        conn.close()
        print(f"[{now}] Backup logged into database table backups successfully.")
    except Exception as e:
        print(f"Error logging to database: {e}")

def run_mysql_dump(filename):
    """تنفيذ عملية النسخ الفعلي وتوليد ملف SQL حقيقي"""
    if not os.path.exists(BACKUP_DIR):
        os.makedirs(BACKUP_DIR)
        
    filepath = os.path.join(BACKUP_DIR, filename)
    
    mysqldump_cmd = "mysqldump"
    if os.path.exists("C:\\xampp\\mysql\\bin\\mysqldump.exe"):
        mysqldump_cmd = '"C:\\xampp\\mysql\\bin\\mysqldump.exe"'
        
    password_part = f"-p{DB_CONFIG['password']}" if DB_CONFIG['password'] else ""
    
    command = f"{mysqldump_cmd} -h {DB_CONFIG['host']} -u {DB_CONFIG['user']} {password_part} {DB_CONFIG['database']} > \"{filepath}\""
    
    return_code = os.system(command)
    return return_code == 0

# 🔄 حلقة لا متناهية تعمل في الخلفية بالكامل
print("Bayane Backup Background Service Started Successfully...")
last_backed_up_date = "" 

while True:
    current_time = datetime.now().strftime("%H:%M")
    current_date = datetime.now().strftime("%Y-%m-%d")
    
    config = get_backup_settings()
    
    if config and config.get('auto_backup'):
        target_time = config.get('backup_time', '23:00')
        
        if current_time == target_time and last_backed_up_date != current_date:
            print(f"Target time reached ({target_time}). Starting automatic backup...")
            
            filename = f"stationery_pos_auto_{datetime.now().strftime('%Y_%m_%d_%H%M%S')}.sql"
            
            if run_mysql_dump(filename):
                log_backup_to_db(filename, 'success')
                last_backed_up_date = current_date
            else:
                log_backup_to_db(filename, 'failed')
                
    time.sleep(60)