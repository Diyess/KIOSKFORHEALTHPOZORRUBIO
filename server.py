"""
HealthKiosk — Unified WebSocket Server
========================================
Run this ONCE:
    python3 server.py

WebSocket:
    ws://0.0.0.0:8765
"""

import asyncio
import json
import os
import sys
import subprocess
import csv
import statistics
import time
import threading
import base64
import traceback
import websockets

import RPi.GPIO as GPIO

# ─────────────────────────────────────────────────────────────
# CONFIG
# ─────────────────────────────────────────────────────────────
HOST = "0.0.0.0"
PORT = 8765

BUZZER_PIN = 17

SENSOR_DIR = os.path.join(
    os.path.dirname(__file__),
    "SENSORS"
)

PYTHON = sys.executable

# ─────────────────────────────────────────────────────────────
# SOUND / MUSIC
# ─────────────────────────────────────────────────────────────
# Put your audio files (mp3 or wav) inside this folder.
SOUND_DIR = os.path.join(
    os.path.dirname(__file__),
    "SOUNDS"
)

SOUND_FACE_SUCCESS = "face_success.mp3"
SOUND_OXIMETER_SUCCESS = "oximeter_success.mp3"
SOUND_SCANID_SUCCESS = "scanid_success.mp3"
SOUND_BP_SUCCESS = "bp_success.mp3"


def _play_sound_blocking(filename: str):
    """Blocking helper — actually plays the file. Run this in an executor."""

    path = os.path.join(SOUND_DIR, filename)

    if not os.path.exists(path):
        print(f"[!] Sound file not found: {path}")
        return

    try:

        if path.lower().endswith(".wav"):
            subprocess.run(
                ["aplay", "-q", path],
                check=False
            )
        else:
            # mp3 / other — needs mpg123 installed (sudo apt install mpg123)
            subprocess.run(
                ["mpg123", "-q", path],
                check=False
            )

    except FileNotFoundError:
        print("[!] No audio player found (need 'aplay' for .wav or 'mpg123' for .mp3).")

    except Exception as e:
        print(f"[!] Failed to play sound {filename}: {e}")


async def play_sound(filename: str):
    """Non-blocking wrapper so playing audio doesn't stall the event loop."""

    loop = asyncio.get_event_loop()

    await loop.run_in_executor(None, _play_sound_blocking, filename)

# ─────────────────────────────────────────────────────────────
# CLIENTS
# ─────────────────────────────────────────────────────────────
CLIENTS = set()

# ─────────────────────────────────────────────────────────────
# BROADCAST
# ─────────────────────────────────────────────────────────────
async def broadcast(data: dict):

    msg = json.dumps(data)

    dead = set()

    for ws in CLIENTS:

        try:
            await ws.send(msg)

        except Exception:
            dead.add(ws)

    CLIENTS.difference_update(dead)

# ─────────────────────────────────────────────────────────────
# HELPERS
# ─────────────────────────────────────────────────────────────
# HELPER
async def buzz():

    GPIO.setwarnings(False)
    GPIO.setmode(GPIO.BCM)
    GPIO.setup(BUZZER_PIN, GPIO.OUT)

    for _ in range(3):
        GPIO.output(BUZZER_PIN, GPIO.HIGH)
        await asyncio.sleep(0.08)
        GPIO.output(BUZZER_PIN, GPIO.LOW)
        await asyncio.sleep(0.05)

def sensor_path(name: str):

    return os.path.join(
        SENSOR_DIR,
        name
    )

# ─────────────────────────────────────────────────────────────
# RUN SENSOR SCRIPT
# ─────────────────────────────────────────────────────────────
async def run_sensor_script(script: str, ws):

    path = sensor_path(script)

    if not os.path.exists(path):

        await ws.send(json.dumps({
            "error": f"Script not found: {script}"
        }))

        return

    proc = await asyncio.create_subprocess_exec(
        PYTHON,
        path,
        stdout=asyncio.subprocess.PIPE,
        stderr=asyncio.subprocess.PIPE,
    )

    try:

        async for raw in proc.stdout:

            line = raw.decode().strip()

            if not line:
                continue

            try:

                data = json.loads(line)

                await ws.send(json.dumps(data))

            except json.JSONDecodeError:

                await ws.send(json.dumps({
                    "status": line
                }))

    finally:

        proc.kill()

        await proc.wait()

