import sys
import shutil
import os
import json

SCRIPT_DIR = os.path.dirname(__file__)
CONFIG_PATH = os.path.join(SCRIPT_DIR, "config.json")

# Make sure config file exists
if not os.path.exists(CONFIG_PATH):
    print(f"Missing config file: {CONFIG_PATH}")
    print("Quitting")
    sys.exit(1)

try:
    with open(CONFIG_PATH) as config_file:
        config = json.load(config_file)
except Exception as e:
    print("Error reading config file")
    print(repr(e))

if not config:
    print("Config file not loaded")
    print("Quitting")
    sys.exit(1)

env = config["env"]
WEB_ROOT = config["web"]["www_dir"]



def delete_files(path):
    dir_list = os.listdir(path)
    for f in dir_list:
        f_path = os.path.join(path, f)
        if os.path.isdir(f_path):
            if "phpMyAdmin" in f:
                continue
            else:
                shutil.rmtree(f_path)
        else:
            os.remove(f_path)
    return True

def deploy_web(src, dst):
    r = shutil.copytree(src, dst, dirs_exist_ok=True) 
    if r:
        return True
    return False



print("=== Deploying Chillbot ===")
print(f"  WEB_ROOT: {WEB_ROOT}")
print()


if env == "test":
    print("=== Remove Old Files ===")
    print("  Clearing WEB_ROOT...", end="")
    if delete_files(WEB_ROOT):
        print("Done")
    else:
        print("ERROR")
        print("  Error clearing the web root. Quitting.")
        sys.exit()
    print()
    print("=== Deploying Web Code ===")
    repo_web_dir = os.path.join(SCRIPT_DIR, "web")
    print(f"  Copying from: {repo_web_dir}")
    print(f"  Copying to: {WEB_ROOT}...", end="")
    if deploy_web(repo_web_dir, WEB_ROOT):
        print("Done")
    else:
        print("ERROR")
        print("  Error copying web files to WEB_ROOT. Quitting.")
        sys.exit()
    print()

print("=== Writing PHP Config File ===")
print("Copying config.json")

dst_config = os.path.join(WEB_ROOT, "inc", "config.json")
if shutil.copy2(CONFIG_PATH, dst_config):
    print("Done")
else:
    print("Error copying config.json")




