#!/usr/bin/env python3
"""Park BrandDad work Chrome off-screen. Never foreground. Never kill."""
import ctypes
import sys

hwnd = int(sys.argv[1]) if len(sys.argv) > 1 else 197924
SW_SHOWNOACTIVATE = 4
SWP_NOSIZE = 0x0001
SWP_NOACTIVATE = 0x0010
HWND_BOTTOM = 1
user32 = ctypes.windll.user32
buf = ctypes.create_unicode_buffer(512)
user32.GetWindowTextW(hwnd, buf, 512)
print("hwnd=%s title=%s" % (hwnd, buf.value))
user32.ShowWindow(hwnd, SW_SHOWNOACTIVATE)
user32.SetWindowPos(
    hwnd,
    HWND_BOTTOM,
    -2400,
    -2400,
    0,
    0,
    SWP_NOSIZE | SWP_NOACTIVATE,
)
