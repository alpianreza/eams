import getpass
import json
import os
import platform
import socket
import subprocess
import sys
import tempfile
import time
import uuid
from datetime import datetime
from urllib.parse import urlparse

try:
    import psutil
except Exception:
    psutil = None

try:
    import requests
except Exception:
    requests = None


APP_NAME = "YHS Client"
START_TS = time.time()
DEFAULT_CONFIG = {
    "device_token": "",
    "lan_url": "http://192.168.1.14/api/agent/heartbeat",
    "public_url": "https://eams.ptyhs.com/api/agent/heartbeat",
    "command_url": "https://eams.ptyhs.com/api/agent/command",
    "interval": 900,
    "command_poll_interval": 5,
    "agent_version": "2.1",
    "update_url": "https://eams.ptyhs.com/api/agent/update",
    "startup": True,
}


def get_install_dir():
    if getattr(sys, "frozen", False):
        return os.path.dirname(sys.executable)
    return os.path.dirname(os.path.abspath(__file__))


def get_data_dir():
    if getattr(sys, "frozen", False):
        base = os.environ.get("PROGRAMDATA") or get_install_dir()
        return os.path.join(base, APP_NAME)
    return get_install_dir()


INSTALL_DIR = get_install_dir()
DATA_DIR = get_data_dir()
CONFIG_PATH = os.path.join(DATA_DIR, "config.json")
LOG_PATH = os.path.join(DATA_DIR, "agent.log")
LEGACY_CONFIG_PATH = os.path.join(INSTALL_DIR, "config.json")
LEGACY_LOG_PATH = os.path.join(INSTALL_DIR, "agent.log")


def log(message):
    try:
        os.makedirs(DATA_DIR, exist_ok=True)
        with open(LOG_PATH, "a", encoding="utf8") as handle:
            handle.write(f"{datetime.now().isoformat(timespec='seconds')} | {message}\n")
    except Exception:
        pass


def _copy_legacy_if_needed(source_path, target_path):
    if source_path == target_path:
        return
    if os.path.exists(target_path) or not os.path.exists(source_path):
        return
    try:
        with open(source_path, "rb") as src, open(target_path, "wb") as dst:
            dst.write(src.read())
        log(f"Migrated legacy file: {os.path.basename(source_path)}")
    except Exception as exc:
        log(f"Legacy migration failed for {source_path}: {exc}")


def _load_config():
    merged = DEFAULT_CONFIG.copy()

    if os.path.exists(CONFIG_PATH):
        try:
            with open(CONFIG_PATH, "r", encoding="utf-8-sig") as handle:
                loaded = json.load(handle)
            if isinstance(loaded, dict):
                merged.update(loaded)
        except Exception as exc:
            log(f"Config read failed, using defaults: {exc}")

    return merged


def _normalize_heartbeat_url(url):
    raw = str(url or "").strip()
    if not raw:
        return raw

    normalized = raw
    normalized = normalized.replace("/api/it/agent-heartbeat", "/api/agent/heartbeat")
    normalized = normalized.replace("/api/it/heartbeat", "/api/agent/heartbeat")
    normalized = normalized.replace("/api/agent/heartbeat/", "/api/agent/heartbeat")
    return normalized


def _derive_update_url(heartbeat_url):
    heartbeat = _normalize_heartbeat_url(heartbeat_url)
    if not heartbeat:
        return ""
    if "/api/agent/heartbeat" in heartbeat:
        return heartbeat.replace("/api/agent/heartbeat", "/api/agent/update")
    return ""


def _derive_command_url(heartbeat_url):
    heartbeat = _normalize_heartbeat_url(heartbeat_url)
    if not heartbeat:
        return ""
    if "/api/agent/heartbeat" in heartbeat:
        return heartbeat.replace("/api/agent/heartbeat", "/api/agent/command")
    return ""


def _normalize_update_url(update_url, fallback_heartbeat=None):
    raw = str(update_url or "").strip()

    if not raw and fallback_heartbeat:
        return _derive_update_url(fallback_heartbeat)

    normalized = raw
    normalized = normalized.replace("/api/it/agent-update", "/api/agent/update")
    normalized = normalized.replace("/api/it/agent_update", "/api/agent/update")
    normalized = normalized.replace("/api/agent/update/", "/api/agent/update")
    return normalized


def _normalize_command_url(command_url, fallback_heartbeat=None):
    raw = str(command_url or "").strip()

    if not raw and fallback_heartbeat:
        return _derive_command_url(fallback_heartbeat)

    normalized = raw
    normalized = normalized.replace("/api/agent/command/", "/api/agent/command")
    normalized = normalized.replace("/api/it/agent-command", "/api/agent/command")
    normalized = normalized.replace("/api/it/agent_command", "/api/agent/command")
    return normalized


