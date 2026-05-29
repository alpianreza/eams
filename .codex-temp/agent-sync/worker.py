import atexit
import time
import traceback
import ctypes

import agent_core

_WORKER_MUTEX = None


def _command_poll_interval():
    try:
        return max(5, float(getattr(agent_core, "COMMAND_POLL_INTERVAL", 5) or 5))
    except Exception:
        return 5.0


def _sync_interval():
    try:
        return max(5, float(getattr(agent_core, "INTERVAL", 900) or 900))
    except Exception:
        return 900.0


def _release_single_instance():
    global _WORKER_MUTEX

    if _WORKER_MUTEX is None or agent_core.os.name != "nt":
        return

    try:
        ctypes.windll.kernel32.ReleaseMutex(_WORKER_MUTEX)
        ctypes.windll.kernel32.CloseHandle(_WORKER_MUTEX)
    except Exception:
        pass
    _WORKER_MUTEX = None


def _acquire_single_instance():
    global _WORKER_MUTEX

    if agent_core.os.name != "nt":
        return True

    try:
        kernel32 = ctypes.windll.kernel32
        mutex = kernel32.CreateMutexW(None, False, "Global\\YHSClientWorkerLoop")
        already_exists = kernel32.GetLastError() == 183
        if already_exists:
            try:
                kernel32.CloseHandle(mutex)
            except Exception:
                pass
            agent_core.log("Worker instance already running, skip duplicate start")
            return False

        _WORKER_MUTEX = mutex
        atexit.register(_release_single_instance)
        return True
    except Exception as exc:
        agent_core.log(f"Worker single-instance guard unavailable: {exc}")
        return True


def worker_loop():
    if not _acquire_single_instance():
        return

    agent_core.log("Worker started")

    next_sync_at = time.time()
    next_command_poll_at = time.time()

    while True:
        now = time.time()

        if now >= next_command_poll_at:
            try:
                command = agent_core.poll_command()
                if command:
                    agent_core.execute_command(command)
                    next_sync_at = min(next_sync_at, time.time() + _sync_interval())
            except Exception as e:
                agent_core.log(f"Command poll error: {e}")
                agent_core.log(traceback.format_exc())

            next_command_poll_at = time.time() + _command_poll_interval()
            now = time.time()

        if now >= next_sync_at:
            try:
                agent_core.send()
            except Exception as e:
                agent_core.log(f"Worker error: {e}")
                agent_core.log(traceback.format_exc())

            next_sync_at = time.time() + _sync_interval()
            now = time.time()
            if next_command_poll_at <= now:
                next_command_poll_at = now + _command_poll_interval()

        next_wakeup_at = min(next_sync_at, next_command_poll_at)
        time.sleep(max(1.0, next_wakeup_at - time.time()))