# ─────────────────────────────────────────────────────────────
# WEIGHT (REAL HX711 LOAD CELL)
# ─────────────────────────────────────────────────────────────
async def handle_weight(ws):

    await ws.send(json.dumps({
        "status": "Initializing system..."
    }))

    try:

        import RPi.GPIO as GPIO
        from hx711 import HX711

        # GPIO SETUP
        GPIO.setwarnings(False)
        GPIO.setmode(GPIO.BCM)

        DATA_PIN = 5
        CLOCK_PIN = 6

        # HX711 INIT
        hx = HX711(
            dout_pin=DATA_PIN,
            pd_sck_pin=CLOCK_PIN
        )

        # Reduced settle time for faster startup
        await asyncio.sleep(0.5)

        # ─────────────────────────────────────────
        # TARE FUNCTION
        # ─────────────────────────────────────────
        await ws.send(json.dumps({
            "status": "Taring... Make sure NO weight is on the scale."
        }))

        # Reduced tare wait for faster startup
        await asyncio.sleep(0.5)

        loop = asyncio.get_event_loop()

        readings = await loop.run_in_executor(
            None,
            lambda: hx.get_raw_data(times=25)
        )

        offset = statistics.mean(readings)

        await ws.send(json.dumps({
            "status": f"Tare complete. Offset: {round(offset, 2)}"
        }))

        # CALIBRATION FACTOR
        scale_factor = 23000

        await ws.send(json.dumps({
            "status": "System ready. Step on the scale with BOTH feet."
        }))

        # ─────────────────────────────────────────
        # WAIT FOR WEIGHT (faster polling)
        # ─────────────────────────────────────────
        while True:

            raw = await loop.run_in_executor(
                None,
                lambda: hx.get_raw_data(times=5)
            )

            avg = statistics.mean(raw)

            weight = (offset - avg) / scale_factor

            # Someone stepped on scale
            if weight > 5:

                await ws.send(json.dumps({
                    "status": "Weight detected. Capturing..."
                }))

                break

            await asyncio.sleep(0.15)

        # ─────────────────────────────────────────
        # CAPTURE WINDOW — 5 SECONDS ONLY
        # Collect samples for a fixed 5s window instead of
        # looping indefinitely until a strict deviation is hit.
        # ─────────────────────────────────────────
        samples = []

        capture_start = time.time()

        while time.time() - capture_start < 5:

            raw_data = await loop.run_in_executor(
                None,
                lambda: hx.get_raw_data(times=3)
            )

            val = statistics.mean(raw_data)

            weight = (offset - val) / scale_factor

            # Human weight filter
            if 5 <= weight <= 300:
                samples.append(weight)

            await asyncio.sleep(0.05)

        if len(samples) < 3:

            await ws.send(json.dumps({
                "status": "error",
                "message": "Could not get a stable reading. Please try again."
            }))

            GPIO.cleanup()

            return

        # ─────────────────────────────────────────
        # FINAL WEIGHT — median is robust to the odd
        # noisy sample without needing more time to settle
        # ─────────────────────────────────────────
        final_weight = statistics.median(samples)

        if final_weight < 0:
            final_weight = 0

        final_weight = round(final_weight, 1)

        await ws.send(json.dumps({
            "status": "Done",
            "weight": final_weight
        }))

        await buzz()

        GPIO.cleanup()

    except Exception as e:

        traceback.print_exc()

        await ws.send(json.dumps({
            "status": "error",
            "message": str(e)
        }))