def _normalize_config_values(config):
    changed = False

    lan_url = _normalize_heartbeat_url(config.get("lan_url", ""))
    if lan_url != config.get("lan_url", ""):
        config["lan_url"] = lan_url
        changed = True

    public_url = _normalize_heartbeat_url(config.get("public_url", ""))
    if public_url != config.get("public_url", ""):
        config["public_url"] = public_url
        changed = True

    update_url = _normalize_update_url(config.get("update_url", ""), public_url or lan_url)
    if update_url != config.get("update_url", ""):
        config["update_url"] = update_url
        changed = True

    command_url = _normalize_command_url(config.get("command_url", ""), public_url or lan_url)
    if command_url != config.get("command_url", ""):
        config["command_url"] = command_url
        changed = True

    return changed


def save_config():
    try:
        os.makedirs(DATA_DIR, exist_ok=True)
        tmp = CONFIG_PATH + ".tmp"
        with open(tmp, "w", encoding="utf8") as handle:
            json.dump(cfg, handle, indent=2, ensure_ascii=False)
        os.replace(tmp, CONFIG_PATH)
    except Exception as exc:
        log(f"Config write failed: {exc}")


os.makedirs(DATA_DIR, exist_ok=True)
_copy_legacy_if_needed(LEGACY_CONFIG_PATH, CONFIG_PATH)
_copy_legacy_if_needed(LEGACY_LOG_PATH, LOG_PATH)
cfg = _load_config()
if _normalize_config_values(cfg):
    log("Config endpoints normalized")
if str(cfg.get("agent_version", "")).strip() != DEFAULT_CONFIG["agent_version"]:
    cfg["agent_version"] = DEFAULT_CONFIG["agent_version"]
    log(f"Agent version set to {cfg['agent_version']}")
save_config()

DEVICE_TOKEN = cfg.get("device_token", "")
LAN_URL = cfg.get("lan_url", DEFAULT_CONFIG["lan_url"])
PUBLIC_URL = cfg.get("public_url", DEFAULT_CONFIG["public_url"])
COMMAND_URL = cfg.get("command_url", DEFAULT_CONFIG["command_url"]) or _derive_command_url(PUBLIC_URL or LAN_URL)
INTERVAL = int(cfg.get("interval", DEFAULT_CONFIG["interval"]) or DEFAULT_CONFIG["interval"])
COMMAND_POLL_INTERVAL = int(
    cfg.get("command_poll_interval", DEFAULT_CONFIG["command_poll_interval"])
    or DEFAULT_CONFIG["command_poll_interval"]
)
AGENT_VERSION = str(cfg.get("agent_version", DEFAULT_CONFIG["agent_version"]))
UPDATE_URL = cfg.get("update_url", DEFAULT_CONFIG["update_url"]) or _derive_update_url(PUBLIC_URL or LAN_URL)
STARTUP_ENABLED = bool(cfg.get("startup", True))

if INTERVAL < 10:
    INTERVAL = DEFAULT_CONFIG["interval"]
    cfg["interval"] = INTERVAL
    save_config()

if COMMAND_POLL_INTERVAL < 5:
    COMMAND_POLL_INTERVAL = DEFAULT_CONFIG["command_poll_interval"]
    cfg["command_poll_interval"] = COMMAND_POLL_INTERVAL
    save_config()

HARDWARE_CACHE = None
SYSTEM_CACHE = None
WMI_CLIENT = None
WMI_INIT_DONE = False

session = requests.Session() if requests else None
if session:
    session.headers.update(
        {
            "User-Agent": f"YHSClient/{AGENT_VERSION}",
            "Accept": "application/json",
        }
    )


def get_wmi():
    global WMI_CLIENT
    global WMI_INIT_DONE

    if WMI_INIT_DONE:
        return WMI_CLIENT

    WMI_INIT_DONE = True
    try:
        import wmi

        WMI_CLIENT = wmi.WMI()
    except Exception as exc:
        log(f"WMI unavailable: {exc}")
        WMI_CLIENT = None
    return WMI_CLIENT


def get_client_arch():
    return "x64" if sys.maxsize > 2**32 else "x86"


def get_os_arch():
    env_arch = os.environ.get("PROCESSOR_ARCHITECTURE", "")
    wow64_arch = os.environ.get("PROCESSOR_ARCHITEW6432", "")
    arch_text = (wow64_arch or env_arch or platform.machine()).lower()
    if "64" in arch_text or arch_text in {"amd64", "x86_64"}:
        return "x64"
    return "x86"


def get_runtime_health():
    return {
        "client_arch": get_client_arch(),
        "os_arch": get_os_arch(),
        "python_version": platform.python_version(),
        "frozen": bool(getattr(sys, "frozen", False)),
        "uptime_sec": int(time.time() - START_TS),
    }


