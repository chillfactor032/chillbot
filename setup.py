import sys
import shutil
import os
import json
import subprocess
import argparse
import platform
from enum import Enum, auto

class OperatingSystem(Enum):
    WINDOWS = auto()
    MAC = auto()
    LINUX = auto()

parser = argparse.ArgumentParser(
    prog = 'ChillBot',
    description = 'A Twitch.tv chat bot',
    epilog = '')
parser.add_argument("-c", "--config", default="config.json", help="Path to the config.json file.")
parser.add_argument("-n", "--clean", action='store_true', help="Remove the old files only.")
args = parser.parse_args()

# Place the config file adjacent to setup.py
SCRIPT_DIR = os.path.dirname(__file__)
WEB_APP_SOURCE = os.path.join(SCRIPT_DIR, "web").replace("\\", "/")
CONFIG_PATH = os.path.join(SCRIPT_DIR, args.config).replace("\\", "/")

# Running Setup Script to move the files to the correct location
def main():
    print("=== ChillBot Setup ===")
    if get_os() == OperatingSystem.WINDOWS:
        print("  Windows OS Detected")
        python_cmd = "py"
    else:
        print("  Linux Detected")
        python_cmd = "python3"
    print()
    
    print("=== Reading Config File ===")
    print(f"  Config Path: {CONFIG_PATH}")
    config = read_config(CONFIG_PATH)
    if config is None:
        print("  Config file not loaded. Quitting")
        sys.exit(1)
    env = config["env"]
    web_root = config["web"]["www_dir"]
    print(f"  Env: {env}")
    print(f"  Bot Web App Src: {WEB_APP_SOURCE}")
    print(f"  Web Root: {web_root}")
    print()

    print("=== Clean Web Root Dir ===")
    if delete_files(web_root):
        print("  Done!")
    else:
        print("  Error clearing web root. Quitting")
        sys.exit(1)
    print()

    if args.clean:
        print("=== Clean Only Flag Set ===")
        print("  Skipping Further Steps")
        print()
        print("=== Setup Complete ===")
        sys.exit(0)
    else:
        print("=== Copy web To Web Root ===")
        if deploy_web(WEB_APP_SOURCE, web_root):
            print("  Done!")
        else:
            print("  Error copying bot files to web root. Quitting")
            sys.exit(1)
        print()

    print("=== Writing PHP Config File ===")
    dst_config = os.path.join(web_root, "inc", "config.json").replace("\\", "/")
    print(f"  Config Source Path: {CONFIG_PATH}")
    print(f"  Config Destination Path: {dst_config}")
    if copy_config(CONFIG_PATH, dst_config):
        print("  Copy complete.")
    else:
        print("  Error copying config file! Quitting")
        sys.exit(1)
    print()

    print("== CHOWN Web Root to www-data ===")
    if env == "prod":
        if chown(web_root):
            print("  Done")
        else:
            print("  Error running CHMOD and CHOWN")
    else:
        print("  Non-prod env. Skipping this step.")
    print("")
    print("=== Setup Complete ===")

def get_os():
    platform_str = platform.platform()
    if "Windows" in platform_str:
        return OperatingSystem.WINDOWS
    elif "MacOS" in platform_str:
        return OperatingSystem.MAC
    else:
        return OperatingSystem.LINUX

def read_config(path):
    config = None
    # Make sure config file exists
    if not os.path.exists(path):
        print(f"  Missing config file: {path}")
        return None
    try:
        with open(path) as config_file:
            config = json.load(config_file)
    except Exception as e:
        print("  Error reading config file")
        print(repr(e))
        return None
    return config

# Delete all files and directories in path, except phpMyAdmin
def delete_files(path):
    dir_list = os.listdir(path)
    for f in dir_list:
        f_path = os.path.join(path, f).replace("\\", "/")
        if os.path.isdir(f_path):
            if "phpMyAdmin" in f:
                continue
            else:
                shutil.rmtree(f_path)
        else:
            os.remove(f_path)
    return True

def deploy_web(src, dst):
    cmd = f"cp -pr {src}/* {dst}"
    print(f"  {cmd}")
    ret = subprocess.run([cmd], capture_output=True)
    if(ret.returncode != 0):
        print(f"  Error running copy command")
        err = ret.stderr.decode('utf-8')
        out = ret.stdout.decode('utf-8')
        if len(ret.stderr) > 0:
            print(f"  {err}")
        if len(ret.stdout) > 0:
            print(f"  {out}")
        return False
    return True

# Copy the config.json to the PHP Dir
def copy_config(src, dst):
    cmd = f"cp -p {src} {dst}"
    print(f"  {cmd}")
    ret = subprocess.run([cmd], capture_output=True)
    if(ret.returncode != 0):
        print(f"  Error running copy command")
        err = ret.stderr.decode('utf-8')
        out = ret.stdout.decode('utf-8')
        if len(ret.stderr) > 0:
            print(f"  {err}")
        if len(ret.stdout) > 0:
            print(f"  {out}")
        return False
    return True

def pip_install_requirements(python_cmd, req_path, log_path):
    #python3 -m pip install -r {req_path}
    cmd = f"{python_cmd} -m pip install -r {req_path}"
    print(f"  {cmd}")
    ret = subprocess.run([cmd], shell=True, capture_output=True)
    if(ret.returncode != 0):
        print(f"  Error running pip install command")
        err = ret.stderr.decode('utf-8')
        out = ret.stdout.decode('utf-8')
        if len(ret.stderr) > 0:
            print(f"  {err}")
        if len(ret.stdout) > 0:
            with open(log_path, "w") as log:
                log.write(out)
                log.write(err)
            print(f"  Wrote Log: {log_path}")
        return False
    return True

def chown(web_dir):
    #chmod -R g+rwx {WEB_ROOT} && chown -R www-data:www-data {WEB_ROOT}
    chmod_cmd = f"chmod -R g+rwx {web_dir}"
    chown_cmd = f"chown -R www-data:www-data {web_dir}"
    print(f"  {chmod_cmd}")
    ret = subprocess.run([chmod_cmd], shell=True, capture_output=True)
    if(ret.returncode != 0):
        print(f"  Error running chmod command:")
        err = ret.stderr.decode('utf-8')
        out = ret.stdout.decode('utf-8')
        if len(ret.stderr) > 0:
            print(f"  {err}")
        if len(ret.stdout) > 0:
            print(f"  {out}")
        return False

    print(f"  {chown_cmd}")
    ret = subprocess.run([chown_cmd], shell=True, capture_output=True)
    if(ret.returncode != 0):
        print(f"  Error running chown command:")
        err = ret.stderr.decode('utf-8')
        out = ret.stdout.decode('utf-8')
        if len(ret.stderr) > 0:
            print(f"  {err}")
        if len(ret.stdout) > 0:
            print(f"  {out}")
        return False
    return True

if __name__ == "__main__":
   main()
