#pragma once
/*
 * config_portal.h - Web server lokal untuk setup credential Smart Socket
 *  - Konfigurasi disimpan di NVS (namespace "config")
 *  - Portal aktif di IP STA (port 80) dan otomatis membuka AP setup jika:
 *      a) WiFi belum dikonfigurasi / gagal konek saat boot
 *      b) tombol BOOT (GPIO0) ditahan 3 detik saat power-on
 *      c) WiFi putus > 2 menit
 *  - Login: user "admin", password = Web Password (default "admin1234")
 *  - Password AP setup = Web Password
 */
#include <WebServer.h>
#include <DNSServer.h>
#include <Preferences.h>

struct DeviceConfig {
    String   wifiSsid, wifiPass;
    String   mqttHost, mqttUser, mqttPass, mqttClientId;
    uint16_t mqttPort = 8883;
    String   deviceUid;
    String   serverUrl;
    String   ntpServer;
    int      gmtOffset = 7;
    String   webPass;
};
DeviceConfig cfg;

static Preferences cfgPrefs;
static WebServer   webServer(80);
static DNSServer   dnsServer;
static bool          portalAP       = false;
static bool          portalForced   = false;
static unsigned long restartAt      = 0;
static unsigned long wifiDownSince  = 0;

void cfgLoad() {
    cfgPrefs.begin("config", true);
    cfg.wifiSsid     = cfgPrefs.getString("wssid", "");
    cfg.wifiPass     = cfgPrefs.getString("wpass", "");
    cfg.mqttHost     = cfgPrefs.getString("mhost", "ab11f67ab13c48b5937d15d0439112f4.s1.eu.hivemq.cloud");
    cfg.mqttPort     = cfgPrefs.getUShort("mport", 8883);
    cfg.mqttUser     = cfgPrefs.getString("muser", "");
    cfg.mqttPass     = cfgPrefs.getString("mpass", "");
    cfg.mqttClientId = cfgPrefs.getString("mcid", "ESP32_SmartSocket");
    cfg.deviceUid    = cfgPrefs.getString("uid", "SS-EPJPJV0Q8DOM");
    cfg.serverUrl    = cfgPrefs.getString("url", "");
    cfg.ntpServer    = cfgPrefs.getString("ntp", "pool.ntp.org");
    cfg.gmtOffset    = cfgPrefs.getInt("gmt", 7);
    cfg.webPass      = cfgPrefs.getString("webpw", "admin1234");
    cfgPrefs.end();
}

void cfgSave() {
    cfgPrefs.begin("config", false);
    cfgPrefs.putString("wssid", cfg.wifiSsid);
    cfgPrefs.putString("wpass", cfg.wifiPass);
    cfgPrefs.putString("mhost", cfg.mqttHost);
    cfgPrefs.putUShort("mport", cfg.mqttPort);
    cfgPrefs.putString("muser", cfg.mqttUser);
    cfgPrefs.putString("mpass", cfg.mqttPass);
    cfgPrefs.putString("mcid", cfg.mqttClientId);
    cfgPrefs.putString("uid", cfg.deviceUid);
    cfgPrefs.putString("url", cfg.serverUrl);
    cfgPrefs.putString("ntp", cfg.ntpServer);
    cfgPrefs.putInt("gmt", cfg.gmtOffset);
    cfgPrefs.putString("webpw", cfg.webPass);
    cfgPrefs.end();
}