def get_ip():
    sock = None
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.connect(("8.8.8.8", 80))
        return sock.getsockname()[0]
    except Exception:
        return None
    finally:
        if sock:
            sock.close()


def get_mac_address():
    try:
        client = get_wmi()
        if client:
            for nic in client.Win32_NetworkAdapterConfiguration(IPEnabled=True):
                if nic.MACAddress:
                    return nic.MACAddress.lower()
    except Exception:
        pass

    try:
        mac = uuid.getnode()
        return ":".join(
            ["{:02x}".format((mac >> shift) & 0xFF) for shift in range(0, 8 * 6, 8)][::-1]
        )
    except Exception:
        return None


def get_serial():
    try:
        client = get_wmi()
        if client:
            bios = client.Win32_BIOS()
            if bios:
                return bios[0].SerialNumber
    except Exception:
        pass
    return None


def system_info():
    details = {"architecture": platform.machine()}
    try:
        client = get_wmi()
        if not client:
            return details

        sysinfo = client.Win32_ComputerSystem()[0]
        bios = client.Win32_BIOS()[0]
        cpu = client.Win32_Processor()[0]
        gpus = client.Win32_VideoController()
        gpu_name = gpus[0].Name if gpus else None

        details.update(
            {
                "manufacturer": getattr(sysinfo, "Manufacturer", None),
                "model": getattr(sysinfo, "Model", None),
                "bios": getattr(bios, "SMBIOSBIOSVersion", None),
                "cpu_name": getattr(cpu, "Name", None),
                "cpu_core": getattr(cpu, "NumberOfCores", None),
                "cpu_thread": getattr(cpu, "NumberOfLogicalProcessors", None),
                "gpu": gpu_name,
            }
        )
    except Exception as exc:
        log(f"system_info failed: {exc}")
    return details


def system_info_cached():
    global SYSTEM_CACHE
    if SYSTEM_CACHE is not None:
        return SYSTEM_CACHE
    SYSTEM_CACHE = system_info()
    return SYSTEM_CACHE


def disk_model():
    try:
        client = get_wmi()
        if client:
            models = [str(disk.Model).strip() for disk in client.Win32_DiskDrive() if getattr(disk, "Model", None)]
            if models:
                return ", ".join(models)
    except Exception:
        pass

    disks = get_disks()
    models = [str(disk.get("model") or "").strip() for disk in disks if isinstance(disk, dict)]
    models = [model for model in models if model]
    return ", ".join(models) if models else None


def storage_total():
    if not psutil:
        return None
    try:
        usage = psutil.disk_usage("C:\\")
        return round(usage.total / (1024**3))
    except Exception:
        return None


def storage_free():
    if not psutil:
        return None
    try:
        usage = psutil.disk_usage("C:\\")
        return round(usage.free / (1024**3), 2)
    except Exception:
        return None


def get_windows_name():
    try:
        client = get_wmi()
        if client:
            os_info = client.Win32_OperatingSystem()[0]
            caption = getattr(os_info, "Caption", None)
            if caption:
                return caption
    except Exception:
        pass

    output = _run_hidden_powershell("(Get-CimInstance Win32_OperatingSystem).Caption")
    if output:
        return output.splitlines()[0].strip()

    return platform.system()


def get_windows_edition():
    try:
        client = get_wmi()
        if client:
            os_info = client.Win32_OperatingSystem()[0]
            caption = getattr(os_info, "Caption", None)
            if caption:
                return caption
    except Exception:
        pass

    output = _run_hidden_powershell("(Get-CimInstance Win32_OperatingSystem).Caption")
    if output:
        return output.splitlines()[0].strip()

    return None


def get_activation_status():
    try:
        client = get_wmi()
        if not client:
            return None
        for license_item in client.SoftwareLicensingProduct():
            if license_item.PartialProductKey:
                return "activated" if license_item.LicenseStatus == 1 else "not_activated"
    except Exception:
        pass
    return None


def get_pending_update():
    if os.name != "nt":
        return None

    try:
        command = [
            "powershell",
            "-NoProfile",
            "-WindowStyle",
            "Hidden",
            "-Command",
            "(New-Object -ComObject Microsoft.Update.Session).CreateUpdateSearcher().Search('IsInstalled=0').Updates.Count",
        ]

        result = subprocess.run(
            command,
            capture_output=True,
            text=True,
            timeout=30,
            creationflags=getattr(subprocess, "CREATE_NO_WINDOW", 0),
        )
        return int((result.stdout or "").strip())
    except Exception as exc:
        log(f"Pending update check failed: {exc}")
        return None