# ─────────────────────────────────────────────────────────────
# HEIGHT (REAL HC-SR04 SENSOR)
# ─────────────────────────────────────────────────────────────
async def handle_height(ws):

    await ws.send(json.dumps({
        "status": "Initializing height sensor..."
    }))

    try:

        import RPi.GPIO as GPIO

        # GPIO SETUP
        TRIG = 23
        ECHO = 24

        GPIO.setwarnings(False)
        GPIO.setmode(GPIO.BCM)

        GPIO.setup(TRIG, GPIO.OUT)
        GPIO.setup(ECHO, GPIO.IN)

        GPIO.output(TRIG, False)

        # Sensor reference height
        SENSOR_HEIGHT = 210.0

        # Reduced settle time for faster startup
        await asyncio.sleep(0.5)

        # ─────────────────────────────────────────
        # GET DISTANCE FUNCTION
        # ─────────────────────────────────────────
        def get_distance():

            GPIO.output(TRIG, False)
            time.sleep(0.05)

            GPIO.output(TRIG, True)
            time.sleep(0.00001)
            GPIO.output(TRIG, False)

            timeout = time.time() + 0.03

            while GPIO.input(ECHO) == 0:

                pulse_start = time.time()

                if pulse_start > timeout:
                    return None

            pulse_end = time.time()

            while GPIO.input(ECHO) == 1:

                pulse_end = time.time()

                if pulse_end - pulse_start > 0.03:
                    return None

            pulse_duration = pulse_end - pulse_start

            distance = (pulse_duration * 34300) / 2

            return distance

        # ─────────────────────────────────────────
        # WAIT FOR PERSON — no fixed countdown, this
        # triggers the instant someone is in range
        # ─────────────────────────────────────────
        await ws.send(json.dumps({
            "status": "Waiting for person..."
        }))

        while True:

            dist = get_distance()

            if dist is not None:

                height = SENSOR_HEIGHT - dist

                if 50 <= height <= 250:
                    break

            await asyncio.sleep(0.1)

        await ws.send(json.dumps({
            "status": "Person detected. Hold still..."
        }))

        # ─────────────────────────────────────────
        # QUICK STABILIZATION WINDOW
        # Short capture window right after detection
        # instead of a long fixed countdown.
        # ─────────────────────────────────────────
        readings = []

        capture_start = time.time()

        while time.time() - capture_start < 1.5:

            dist = get_distance()

            if dist is not None:

                height = SENSOR_HEIGHT - dist

                # Human height filter
                if 50 <= height <= 250:

                    readings.append(height)

            await asyncio.sleep(0.1)

        # ─────────────────────────────────────────
        # RESULT
        # ─────────────────────────────────────────
        if len(readings) == 0:

            await ws.send(json.dumps({
                "status": "error",
                "message": "No height detected"
            }))

        else:

            final_height = round(max(readings), 1)

            await ws.send(json.dumps({
                "status": "Done",
                "height": final_height
            }))

            await buzz()
        GPIO.cleanup()

    except Exception as e:

        traceback.print_exc()

        await ws.send(json.dumps({
            "status": "error",
            "message": str(e)
        }))

# ─────────────────────────────────────────────────────────────
# TEMPERATURE (REAL K3+ SENSOR)
# ─────────────────────────────────────────────────────────────
async def handle_temperature(ws):

    await ws.send(json.dumps({
        "status": "Initializing K3+ temperature sensor..."
    }))

    try:

        import serial

        # Open serial connection
        ser = serial.Serial(
            port='/dev/serial0',
            baudrate=9600,
            timeout=1
        )

        await asyncio.sleep(2)

        # Clear old serial garbage
        ser.reset_input_buffer()

        await ws.send(json.dumps({
            "status": "Waiting for valid temperature..."
        }))

        temp = None

        while True:

            # Read serial line
            raw = ser.readline().decode(
                'utf-8',
                errors='ignore'
            ).strip()

            # Example expected:
            # @xxx,36.7,xxx#

            if raw.startswith('@') and raw.endswith('#'):

                parts = raw.split(',')

                if len(parts) >= 3:

                    try:

                        temp_val = float(parts[1])

                        # Human body range validation
                        if 30 <= temp_val <= 45:

                            temp = round(temp_val, 1)

                            break

                    except:
                        pass

            await asyncio.sleep(0.1)

        ser.close()

        await ws.send(json.dumps({
            "status": "done",
            "temp": temp
        }))

        await buzz()
    except Exception as e:

        traceback.print_exc()

        await ws.send(json.dumps({
            "status": "error",
            "message": str(e)
        }))

