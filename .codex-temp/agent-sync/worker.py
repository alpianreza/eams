import time
import traceback

import agent_core


def _command_poll_interval():
    try:
        return max(10, float(getattr(agent_core, "COMMAND_POLL_INTERVAL", 10) or 10))
    except Exception:
        return 10.0


def _sync_interval():
    try:
        return max(10, float(getattr(agent_core, "INTERVAL", 600) or 600))
    except Exception:
        return 600.0


def worker_loop():
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