def map_windows_release(build):
    try:
        build_number = int(str(build).split(".")[-1])
    except Exception:
        return None

    mapping = {
        19041: "2004",
        19042: "20H2",
        19043: "21H1",
        19044: "21H2",
        19045: "22H2",
        22000: "21H2",
        22621: "22H2",
        22631: "23H2",
        26100: "24H2",
    }
    return mapping.get(build_number, "unknown")


def should_check(key, hours):
    try:
        last = float(cfg.get(key, 0) or 0)
    except Exception:
        last = 0

    now = time.time()
    if now - last >= hours * 3600:
        cfg[key] = now
        save_config()
        return True
    return False


def _run_hidden_powershell(script, timeout=20):
    if os.name != "nt":
        return None

    try:
        result = subprocess.run(
            ["powershell", "-NoProfile", "-ExecutionPolicy", "Bypass", "-Command", script],
            capture_output=True,
            text=True,
            timeout=timeout,
            creationflags=getattr(subprocess, "CREATE_NO_WINDOW", 0),
        )
        output = (result.stdout or "").strip()
        if result.returncode != 0:
            error_message = (result.stderr or output or "").strip()
            if error_message:
                log(f"PowerShell fallback error: {error_message[:240]}")
            if not output:
                return None
        return output
    except Exception as exc:
        log(f"PowerShell fallback failed: {exc}")
        return None


def _run_hidden_powershell_json(script, timeout=20):
    output = _run_hidden_powershell(script, timeout=timeout)
    if not output:
        return []

    try:
        parsed = json.loads(output)
    except Exception as exc:
        log(f"PowerShell JSON parse failed: {exc}")
        return []

    if isinstance(parsed, dict):
        return [parsed]
    if isinstance(parsed, list):
        return parsed
    return []


def _normalize_manufacturer(value):
    manufacturer = str(value or "").strip()
    if not manufacturer or manufacturer == "00000000":
        return "Unknown"
    return manufacturer


def _normalize_ram_slot(raw):
    if not isinstance(raw, dict):
        return None

    raw_size = raw.get("size_gb")
    if raw_size is None:
        raw_size = raw.get("Capacity") or raw.get("capacity")

    try:
        size_gb = round(int(raw_size) / (1024**3)) if raw_size else None
    except Exception:
        size_gb = None

    try:
        speed = int(raw.get("speed", raw.get("Speed", 0)) or 0)
    except Exception:
        speed = 0

    slot = {
        "size_gb": size_gb,
        "manufacturer": _normalize_manufacturer(raw.get("manufacturer", raw.get("Manufacturer"))),
        "speed": speed,
    }

    if slot["size_gb"] is None and slot["manufacturer"] == "Unknown" and slot["speed"] == 0:
        return None

    return slot


def _normalize_disk_entry(raw):
    if not isinstance(raw, dict):
        return None

    model = str(raw.get("model") or raw.get("Model") or "").strip()
    raw_size = raw.get("size_gb")
    if raw_size is None:
        raw_size = raw.get("Size") or raw.get("size")

    try:
        size_gb = round(int(raw_size) / (1024**3)) if raw_size else None
    except Exception:
        size_gb = None

    if not model and size_gb is None:
        return None

    return {
        "model": model or "Disk",
        "size_gb": size_gb,
    }


def get_ram_slots():
    slots = []

    try:
        client = get_wmi()
        if client:
            for module in client.Win32_PhysicalMemory():
                normalized = _normalize_ram_slot(
                    {
                        "Capacity": getattr(module, "Capacity", None),
                        "Manufacturer": getattr(module, "Manufacturer", None),
                        "Speed": getattr(module, "Speed", None),
                    }
                )
                if normalized:
                    slots.append(normalized)
    except Exception as exc:
        log(f"RAM error: {exc}")

    if slots:
        return slots

    fallback_slots = _run_hidden_powershell_json(
        "@(Get-CimInstance Win32_PhysicalMemory | Select-Object Manufacturer, Speed, @{Name='Capacity';Expression={[int64]$_.Capacity}}) | ConvertTo-Json -Compress"
    )
    for module in fallback_slots:
        normalized = _normalize_ram_slot(module)
        if normalized:
            slots.append(normalized)

    if slots:
        log("RAM detail loaded via PowerShell fallback")

    return slots


def get_disks():
    disks = []

    try:
        client = get_wmi()
        if client:
            for disk in client.Win32_DiskDrive():
                normalized = _normalize_disk_entry(
                    {
                        "Model": getattr(disk, "Model", None),
                        "Size": getattr(disk, "Size", None),
                    }
                )
                if normalized:
                    disks.append(normalized)
    except Exception as exc:
        log(f"Disk error: {exc}")

    if disks:
        return disks

    fallback_disks = _run_hidden_powershell_json(
        "@(Get-CimInstance Win32_DiskDrive | Select-Object Model, @{Name='Size';Expression={[int64]$_.Size}}) | ConvertTo-Json -Compress"
    )
    for disk in fallback_disks:
        normalized = _normalize_disk_entry(disk)
        if normalized:
            disks.append(normalized)

    if disks:
        log("Disk detail loaded via PowerShell fallback")

    return disks

