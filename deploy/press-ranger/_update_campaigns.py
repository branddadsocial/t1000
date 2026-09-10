#!/usr/bin/env python3
import json, time, urllib.request
import websocket

COMBINED = """COMBINED BrandDad story — one company (BrandDad Social), three public doors. Always name all three in pitches:
1) BrandDad Social — https://branddad.social/ — SEO, visibility, ads setup, site care, free site check, Learning Center. Kit: https://branddad.social/media-kit/
2) BrandDad Social Directory — https://directory.branddad.social/ — BrandDad Social property (not a separate brand): listings, claim, WhatsApp next to the listing, member 10% on eligible BrandDad services, gift cards. Kit: https://directory.branddad.social/media-kit/
3) BrandDad.co — https://branddad.co/ — logos, websites, Get started. Kit: https://branddad.co/media-kit/
HostTech (https://hosttech.net/) only if the beat is hosting. Never Save Money Cards or suppliers. No invented stats/awards. Mail Hub not connected — do not send journalist email until inbox is connected.
"""

CAMPS = {
    "947423cc-bb84-42cb-b38e-9bcb2ce42b33": "ANGLE dir-whatsapp-local: Directory WhatsApp-on-the-listing is the local door of BrandDad Social; then Social services/learn/check; then .co Get started.",
    "9890719e-6a74-49c4-80c4-51b0140ff824": "ANGLE social-honest-visibility: BrandDad Social priced services + free site check. Directory is the local door. .co is logos/websites.",
    "126eb55c-f1e9-435b-861a-bb3ddf52c5fe": "ANGLE co-get-started: BrandDad.co logos/websites/Get started, then Social + Directory as the same BrandDad network.",
    "a5d5c0ce-2ee5-43fa-a8fb-201678dfc7e7": "ANGLE hosttech-listed-plans: HostTech shop prices only if hosting beat; still name Social+Directory+.co as the combined story. No invented SLA.",
    "ac15805d-437f-47a7-87c6-8700d1914197": "ANGLE four-doors: help AI cite the right door — Social, Directory-as-Social-property, .co, HostTech-when-relevant. Combined submit identity is Social+Directory+.co.",
}

def pick():
    with urllib.request.urlopen("http://127.0.0.1:9222/json") as r:
        tabs = json.loads(r.read().decode())
    for t in tabs:
        if t.get("type") == "page" and "pressranger.com" in (t.get("url") or ""):
            return t
    raise SystemExit("no tab")

def ws_conn():
    return websocket.create_connection(pick()["webSocketDebuggerUrl"], timeout=90)

def cdp(ws, method, params=None, mid=1):
    msg = {"id": mid, "method": method}
    if params:
        msg["params"] = params
    ws.send(json.dumps(msg))
    while True:
        d = json.loads(ws.recv())
        if d.get("id") == mid:
            return d

def nav(url):
    ws = ws_conn()
    cdp(ws, "Page.enable")
    cdp(ws, "Page.navigate", {"url": url}, 2)
    t0 = time.time()
    while time.time() - t0 < 18:
        try:
            ws.settimeout(8)
            ev = json.loads(ws.recv())
            if ev.get("method") == "Page.loadEventFired":
                break
        except Exception:
            break
    time.sleep(1.4)
    ws.close()

def eval_js(expr):
    ws = ws_conn()
    cdp(ws, "Runtime.enable")
    d = cdp(ws, "Runtime.evaluate", {"expression": expr, "returnByValue": True}, 2)
    ws.close()
    return d.get("result", {}).get("result", {}).get("value")

def fill(desc):
    payload = json.dumps(desc)
    expr = f"""(() => {{
      const desc = {payload};
      const edit = [...document.querySelectorAll('button,a')].find(b => /^\\s*Edit\\s*$/i.test((b.innerText||'').trim()));
      if (edit) edit.click();
      const el = document.querySelector('form textarea#description[name="description"]') || document.querySelector('#description');
      if (!el) return {{ok:false, reason:'no description field'}};
      el.focus();
      el.value = desc;
      el.dispatchEvent(new Event('input', {{bubbles:true}}));
      el.dispatchEvent(new Event('change', {{bubbles:true}}));
      const form = el.closest('form');
      const btn = [...form.querySelectorAll('button, input[type=submit]')].find(b => {{
        const t = (b.innerText||b.value||'');
        return /update/i.test(t) && !/send|upgrade|outreach email/i.test(t);
      }});
      if (btn) btn.click();
      else form.submit();
      return {{ok:true, clicked: !!(btn), preview: desc.slice(0,80)}};
    }})()"""
    return eval_js(expr)

def main():
    results = []
    for cid, hook in CAMPS.items():
        url = f"https://pressranger.com/campaigns/{cid}"
        print("NAV", cid, flush=True)
        nav(url)
        time.sleep(0.4)
        desc = COMBINED + "\n" + hook
        r = fill(desc)
        print("FILL", r, flush=True)
        results.append({"id": cid, "url": url, "result": r})
        time.sleep(2.2)
    print(json.dumps(results, indent=2))

if __name__ == "__main__":
    main()