// ------------------------------- Tampilan -------------------------------
static const char PORTAL_CSS[] PROGMEM =
":root{--bg:#f4f4f2;--fg:#1b1b1b;--mut:#6a6a66;--card:#fff;--bd:#d5d5d0;--ac:#0f5f8a}"
"@media(prefers-color-scheme:dark){:root{--bg:#131313;--fg:#e6e6e3;--mut:#96968f;--card:#1c1c1c;--bd:#34342f;--ac:#3b82b8}}"
"*{box-sizing:border-box}"
"body{margin:0;background:var(--bg);color:var(--fg);font:15px/1.5 system-ui,-apple-system,'Segoe UI',Roboto,sans-serif}"
"main{max-width:520px;margin:0 auto;padding:20px 14px 40px}"
"h1{font-size:20px;margin:0 0 2px}"
".sub{color:var(--mut);font-size:13px;margin:0 0 16px}"
".stat{display:grid;grid-template-columns:1fr 1fr;gap:1px;background:var(--bd);border:1px solid var(--bd);border-radius:6px;overflow:hidden;margin-bottom:16px}"
".stat div{background:var(--card);padding:8px 10px;font-size:14px;overflow-wrap:anywhere}"
".stat b{display:block;font-size:11px;font-weight:600;color:var(--mut);text-transform:uppercase;letter-spacing:.05em}"
"fieldset{background:var(--card);border:1px solid var(--bd);border-radius:6px;margin:0 0 14px;padding:12px 14px 4px}"
"legend{font-size:12px;font-weight:600;padding:0 6px;color:var(--mut);text-transform:uppercase;letter-spacing:.05em}"
"label{display:block;font-size:13px;margin:0 0 10px}"
"input{display:block;width:100%;margin-top:4px;padding:8px 10px;font:inherit;color:var(--fg);background:var(--bg);border:1px solid var(--bd);border-radius:4px}"
"input:focus{outline:2px solid var(--ac);outline-offset:-1px;border-color:var(--ac)}"
".row{display:grid;grid-template-columns:1fr 1fr;gap:10px}"
".chk{display:flex;gap:8px;align-items:center;font-size:13px;color:var(--mut);margin:0 0 14px}"
".chk input{display:inline;width:auto;margin:0}"
"button,.btn{display:block;width:100%;padding:11px;font:inherit;font-weight:600;text-align:center;text-decoration:none;color:#fff;background:var(--ac);border:0;border-radius:4px;cursor:pointer}"
".msg{background:var(--card);border:1px solid var(--bd);border-radius:6px;padding:14px;margin:0 0 14px}";

static const char PORTAL_JS[] PROGMEM =
"function tp(c){document.querySelectorAll('.pw').forEach(function(e){e.type=c.checked?'text':'password'})}";

static String htmlEsc(const String& s) {
    String o;
    o.reserve(s.length() + 8);
    for (size_t i = 0; i < s.length(); i++) {
        char c = s[i];
        if (c == '&') o += "&amp;";
        else if (c == '<') o += "&lt;";
        else if (c == '>') o += "&gt;";
        else if (c == '"') o += "&quot;";
        else if (c == '\'') o += "&#39;";
        else o += c;
    }
    return o;
}

static String fld(const char* label, const char* name, const String& val,
                  const char* type = "text", const String& ph = "") {
    String s = "<label>";
    s += label;
    s += "<input name='";
    s += name;
    s += "' type='";
    s += type;
    s += "'";
    if (!strcmp(type, "password")) s += " class='pw' autocomplete='new-password'";
    if (!strcmp(type, "number"))   s += " inputmode='numeric'";
    s += " value=\"";
    s += htmlEsc(val);
    s += "\" placeholder=\"";
    s += ph;
    s += "\"></label>";
    return s;
}

static String pwHint(const String& stored) {
    return stored.length() ? String("Tersimpan, kosongkan untuk tetap") : String("Belum diisi");
}

static String statCell(const char* k, const String& v) {
    return String("<div><b>") + k + "</b>" + htmlEsc(v) + "</div>";
}

static void pageBegin(int code, const char* title) {
    webServer.setContentLength(CONTENT_LENGTH_UNKNOWN);
    webServer.send(code, "text/html", "");
    webServer.sendContent("<!DOCTYPE html><html lang='id'><head><meta charset='utf-8'>"
                          "<meta name='viewport' content='width=device-width,initial-scale=1'><title>");
    webServer.sendContent(title);
    webServer.sendContent("</title><style>");
    webServer.sendContent_P(PORTAL_CSS);
    webServer.sendContent("</style></head><body><main>");
}

static void pageEnd() {
    webServer.sendContent("</main></body></html>");
    webServer.sendContent("");
}