def get_hardware_cached():
    global HARDWARE_CACHE
    if HARDWARE_CACHE is not None:
        return HARDWARE_CACHE

    HARDWARE_CACHE = {
        "ram_slots": get_ram_slots(),
        "disks": get_disks(),
    }
    log("Hardware profile cached")
    return HARDWARE_CACHE


def post_with_fallback(payload):
    if not session:
        log("requests module unavailable")
        return None

    for url in [LAN_URL, PUBLIC_URL]:
        if not url:
            continue
        try:
            response = session.post(url, json=payload, timeout=7)
            log(f"POST {url} -> {response.status_code}")
            if not response.ok:
                continue
            try:
                return response.json()
            except Exception:
                log(f"POST {url} returned non-JSON payload")
        except Exception as exc:
            log(f"POST fail {url}: {exc}")
    return None


def _get_command_urls():
    urls = []

    if COMMAND_URL:
        urls.append(str(COMMAND_URL).strip())

    for heartbeat_url in [LAN_URL, PUBLIC_URL]:
        derived = _derive_command_url(heartbeat_url)
        if derived:
            urls.append(derived)

    deduped = []
    seen = set()
    for url in urls:
        if not url or url in seen:
            continue
        seen.add(url)
        deduped.append(url)

    return deduped


def poll_command():
    global DEVICE_TOKEN

    if not session:
        return None

    payload = {
        "device_token": DEVICE_TOKEN,
        "hostname": platform.node(),
        "mac": get_mac_address(),
        "agent_version": AGENT_VERSION,
        "command_poll_interval": COMMAND_POLL_INTERVAL,
    }
    changed = False

    for url in _get_command_urls():
        try:
            response = session.post(url, json=payload, timeout=5)
            log(f"CMD {url} -> {response.status_code}")
            if not response.ok:
                continue

            try:
                data = response.json()
            except Exception:
                log(f"CMD {url} returned non-JSON payload")
                continue

            if not isinstance(data, dict):
                continue

            new_token = str(data.get("device_token") or "").strip()
            if new_token and new_token != DEVICE_TOKEN:
                DEVICE_TOKEN = new_token
                cfg["device_token"] = new_token
                changed = True

            interval_from_server = data.get("interval") or data.get("heartbeat_interval")
            if interval_from_server:
                _set_interval(interval_from_server)
            command_poll_interval = data.get("command_poll_interval")
            if command_poll_interval:
                _set_command_poll_interval(command_poll_interval)
            elif changed:
                save_config()

            command = data.get("command")
            if command:
                log(f"Command poll received: {command}")
                return command

            return None
        except Exception as exc:
            log(f"CMD poll fail {url}: {exc}")

    if changed:
        save_config()

    return None


def _get_update_urls():
    urls = []

    if UPDATE_URL:
        urls.append(str(UPDATE_URL).strip())

    for heartbeat_url in [LAN_URL, PUBLIC_URL]:
        derived = _derive_update_url(heartbeat_url)
        if derived:
            urls.append(derived)

    deduped = []
    seen = set()
    for url in urls:
        if not url or url in seen:
            continue
        seen.add(url)
        deduped.append(url)

    return deduped


def _set_interval(seconds):
    global INTERVAL

    try:
        parsed = int(seconds)
    except Exception:
        log(f"Invalid interval command payload: {seconds}")
        return

    parsed = max(10, min(86400, parsed))
    INTERVAL = parsed
    cfg["interval"] = parsed
    save_config()
    log(f"Heartbeat interval updated to {parsed}s")


def _set_command_poll_interval(seconds):
    global COMMAND_POLL_INTERVAL

    try:
        parsed = int(seconds)
    except Exception:
        log(f"Invalid command poll interval payload: {seconds}")
        return

    parsed = max(5, min(3600, parsed))
    COMMAND_POLL_INTERVAL = parsed
    cfg["command_poll_interval"] = parsed
    save_config()
    log(f"Command poll interval updated to {parsed}s")


def _restart_agent_process():
    try:
        if getattr(sys, "frozen", False):
            subprocess.Popen([sys.executable], close_fds=True)
            os._exit(0)
        os.execl(sys.executable, sys.executable, *sys.argv)
    except Exception as exc:
        log(f"Restart agent failed: {exc}")