# ─────────────────────────────────────────────────────────────
# OXIMETER (REAL MAX30102-STYLE SENSOR — BPM + SpO2)
# ─────────────────────────────────────────────────────────────
# ─────────────────────────────────────────────────────────────
# OXIMETER (REAL MAX30102-STYLE SENSOR — BPM + SpO2)
# Based on standalone oximeter.py
# ─────────────────────────────────────────────────────────────

OX_ADDRESS = 0x57
OX_BUFFER_SIZE = 100
OX_READ_DURATION = 5  # seconds


async def handle_oximeter(ws):

    await ws.send(json.dumps({
        "status": "Initializing oximeter sensor..."
    }))

    bus = None

    try:
        from smbus2 import SMBus
        import numpy as np

        bus = SMBus(1)

        def write_reg(reg, value):
            bus.write_byte_data(OX_ADDRESS, reg, value)

        def read_fifo():
            data = bus.read_i2c_block_data(OX_ADDRESS, 0x07, 6)

            red = (data[0] << 16) | (data[1] << 8) | data[2]
            ir = (data[3] << 16) | (data[4] << 8) | data[5]

            return red, ir

        # -------- SENSOR INIT --------
        write_reg(0x09, 0x40)
        await asyncio.sleep(1)

        write_reg(0x02, 0xC0)
        write_reg(0x03, 0x00)
        write_reg(0x04, 0x00)

        write_reg(0x09, 0x03)
        write_reg(0x0A, 0x27)

        write_reg(0x0C, 0x1F)
        write_reg(0x0D, 0x1F)

        ir_buffer = []
        red_buffer = []

        # BPM
        last_peak_time = 0
        beat_intervals = []
        bpm = 0
        prev_bpm = 0

        # SpO2
        spo2 = 97
        prev_spo2 = spo2

        reading_started = False
        start_time = None

        await ws.send(json.dumps({
            "status": "Place your finger on the sensor"
        }))

        # -------- WAIT + READ --------
        while True:

            red, ir = read_fifo()
            current_time = time.time()

            ir_buffer.append(ir)
            red_buffer.append(red)

            if len(ir_buffer) > OX_BUFFER_SIZE:
                ir_buffer.pop(0)
                red_buffer.pop(0)

            if len(ir_buffer) < OX_BUFFER_SIZE:
                await asyncio.sleep(0.05)
                continue

            ir_np = np.array(ir_buffer)
            red_np = np.array(red_buffer)

            ir_mean = np.mean(ir_np)
            ir_std = np.std(ir_np)

            # -------- WAIT FOR FINGER --------
            if not reading_started:

                if ir_std < 500:
                    await ws.send(json.dumps({
                        "status": "Waiting for finger..."
                    }))

                    await asyncio.sleep(0.2)
                    continue

                # -------- FINGER DETECTED --------
                start_time = time.time()
                reading_started = True

                await ws.send(json.dumps({
                    "status": f"Finger detected! Reading started ({OX_READ_DURATION} seconds)..."
                }))

            # -------- DURING READING --------
            else:

                elapsed = current_time - start_time
                remaining = max(0, int(OX_READ_DURATION - elapsed))

                # -------- FINGER REMOVED --------
                if ir_std < 500:

                    await ws.send(json.dumps({
                        "status": "error",
                        "message": "Finger removed. Please try again."
                    }))

                    return

                # -------- SEND COUNTDOWN TO UI --------
                await ws.send(json.dumps({
                    "status": f"Reading... {remaining}s left",
                    "remaining": remaining,
                    "timer": remaining
                }))

                # -------- STOP AFTER 5 SECONDS --------
                if elapsed >= OX_READ_DURATION:
                    break

                # -------- SpO2 --------
                dc_ir = ir_mean
                dc_red = np.mean(red_np)

                ac_ir = np.max(ir_np) - np.min(ir_np)
                ac_red = np.max(red_np) - np.min(red_np)

                if ac_ir > 2000 and ac_red > 2000:

                    R = (ac_red / dc_red) / (ac_ir / dc_ir)

                    spo2 = 104 - (17 * R)

                    spo2 = max(92, min(100, spo2))

                    spo2 = (spo2 * 0.8) + (prev_spo2 * 0.2)

                    prev_spo2 = spo2

                # -------- BPM --------
                threshold = ir_mean + (ir_std * 0.8)

                if ir > threshold:

                    if current_time - last_peak_time > 0.7:

                        if last_peak_time != 0:

                            interval = current_time - last_peak_time

                            if 0.4 < interval < 1.5:

                                beat_intervals.append(interval)

                                if len(beat_intervals) > 5:
                                    beat_intervals.pop(0)

                                avg_interval = np.mean(beat_intervals)

                                bpm = 60 / avg_interval

                                bpm = (bpm * 0.7) + (prev_bpm * 0.3)

                                prev_bpm = bpm

                        last_peak_time = current_time

            await asyncio.sleep(0.05)

        # -------- SUCCESS RESULT --------
        final_bpm = int(max(55, min(110, bpm)))
        final_spo2 = int(spo2)

        await ws.send(json.dumps({
            "status": "done",
            "bpm": final_bpm,
            "spo2": final_spo2
        }))

        await buzz()
        await play_sound(SOUND_OXIMETER_SUCCESS)

    except Exception as e:

        traceback.print_exc()

        await ws.send(json.dumps({
            "status": "error",
            "message": str(e)
        }))

    finally:

        if bus is not None:
            bus.close()


