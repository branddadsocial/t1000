#!/usr/bin/env python3
"""Park console hosts off-screen. Never Telegram, Chrome, or Cursor."""
import ctypes
from ctypes import wintypes

user32 = ctypes.windll.user32
SWP_NOSIZE = 0x0001
SWP_NOACTIVATE = 0x0010
HWND_BOTTOM = 1
SW_SHOWNOACTIVATE = 4

EnumWindowsProc = ctypes.WINFUNCTYPE(ctypes.c_bool, wintypes.HWND, wintypes.LPARAM)

SKIP = ("telegram", "bhgift", "google chrome", "cursor", "chatgpt", "movies")
CLASSES = (
    "ConsoleWindowClass",
    "CASCADIA_HOSTING_WINDOW_CLASS",
    "PseudoConsoleWindow",
)

def title_of(hwnd):
    buf = ctypes.create_unicode_buffer(512)
    user32.GetWindowTextW(hwnd, buf, 512)
    return buf.value

def class_of(hwnd):
    buf = ctypes.create_unicode_buffer(256)
    user32.GetClassNameW(hwnd, buf, 256)
    return buf.value

def park(hwnd, title):
    user32.ShowWindow(hwnd, SW_SHOWNOACTIVATE)
    user32.SetWindowPos(hwnd, HWND_BOTTOM, -2400, -2400, 0, 0, SWP_NOSIZE | SWP_NOACTIVATE)
    print("parked hwnd=%s title=%s" % (int(hwnd), title))

def cb(hwnd, lparam):
    if not user32.IsWindowVisible(hwnd):
        return True
    cls = class_of(hwnd)
    title = title_of(hwnd)
    low = title.lower()
    if any(s in low for s in SKIP):
        return True
    if cls in CLASSES or low.startswith("windows powershell") or low.startswith("administrator: windows powershell") or low.startswith("command prompt") or "windows terminal" in low:
        park(hwnd, title)
    return True

user32.EnumWindows(EnumWindowsProc(cb), 0)