def _run_windows_shutdown(arguments):
    try:
        subprocess.Popen(
            ["shutdown", *arguments],
            creationflags=getattr(subprocess, "CREATE_NO_WINDOW", 0),
            close_fds=True,
        )
    except Exception as exc:
        log(f"Shutdown command failed: {exc}")


def _lock_workstation():
    if os.name != "nt":
        return

    try:
        import ctypes

        result = ctypes.windll.user32.LockWorkStation()
        if result:
            log("Lock workstation triggered via WinAPI")
            return
        log("LockWorkStation WinAPI returned 0, fallback to rundll32")
    except Exception as exc:
        log(f"Lock workstation WinAPI failed: {exc}")

    try:
        subprocess.Popen(
            ["rundll32.exe", "user32.dll,LockWorkStation"],
            creationflags=getattr(subprocess, "CREATE_NO_WINDOW", 0),
            close_fds=True,
        )
        log("Lock workstation triggered via rundll32")
    except Exception as exc:
        log(f"Lock workstation fallback failed: {exc}")


def execute_command(command_payload):
    command_name = command_payload
    command_args = {}

    if isinstance(command_payload, dict):
        command_name = command_payload.get("name") or command_payload.get("command")
        command_args = command_payload.get("args") or {}

    if not command_name:
        return None

    command_name = str(command_name).strip().lower()
    log(f"Execute command: {command_name}")

    if command_name in {"restart", "restart_os"}:
        _run_windows_shutdown(["/r", "/t", "0", "/f"])
        return command_name

    if command_name in {"shutdown", "shutdown_os"}:
        _run_windows_shutdown(["/s", "/t", "0", "/f"])
        return command_name

    if command_name in {"lock", "lock_screen", "lock_workstation", "lock_pc", "lock_device"}:
        _lock_workstation()
        return command_name

    if command_name in {"update", "push_update", "agent_update"}:
        direct_url = None
        direct_version = None

        if isinstance(command_args, dict):
            direct_url = command_args.get("url")
            direct_version = command_args.get("version")

        if direct_url is None and isinstance(command_payload, dict):
            direct_url = command_payload.get("url")
            direct_version = command_payload.get("version") or direct_version

        check_update(force=True, direct_url=direct_url, direct_version=direct_version)
        return command_name

    if command_name in {"restart_agent", "app_restart"}:
        _restart_agent_process()
        return command_name

    if command_name in {"set_interval", "heartbeat_interval"}:
        seconds = command_args.get("seconds") if isinstance(command_args, dict) else None
        if seconds is None and isinstance(command_payload, dict):
            seconds = command_payload.get("seconds")
        _set_interval(seconds)
        return command_name

    if command_name in {"set_command_poll_interval", "command_poll_interval"}:
        seconds = command_args.get("seconds") if isinstance(command_args, dict) else None
        if seconds is None and isinstance(command_payload, dict):
            seconds = command_payload.get("seconds")
        _set_command_poll_interval(seconds)
        return command_name

    if command_name in {"sync", "sync_now"}:
        log("Sync command received")
        send()
        return command_name

    log(f"Unknown command ignored: {command_name}")
    return command_name


def _download_file(url):
    if not session:
        raise RuntimeError("requests module unavailable")

    parsed = urlparse(url)
    file_name = os.path.basename(parsed.path) or "YHSClient_update.exe"
    if "." not in file_name:
        file_name += ".exe"
    temp_path = os.path.join(tempfile.gettempdir(), file_name)

    response = session.get(url, stream=True, timeout=30)
    response.raise_for_status()

    with open(temp_path, "wb") as handle:
        for chunk in response.iter_content(8192):
            if chunk:
                handle.write(chunk)
    return temp_path


def _can_write_to(path):
    try:
        test_file = path + ".write-test"
        with open(test_file, "w", encoding="utf8") as handle:
            handle.write("ok")
        os.remove(test_file)
        return True
    except Exception:
        return False


def _start_file(path):
    if os.name == "nt":
        os.startfile(path)
        return
    subprocess.Popen([path], close_fds=True)


def _launch_self_replace(downloaded_path):
    target_exe = sys.executable
    updater_script = os.path.join(
        tempfile.gettempdir(),
        f"yhs_updater_{int(time.time())}.cmd",
    )

    script_text = (
        "@echo off\r\n"
        "ping 127.0.0.1 -n 3 > nul\r\n"
        f"move /Y \"{downloaded_path}\" \"{target_exe}\" > nul\r\n"
        f"start \"\" \"{target_exe}\"\r\n"
        "del \"%~f0\"\r\n"
    )

    with open(updater_script, "w", encoding="utf8") as handle:
        handle.write(script_text)

    subprocess.Popen(
        ["cmd", "/c", updater_script],
        creationflags=getattr(subprocess, "CREATE_NO_WINDOW", 0),
        close_fds=True,
    )