# ─────────────────────────────────────────────────────────────
# BLOOD PRESSURE (OMRON HEM-7361T VIA omblepy / BLE)
# ─────────────────────────────────────────────────────────────
BP_MAC = "00:5F:BF:95:18:E0"
BP_OMBLEPY_DIR = "/home/pi/omblepy"
BP_CSV_FILE = "/home/pi/omblepy/user1.csv"
BP_PYTHON = "/home/pi/omblepy/venv/bin/python3"

async def handle_bloodpressure(ws):

    await ws.send(json.dumps({
        "status": "Connecting to OMRON HEM-7361T..."
    }))

    try:

        loop = asyncio.get_event_loop()

        def run_omblepy():

            return subprocess.run(
                [
                    BP_PYTHON,
                    "omblepy.py",
                    "-d", "HEM-7361T",
                    "-m", BP_MAC,
                    "-n"  # new records only, not full history -> faster
                ],
                cwd=BP_OMBLEPY_DIR,
                capture_output=True,
                text=True,
                timeout=60  # safety net in case the BLE connection hangs
            )

        start_time = time.time()

        try:

            result = await loop.run_in_executor(None, run_omblepy)

        except subprocess.TimeoutExpired:

            await ws.send(json.dumps({
                "status": "error",
                "message": "Timed out waiting for the BP monitor. Please try again."
            }))

            return

        elapsed = time.time() - start_time

        if result.returncode != 0:

            err_tail = result.stderr.strip()[-500:] if result.stderr else ""

            await ws.send(json.dumps({
                "status": f"omblepy did not exit cleanly ({elapsed:.1f}s)"
            }))

            if err_tail:

                await ws.send(json.dumps({
                    "status": f"Error details: {err_tail}"
                }))

        if not os.path.exists(BP_CSV_FILE):

            await ws.send(json.dumps({
                "status": "error",
                "message": "user1.csv was not found."
            }))

            return

        try:

            with open(BP_CSV_FILE, newline="") as f:

                rows = list(csv.DictReader(f))

        except Exception as e:

            await ws.send(json.dumps({
                "status": "error",
                "message": f"Could not read user1.csv: {e}"
            }))

            return

        await ws.send(json.dumps({
            "status": f"Total User 1 records: {len(rows)}"
        }))

        if not rows:

            await ws.send(json.dumps({
                "status": "error",
                "message": "No records found yet in user1.csv."
            }))

            return

        # Always take the LAST row — that is the latest reading —
        # and that is what gets sent to the UI.
        latest = rows[-1]

        required = ("datetime", "sys", "dia", "bpm")

        if not all(k in latest for k in required):

            await ws.send(json.dumps({
                "status": "error",
                "message": f"CSV row missing expected columns: {list(latest.keys())}"
            }))

            return

        await ws.send(json.dumps({
            "status": "done",
            "datetime": latest["datetime"],
            "systolic": latest["sys"],
            "diastolic": latest["dia"],
            "pulse": latest["bpm"]
        }))

        await buzz()
        await play_sound(SOUND_BP_SUCCESS)

    except Exception as e:

        traceback.print_exc()

        await ws.send(json.dumps({
            "status": "error",
            "message": str(e)
        }))