static void sendMsg(int code, const char* title, const String& msg, bool back) {
    pageBegin(code, title);
    webServer.sendContent(String("<h1>") + title + "</h1><p class='sub'>Smart Socket</p><div class='msg'>" + htmlEsc(msg) + "</div>");
    if (back) webServer.sendContent("<a class='btn' href='javascript:history.back()'>Kembali</a>");
    pageEnd();
}

static bool portalAuth() {
    if (webServer.authenticate("admin", cfg.webPass.c_str())) return true;
    webServer.requestAuthentication();
    return false;
}

// ------------------------------- Handlers -------------------------------
static void handleRoot() {
    if (!portalAuth()) return;

    bool up = (WiFi.status() == WL_CONNECTED);
    unsigned long s = millis() / 1000;
    String uptime = String(s / 3600) + " j " + String((s % 3600) / 60) + " m";

    pageBegin(200, "Smart Socket - Pengaturan");
    webServer.sendContent("<h1>Pengaturan Perangkat</h1><p class='sub'>Smart Socket, firmware " FIRMWARE_VERSION "</p>");

    String st = "<div class='stat'>";
    st += statCell("Device UID", cfg.deviceUid);
    st += statCell("Mode", portalAP ? "AP setup aktif" : "Station");
    st += statCell("WiFi", up ? (WiFi.SSID() + " (" + String(WiFi.RSSI()) + " dBm)") : String("Tidak terhubung"));
    st += statCell("IP", up ? WiFi.localIP().toString() : String("-"));
    st += statCell("Uptime", uptime);
    st += statCell("Free heap", String(ESP.getFreeHeap() / 1024) + " KB");
    st += "</div>";
    webServer.sendContent(st);

    webServer.sendContent("<form method='POST' action='/save'>");

    String f = "<fieldset><legend>WiFi</legend>";
    f += fld("SSID", "wssid", cfg.wifiSsid);
    f += fld("Password", "wpass", "", "password", pwHint(cfg.wifiPass));
    f += "</fieldset>";
    webServer.sendContent(f);

    f = "<fieldset><legend>MQTT</legend>";
    f += fld("Host", "mhost", cfg.mqttHost);
    f += "<div class='row'>";
    f += fld("Port", "mport", String(cfg.mqttPort), "number");
    f += fld("Client ID", "mcid", cfg.mqttClientId);
    f += "</div>";
    f += fld("Username", "muser", cfg.mqttUser);
    f += fld("Password", "mpass", "", "password", pwHint(cfg.mqttPass));
    f += "</fieldset>";
    webServer.sendContent(f);

    f = "<fieldset><legend>Perangkat</legend>";
    f += fld("Device UID", "uid", cfg.deviceUid);
    f += fld("URL Server / Dashboard", "url", cfg.serverUrl, "text", "https://");
    f += "<div class='row'>";
    f += fld("NTP Server", "ntp", cfg.ntpServer);
    f += fld("Zona waktu (GMT +/- jam)", "gmt", String(cfg.gmtOffset), "number");
    f += "</div></fieldset>";
    webServer.sendContent(f);

    f = "<fieldset><legend>Akses Portal</legend>";
    f += fld("Password baru (minimal 8 karakter)", "webpw", "", "password", "Kosongkan untuk tetap");
    f += "</fieldset>";
    f += "<label class='chk'><input type='checkbox' onchange='tp(this)'>Tampilkan password</label>";
    f += "<button type='submit'>Simpan dan restart</button></form><script>";
    webServer.sendContent(f);
    webServer.sendContent_P(PORTAL_JS);
    webServer.sendContent("</script>");
    pageEnd();
}