def download_and_replace(url, version):
    global AGENT_VERSION

    try:
        if not url:
            return

        log(f"Downloading update from {url}")
        downloaded_path = _download_file(url)
        AGENT_VERSION = str(version or AGENT_VERSION)
        cfg["agent_version"] = AGENT_VERSION
        save_config()

        if not getattr(sys, "frozen", False):
            log("Dev mode detected, launching downloaded update package directly")
            _start_file(downloaded_path)
            return

        install_dir_writable = _can_write_to(os.path.join(INSTALL_DIR, "yhsclient"))
        extension = os.path.splitext(downloaded_path)[1].lower()

        if not install_dir_writable:
            log("Install dir not writable, launching update package directly")
            _start_file(downloaded_path)
            os._exit(0)

        if extension in {".msi"}:
            log("MSI update package detected, launching installer")
            _start_file(downloaded_path)
            os._exit(0)

        _launch_self_replace(downloaded_path)
        os._exit(0)
    except Exception as exc:
        log(f"Update failed: {exc}")


def check_update(force=False, direct_url=None, direct_version=None):
    global AGENT_VERSION

    if not session:
        return

    if direct_url:
        download_and_replace(direct_url, direct_version or AGENT_VERSION)
        return

    urls = _get_update_urls()
    if not urls:
        if force:
            log("No update endpoint configured")
        return

    payload = {
        "device_token": DEVICE_TOKEN,
        "agent_version": AGENT_VERSION,
        "client_arch": get_client_arch(),
        "os_arch": get_os_arch(),
        "hostname": platform.node(),
        "mac": get_mac_address(),
    }

    for url in urls:
        try:
            response = session.post(url, json=payload, timeout=7)
            log(f"Update check {url} -> {response.status_code}")
            if not response.ok:
                continue

            try:
                data = response.json()
            except Exception:
                log(f"Update response non-JSON from {url}")
                continue

            if isinstance(data, dict) and data.get("update"):
                download_and_replace(data.get("url"), data.get("version"))
            return
        except Exception as exc:
            log(f"Update check fail {url}: {exc}")


def ensure_startup():
    startup_enabled = bool(cfg.get("startup", STARTUP_ENABLED))

    try:
        startup_dir = os.path.join(
            os.environ["APPDATA"],
            r"Microsoft\Windows\Start Menu\Programs\Startup",
        )
        shortcut_path = os.path.join(startup_dir, "YHSClient.lnk")

        if not startup_enabled:
            if os.path.exists(shortcut_path):
                os.remove(shortcut_path)
                log("Startup shortcut removed")
            return

        if os.path.exists(shortcut_path):
            return

        from win32com.client import Dispatch

        shell = Dispatch("WScript.Shell")
        shortcut = shell.CreateShortCut(shortcut_path)

        if getattr(sys, "frozen", False):
            shortcut.Targetpath = sys.executable
            shortcut.WorkingDirectory = os.path.dirname(sys.executable)
        else:
            shortcut.Targetpath = sys.executable
            shortcut.Arguments = f"\"{os.path.abspath(os.path.join(INSTALL_DIR, 'agent.py'))}\""
            shortcut.WorkingDirectory = INSTALL_DIR

        shortcut.save()
        log("Startup shortcut created")
    except Exception as exc:
        log(f"Startup setup failed: {exc}")


def _memory_total_gb():
    if not psutil:
        return None
    try:
        return round(psutil.virtual_memory().total / (1024**3))
    except Exception:
        return None


def _cpu_usage_percent():
    if not psutil:
        return None
    try:
        return psutil.cpu_percent(interval=1)
    except Exception:
        return None


def _ram_usage_percent():
    if not psutil:
        return None
    try:
        return psutil.virtual_memory().percent
    except Exception:
        return None