# ─────────────────────────────────────────────────────────────
# FACE CAPTURE
# Browser handles camera and face detection.
# Python handles buzzer and success sound.
# ─────────────────────────────────────────────────────────────
async def handle_face(ws):

    try:

        await ws.send(json.dumps({
            "status": "Photo captured."
        }))

        # BUZZER
        await buzz()

        # SUCCESS SOUND
        await play_sound(SOUND_FACE_SUCCESS)

        await ws.send(json.dumps({
            "status": "done"
        }))

    except Exception as e:

        traceback.print_exc()

        await ws.send(json.dumps({
            "status": "error",
            "message": str(e)
        }))

# ─────────────────────────────────────────────────────────────
# SCAN ID (REAL — PICAMERA2 + QR CODE ON NATIONAL ID)
# ─────────────────────────────────────────────────────────────
SCANID_TIMEOUT = 30  # seconds overall timeout
SCANID_SCAN_INTERVAL = 0.5


def _calculate_age(dob_string: str):

    try:

        from datetime import datetime

        dob = datetime.strptime(
            dob_string,
            "%B %d, %Y"
        )

        today = datetime.today()

        age = (
            today.year
            - dob.year
            - (
                (today.month, today.day)
                < (dob.month, dob.day)
            )
        )

        return age

    except Exception:
        return "N/A"


async def handle_scanid(ws):

    await ws.send(json.dumps({
        "status": "Initializing ID scanner..."
    }))

    picam2 = None

    try:

        from picamera2 import Picamera2
        import cv2

        picam2 = Picamera2()

        config = picam2.create_preview_configuration(
            main={
                "format": "RGB888",
                "size": (960, 540)
            }
        )

        picam2.configure(config)

        # Continuous autofocus
        picam2.set_controls({
            "AfMode": 2
        })

        picam2.start()

        await asyncio.sleep(2)

        qr_detector = cv2.QRCodeDetector()

        await ws.send(json.dumps({
            "status": "Show your ID's QR code to the camera"
        }))

        loop = asyncio.get_event_loop()

        loop_start = time.time()
        last_scan = 0
        info = None

        while time.time() - loop_start < SCANID_TIMEOUT:

            frame = await loop.run_in_executor(None, picam2.capture_array)

            data = ""

            # Scan every 0.5 second (matches original scan_interval)
            if time.time() - last_scan >= SCANID_SCAN_INTERVAL:

                data, points, _ = qr_detector.detectAndDecode(frame)

                last_scan = time.time()

            if data:

                try:

                    parsed = json.loads(data)

                    subject = parsed.get("subject", {})

                    fname = subject.get("fName", "")
                    mname = subject.get("mName", "")
                    lname = subject.get("lName", "")
                    suffix = subject.get("Suffix", "")

                    fullname = f"{fname} {mname} {lname} {suffix}".strip()

                    birthdate = subject.get("DOB", "")
                    sex = subject.get("sex", "")

                    age = _calculate_age(birthdate)

                    info = {
                        "type": "id_detected",
                        "fname": fname,
                        "mname": mname,
                        "lname": lname,
                        "suffix": suffix,
                        "fullname": fullname,
                        "dob": birthdate,
                        "sex": sex,
                        "age": age
                    }

                    break

                except Exception:
                    # QR code found but wasn't valid ID JSON — keep scanning
                    pass

            await asyncio.sleep(0.05)

        if info is None:

            await ws.send(json.dumps({
                "status": "error",
                "message": "No ID QR code detected. Please try again."
            }))

            return

        await buzz()
        await play_sound(SOUND_SCANID_SUCCESS)

        await ws.send(json.dumps(info))

    except Exception as e:

        traceback.print_exc()

        await ws.send(json.dumps({
            "status": "error",
            "message": str(e)
        }))

    finally:

        if picam2 is not None:

            try:
                picam2.stop()
            except Exception:
                pass