static void handleSave() {
    if (!portalAuth()) return;

    String ssid = webServer.arg("wssid");   ssid.trim();
    String host = webServer.arg("mhost");   host.trim();
    String uid  = webServer.arg("uid");     uid.trim();
    int port    = webServer.arg("mport").toInt();
    String newWebPw = webServer.arg("webpw");

    if (ssid.isEmpty() || host.isEmpty() || uid.isEmpty() || port < 1 || port > 65535) {
        sendMsg(400, "Gagal menyimpan", "SSID, host MQTT, Device UID, dan port (1-65535) wajib diisi dengan benar.", true);
        return;
    }
    if (newWebPw.length() > 0 && newWebPw.length() < 8) {
        sendMsg(400, "Gagal menyimpan", "Password portal minimal 8 karakter.", true);
        return;
    }

    cfg.wifiSsid = ssid;
    if (webServer.arg("wpass").length() > 0) cfg.wifiPass = webServer.arg("wpass");
    cfg.mqttHost = host;
    cfg.mqttPort = (uint16_t)port;
    cfg.mqttUser = webServer.arg("muser");
    if (webServer.arg("mpass").length() > 0) cfg.mqttPass = webServer.arg("mpass");
    String cid = webServer.arg("mcid"); cid.trim();
    cfg.mqttClientId = cid.isEmpty() ? String("ESP32_SmartSocket") : cid;
    cfg.deviceUid = uid;
    cfg.serverUrl = webServer.arg("url");    cfg.serverUrl.trim();
    String ntp = webServer.arg("ntp");       ntp.trim();
    cfg.ntpServer = ntp.isEmpty() ? String("pool.ntp.org") : ntp;
    cfg.gmtOffset = constrain(webServer.arg("gmt").toInt(), -12, 14);
    if (newWebPw.length() >= 8) cfg.webPass = newWebPw;

    cfgSave();
    sendMsg(200, "Konfigurasi tersimpan",
            "Perangkat akan restart dalam beberapa detik. Sambungkan kembali ke jaringan, lalu buka halaman ini lagi.", false);
    restartAt = millis() + 1500;
}

static void handleNotFound() {
    if (portalAP) { // captive portal redirect
        webServer.sendHeader("Location", String("http://") + WiFi.softAPIP().toString() + "/", true);
        webServer.send(302, "text/plain", "");
    } else {
        webServer.send(404, "text/plain", "Not found");
    }
}

// ------------------------------- API modul -------------------------------
bool portalBootButtonHeld() {
    pinMode(0, INPUT_PULLUP);
    unsigned long t = millis();
    while (digitalRead(0) == LOW) {
        if (millis() - t >= 3000) return true;
        delay(20);
    }
    return false;
}

void portalBegin() {
    webServer.on("/", HTTP_GET, handleRoot);
    webServer.on("/save", HTTP_POST, handleSave);
    webServer.onNotFound(handleNotFound);
    webServer.begin();
    Serial.println("[Portal] Web server lokal aktif di port 80");
}

void portalStartAP(bool forced = false) {
    if (portalAP) return;
    portalAP     = true;
    portalForced = forced;
    WiFi.mode(WIFI_AP_STA);
    String uid4 = cfg.deviceUid.length() > 4 ? cfg.deviceUid.substring(cfg.deviceUid.length() - 4) : cfg.deviceUid;
    String apSsid = "SmartSocket-" + uid4;
    WiFi.softAP(apSsid.c_str(), cfg.webPass.c_str());
    dnsServer.start(53, "*", WiFi.softAPIP());
    Serial.print("[Portal] AP setup: ");
    Serial.print(apSsid);
    Serial.print(" | http://");
    Serial.println(WiFi.softAPIP());
}

void portalLoop(unsigned long now) {
    webServer.handleClient();
    if (portalAP) dnsServer.processNextRequest();

    if (restartAt && now > restartAt) ESP.restart();

    // Matikan AP otomatis jika WiFi sudah terhubung (kecuali dipaksa via tombol)
    if (portalAP && !portalForced && WiFi.status() == WL_CONNECTED) {
        dnsServer.stop();
        WiFi.softAPdisconnect(true);
        portalAP = false;
        Serial.println("[Portal] WiFi terhubung, AP setup dimatikan");
    }

    // Buka AP jika WiFi putus > 2 menit
    if (!portalAP) {
        if (WiFi.status() == WL_CONNECTED) {
            wifiDownSince = 0;
        } else if (wifiDownSince == 0) {
            wifiDownSince = now;
        } else if (now - wifiDownSince > 120000UL) {
            portalStartAP(false);
        }
    }
}