def send():
    global DEVICE_TOKEN
    global INTERVAL

    info = system_info_cached()
    build = platform.version()

    pending_updates = None
    if should_check("last_patch_scan", 12):
        pending_updates = get_pending_update()

    payload = {
        "device_token": DEVICE_TOKEN,
        "agent_version": AGENT_VERSION,
        "heartbeat_interval": INTERVAL,
        "command_poll_interval": COMMAND_POLL_INTERVAL,
        "hostname": platform.node(),
        "device_user": getpass.getuser(),
        "serial_number": get_serial(),
        "lan_ip": get_ip(),
        "mac": get_mac_address(),
        "os": get_windows_name(),
        "os_version": platform.release(),
        "os_edition": get_windows_edition(),
        "os_build": build,
        "os_release": map_windows_release(build),
        "activation_status": get_activation_status(),
        "pending_updates": pending_updates,
        "ram_gb": _memory_total_gb(),
        "cpu_usage": _cpu_usage_percent(),
        "ram_usage": _ram_usage_percent(),
        "storage_gb": storage_total(),
        "storage_free": storage_free(),
        "disk_model": disk_model(),
        "hardware": get_hardware_cached(),
        "health": get_runtime_health(),
        "last_sync_status": cfg.get("last_sync_status"),
        "last_sync_at": cfg.get("last_sync_at"),
        **info,
    }

    response = post_with_fallback(payload)
    if not response:
        cfg["last_sync_status"] = "failed"
        cfg["last_sync_at"] = int(time.time())
        save_config()
        return

    cfg["last_sync_status"] = "ok"
    cfg["last_sync_at"] = int(time.time())

    if response.get("device_token"):
        DEVICE_TOKEN = response["device_token"]
        cfg["device_token"] = DEVICE_TOKEN

    profile = response.get("client_profile")
    if isinstance(profile, dict):
        cfg["client_profile"] = profile

    interval_from_server = response.get("interval") or response.get("heartbeat_interval")
    if interval_from_server:
        _set_interval(interval_from_server)
    command_poll_interval = response.get("command_poll_interval")
    if command_poll_interval:
        _set_command_poll_interval(command_poll_interval)
    else:
        save_config()

    command = response.get("command")
    if command:
        log(f"Command received: {command}")
        execute_command(command)

    if should_check("last_agent_update_check", 24):
        check_update()


def get_client_dashboard_data():
    profile = cfg.get("client_profile") if isinstance(cfg.get("client_profile"), dict) else {}
    asset = profile.get("asset") if isinstance(profile.get("asset"), dict) else {}
    assignment = profile.get("assignment") if isinstance(profile.get("assignment"), dict) else {}
    profile_hardware = profile.get("hardware") if isinstance(profile.get("hardware"), dict) else {}
    local_hardware = get_hardware_cached()
    local_ram_slots = local_hardware.get("ram_slots") if isinstance(local_hardware.get("ram_slots"), list) else []
    local_disks = local_hardware.get("disks") if isinstance(local_hardware.get("disks"), list) else []
    hardware = local_hardware if (local_ram_slots or local_disks) else profile_hardware
    system_static = system_info_cached()

    cpu_usage = _cpu_usage_percent()
    if cpu_usage is None:
        cpu_usage = profile.get("cpu_usage")

    ram_usage = _ram_usage_percent()
    if ram_usage is None:
        ram_usage = profile.get("ram_usage")

    total_storage = storage_total()
    if total_storage is None:
        total_storage = profile.get("storage_total_gb")
    if total_storage is None:
        total_storage = profile.get("storage_gb")

    free_storage = storage_free()
    if free_storage is None:
        free_storage = profile.get("storage_free_gb")
    if free_storage is None:
        free_storage = profile.get("storage_free")

    ram_total = _memory_total_gb()
    if ram_total is None:
        ram_total = profile.get("ram_gb")

    storage_used_percent = None
    if total_storage and free_storage is not None:
        try:
            storage_used_percent = max(0, min(100, ((float(total_storage) - float(free_storage)) / float(total_storage)) * 100))
        except Exception:
            storage_used_percent = None

    return {
        "hostname": platform.node(),
        "device_user": getpass.getuser(),
        "device_token": DEVICE_TOKEN or cfg.get("device_token"),
        "agent_version": AGENT_VERSION,
        "heartbeat_interval": int(INTERVAL),
        "command_poll_interval": int(COMMAND_POLL_INTERVAL),
        "sync_status": cfg.get("last_sync_status") or "unknown",
        "sync_at": cfg.get("last_sync_at"),
        "client_ip": get_ip(),
        "server_ip": profile.get("server_ip"),
        "os": get_windows_name(),
        "os_version": platform.release(),
        "os_edition": get_windows_edition(),
        "cpu_name": system_static.get("cpu_name") or profile.get("cpu_name"),
        "cpu_core": system_static.get("cpu_core") or profile.get("cpu_core"),
        "cpu_thread": system_static.get("cpu_thread") or profile.get("cpu_thread"),
        "gpu": system_static.get("gpu") or profile.get("gpu"),
        "ram_gb": ram_total,
        "storage_total_gb": total_storage,
        "storage_free_gb": free_storage,
        "cpu_usage": cpu_usage,
        "ram_usage": ram_usage,
        "storage_used_percent": storage_used_percent,
        "asset": asset,
        "assignment": assignment,
        "hardware": hardware,
        "pending_updates": profile.get("pending_updates"),
        "activation_status": profile.get("activation_status"),
        "last_seen": profile.get("last_seen"),
    }


log("Agent core initialized")
ensure_startup()