# ─────────────────────────────────────────────────────────────
# THERMAL PRINTER
# ─────────────────────────────────────────────────────────────
async def handle_print(ws, msg):

    try:

        PRINTER = "/dev/usb/lp0"

        ESC_INIT = b'\x1b\x40'

        CENTER = b'\x1b\x61\x01'
        LEFT = b'\x1b\x61\x00'

        BOLD_ON = b'\x1b\x45\x01'
        BOLD_OFF = b'\x1b\x45\x00'

        DOUBLE_ON = b'\x1d\x21\x11'
        DOUBLE_OFF = b'\x1d\x21\x00'

        QUAD_ON     = b'\x1d\x21\x33'

        NORMAL = b'\x1d\x21\x00'

        LINE_FEED = b'\x0a'

        CUT_PAPER = b'\x1d\x56\x41\x00'

        patient = msg.get("patient", {})
        vitals = msg.get("vitals", {})
        record = msg.get("record", {})
        queue = msg.get("queue", "--")

        with open(PRINTER, "wb") as p:

            p.write(ESC_INIT)

            # ==========================================================
            # HEADER
            # ==========================================================

            p.write(CENTER)

            p.write(BOLD_ON)
            p.write(b"HEALTHKIOSK\n")
            p.write(BOLD_OFF)

            p.write(b"Community Health Monitoring\n")
            p.write(b"System\n")
            p.write(b"Pozorrubio, Pangasinan\n")

            p.write(LINE_FEED)

            p.write(
                f"{record.get('date','--')}\n".encode()
            )

            p.write(BOLD_ON)

            p.write(
                f"{record.get('id','--')}\n".encode()
            )

            p.write(BOLD_OFF)

            p.write(b"--------------------------------\n")

            # ==========================================================
            # QUEUE NUMBER
            # ==========================================================

            p.write(CENTER)

            p.write(b"QUEUE NUMBER\n\n")

            p.write(QUAD_ON)
            p.write(
                f"{queue}\n".encode()
            )
            p.write(NORMAL)

            p.write(b"Please wait for your\n")
            p.write(b"number to be called\n")

            p.write(b"--------------------------------\n")

            # ==========================================================
            # PATIENT INFORMATION
            # ==========================================================

            p.write(CENTER)

            p.write(BOLD_ON)
            p.write(b"PATIENT INFORMATION\n")
            p.write(BOLD_OFF)

            p.write(LINE_FEED)

            p.write(LEFT)

            def row(label, value):

                label = str(label)
                value = str(value)

                text = f"{label:<12}{value}\n"
                p.write(text.encode())

            row(
                "AGE",
                patient.get("age", "--")
            )

            row(
                "GENDER",
                patient.get("gender", "--")
            )

            row(
                "BIRTHDAY",
                patient.get("dob", "--")
            )

            row(
                "PHONE",
                patient.get("phone", "--")
            )

            row(
                "BARANGAY",
                patient.get("barangay", "--")
            )

            row(
                "MUNICIPALITY",
                "Pozorrubio"
            )

            row(
                "PROVINCE",
                "Pangasinan"
            )

            p.write(b"--------------------------------\n")

            # ==========================================================
            # BODY MEASUREMENTS
            # ==========================================================

            p.write(CENTER)

            p.write(BOLD_ON)
            p.write(b"BODY MEASUREMENTS\n")
            p.write(BOLD_OFF)

            p.write(LINE_FEED)

            p.write(LEFT)

            row(
                "WEIGHT",
                f"{vitals.get('weight','--')} kg"
            )

            row(
                "HEIGHT",
                f"{vitals.get('height','--')} cm"
            )

            p.write(LINE_FEED)

            # ==========================================================
            # BMI
            # ==========================================================

            p.write(CENTER)

            p.write(b"BODY MASS INDEX (BMI)\n")

            p.write(DOUBLE_ON)

            p.write(
                f"{vitals.get('bmi','--')}\n".encode()
            )

            p.write(DOUBLE_OFF)

            p.write(b"kg/m2\n")

            p.write(b"--------------------------------\n")

            # ==========================================================
            # VITAL SIGNS
            # ==========================================================

            p.write(CENTER)

            p.write(BOLD_ON)
            p.write(b"VITAL SIGNS\n")
            p.write(BOLD_OFF)

            p.write(LINE_FEED)

            p.write(LEFT)

            row(
                "TEMP",
                f"{vitals.get('temp','--')} C"
            )

            row(
                "SpO2",
                f"{vitals.get('spo2','--')} %"
            )

            row(
                "PULSE RATE",
                f"{vitals.get('pulse','--')} bpm"
            )

            p.write(LINE_FEED)

            # ==========================================================
            # BLOOD PRESSURE
            # ==========================================================

            p.write(CENTER)

            p.write(b"BLOOD PRESSURE\n")

            p.write(DOUBLE_ON)

            p.write(
                f"{vitals.get('bp','--')}\n".encode()
            )

            p.write(DOUBLE_OFF)

            p.write(b"mmHg\n")

            p.write(b"--------------------------------\n")

            # ==========================================================
            # BARCODE ID (matches HTML text under barcode)
            # ==========================================================

            p.write(CENTER)

            rec_id = record.get("id", "--")

            # ==========================================================
            # FOOTER
            # ==========================================================

            p.write(CENTER)

            p.write(
                b"This record is generated\n"
            )

            p.write(
                b"by HealthKiosk.\n"
            )

            p.write(
                b"For medical advice,\n"
            )

            p.write(
                b"consult a licensed physician.\n"
            )

            p.write(
                f"Printed: {record.get('date','--')}\n".encode()
            )

            p.write(LINE_FEED)

            p.write(
                b"* * * * "
            )

            p.write(LINE_FEED * 5)

            p.write(CUT_PAPER)

        await ws.send(json.dumps({
            "type": "print_done"
        }))

    except Exception as e:

        traceback.print_exc()

        await ws.send(json.dumps({
            "type": "print_error",
            "message": str(e)
        }))
# ─────────────────────────────────────────────────────────────
# ACTION MAP
# ─────────────────────────────────────────────────────────────
ACTION_MAP = {
    "start_weight": handle_weight,
    "start_height": handle_height,
    "start_temp": handle_temperature,
    "start_oximeter": handle_oximeter,
    "start_bp": handle_bloodpressure,
    "start_face": handle_face,
    "scan_id": handle_scanid,
}

# ─────────────────────────────────────────────────────────────
# MAIN HANDLER
# ─────────────────────────────────────────────────────────────
async def handler(websocket):

    CLIENTS.add(websocket)

    print(f"[+] Client connected | total={len(CLIENTS)}")

    try:

        await websocket.send(json.dumps({
            "status": "Ready"
        }))

        async for raw in websocket:

            try:

                msg = json.loads(raw)

                action = msg.get("action", "")

                print(f"[→] Action: {action}")

                # PRINT RECEIPT
                if msg.get("type") == "print":

                    asyncio.create_task(
                        handle_print(websocket, msg)
                    )

                # SENSOR ACTIONS
                elif action in ACTION_MAP:

                    asyncio.create_task(
                        ACTION_MAP[action](websocket)
                    )

                else:

                    await websocket.send(json.dumps({
                        "error": f"Unknown action: {action}"
                    }))

            except json.JSONDecodeError:

                await websocket.send(json.dumps({
                    "error": "Invalid JSON"
                }))

            except Exception as e:

                traceback.print_exc()

                await websocket.send(json.dumps({
                    "error": str(e)
                }))

    except websockets.exceptions.ConnectionClosed:
        pass

    finally:

        CLIENTS.discard(websocket)

        print(f"[-] Client disconnected | total={len(CLIENTS)}")

# ─────────────────────────────────────────────────────────────
# ENTRY POINT
# ─────────────────────────────────────────────────────────────
async def main():

    print("=" * 50)
    print(" HealthKiosk WebSocket Server")
    print(f" ws://{HOST}:{PORT}")
    print("=" * 50)

    async with websockets.serve(
        handler,
        HOST,
        PORT
    ):

        await asyncio.Future()

if __name__ == "__main__":

    try:

        asyncio.run(main())

    except KeyboardInterrupt:

        print("\nServer stopped.")