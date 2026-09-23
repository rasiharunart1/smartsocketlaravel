/*
 * ============================================================================
 *  Smart Socket ESP32 Firmware
 *  ----------------------------------------------------------------------------
 *  Dual PZEM-004T + 2-Channel Relay + DS18B20 + MQ-2
 *  Target     : ESP32 (Arduino Core)
 *  Backend    : Laravel 13 (smartsocket) via HiveMQ Cloud MQTT (TLS 8883)
 *
 *  Pin Mapping (sesuai spesifikasi hardware):
 *    RELAY1_PIN   = 21
 *    RELAY2_PIN   = 19
 *    DS18B20_PIN  = 5
 *    MQ2_PIN      = 34 (ADC1)
 *    PZEM1_RX     = 16
 *    PZEM1_TX     = 17  (HardwareSerial 1)
 *    PZEM2_RX     = 26
 *    PZEM2_TX     = 27  (HardwareSerial 2)
 * ============================================================================
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <PZEM004Tv30.h>
#include <OneWire.h>
#include <DallasTemperature.h>

// ----------------------------- KONFIGURASI UMUM -----------------------------
#define FIRMWARE_VERSION "2.1.4"

// Identitas perangkat (harus sama dengan DEFAULT_DEVICE_UID backend)
#define DEVICE_UID "ESP32_SOCKET_01"

// ----------------------------- WIFI -----------------------------
// Ganti sesuai jaringan Anda (atau gunakan WiFiManager)
const char* WIFI_SSID = "YOUR_WIFI_SSID";
const char* WIFI_PASS = "YOUR_WIFI_PASSWORD";

// ----------------------------- MQTT (HiveMQ Cloud) -----------------------------
const char* MQTT_HOST     = "ab11f67ab13c48b5937d15d0439112f4.s1.eu.hivemq.cloud";
const uint16_t MQTT_PORT  = 8883;
const char* MQTT_USER     = "wilda";
const char* MQTT_PASS     = "wildajuwita321";
// Client ID unik per perangkat (hindari tabrakan antar perangkat)
const char* MQTT_CLIENT_ID = "esp32_socket_01_client";

// ----------------------------- TOPIK MQTT -----------------------------
String TOPIC_TELEMETRY;   // smartsocket/{uid}/telemetry
String TOPIC_STATUS;      // smartsocket/{uid}/status
String TOPIC_ALERT;       // smartsocket/{uid}/alert
String TOPIC_SWITCH;      // smartsocket/{uid}/command/switch
String TOPIC_THRESHOLD;   // smartsocket/{uid}/command/threshold
String TOPIC_RECONNECT;   // smartsocket/{uid}/command/reconnect

// ----------------------------- PIN MAPPING -----------------------------
#define RELAY1_PIN 21
#define RELAY2_PIN 19

#define DS18B20_PIN 5
#define MQ2_PIN 34

#define PZEM1_RX 16
#define PZEM1_TX 17
HardwareSerial PZEMSerial1(1);
PZEM004Tv30 pzem1(PZEMSerial1, PZEM1_RX, PZEM1_TX);

#define PZEM2_RX 26
#define PZEM2_TX 27
HardwareSerial PZEMSerial2(2);
PZEM004Tv30 pzem2(PZEMSerial2, PZEM2_RX, PZEM2_TX);

OneWire oneWire(DS18B20_PIN);
DallasTemperature sensors(&oneWire);

// ----------------------------- WAKTU & INTERVAL -----------------------------
#define TELEMETRY_INTERVAL_MS 5000   // kirim telemetri tiap 5 detik
#define STATUS_INTERVAL_MS    30000  // publish status online tiap 30 detik
#define RECONNECT_INTERVAL_MS 5000   // jeda retry koneksi

unsigned long lastTelemetryMs = 0;
unsigned long lastStatusMs    = 0;
unsigned long lastReconnectMs = 0;

// ----------------------------- RELAY STATE -----------------------------
bool relay1State = false;  // false = OFF, true = ON
bool relay2State = false;

// ----------------------------- THRESHOLD (default, bisa diupdate via MQTT) -----------------------------
struct Thresholds {
    float max_voltage     = 245.0;
    float max_current     = 15.5;
    float max_temperature = 65.0;
    float max_smoke_ppm   = 995.0;
} thresholds;

// ----------------------------- OBJEK MQTT -----------------------------
WiFiClientSecure wifiClient;
PubSubClient mqttClient(wifiClient);

// ============================================================================
//  SETUP
// ============================================================================
void setup() {
    Serial.begin(115200);
    delay(200);

    // Build topik
    TOPIC_TELEMETRY = String("smartsocket/") + DEVICE_UID + "/telemetry";
    TOPIC_STATUS    = String("smartsocket/") + DEVICE_UID + "/status";
    TOPIC_ALERT     = String("smartsocket/") + DEVICE_UID + "/alert";
    TOPIC_SWITCH    = String("smartsocket/") + DEVICE_UID + "/command/switch";
    TOPIC_THRESHOLD = String("smartsocket/") + DEVICE_UID + "/command/threshold";
    TOPIC_RECONNECT = String("smartsocket/") + DEVICE_UID + "/command/reconnect";

    // Init Relay (LOW = OFF tergantung modul relay, sesuaikan logika)
    pinMode(RELAY1_PIN, OUTPUT);
    pinMode(RELAY2_PIN, OUTPUT);
    digitalWrite(RELAY1_PIN, LOW);
    digitalWrite(RELAY2_PIN, LOW);

    // Init PZEM-004T
    PZEMSerial1.begin(9600, SERIAL_8N1, PZEM1_RX, PZEM1_TX);
    PZEMSerial2.begin(9600, SERIAL_8N1, PZEM2_RX, PZEM2_TX);

    // Init DS18B20
    sensors.begin();

    // Init WiFi
    connectWiFi();

    // Init MQTT (TLS tanpa verifikasi CA — untuk produksi sebaiknya pakai CA cert)
    wifiClient.setInsecure();
    mqttClient.setServer(MQTT_HOST, MQTT_PORT);
    mqttClient.setBufferSize(1024);
    mqttClient.setCallback(mqttCallback);
    mqttClient.setKeepAlive(30);
}

// ============================================================================
//  LOOP
// ============================================================================
void loop() {
    unsigned long now = millis();

    // Pastikan WiFi & MQTT terkoneksi
    if (!mqttClient.connected()) {
        ensureMqttConnected();
    }
    mqttClient.loop();

    // Kirim telemetri berkala
    if (now - lastTelemetryMs >= TELEMETRY_INTERVAL_MS) {
        lastTelemetryMs = now;
        publishTelemetry();
    }

    // Publish status online berkala
    if (now - lastStatusMs >= STATUS_INTERVAL_MS) {
        lastStatusMs = now;
        publishStatus(true);
    }
}

// ============================================================================
//  WIFI
// ============================================================================
void connectWiFi() {
    Serial.print("[WiFi] Menghubungkan ke ");
    Serial.println(WIFI_SSID);
    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASS);

    unsigned long start = millis();
    while (WiFi.status() != WL_CONNECTED && millis() - start < 20000) {
        delay(500);
        Serial.print(".");
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println();
        Serial.print("[WiFi] Terhubung. IP: ");
        Serial.println(WiFi.localIP());
    } else {
        Serial.println();
        Serial.println("[WiFi] Gagal terhubung, mencoba ulang...");
    }
}

// ============================================================================
//  MQTT
// ============================================================================
void ensureMqttConnected() {
    if (WiFi.status() != WL_CONNECTED) {
        connectWiFi();
        return;
    }

    if (mqttClient.connected()) {
        return;
    }

    unsigned long now = millis();
    if (now - lastReconnectMs < RECONNECT_INTERVAL_MS) {
        return;
    }
    lastReconnectMs = now;

    Serial.print("[MQTT] Menghubungkan ke ");
    Serial.print(MQTT_HOST);
    Serial.print(":");
    Serial.println(MQTT_PORT);

    // LWT: kirim "offline" jika perangkat putus koneksi secara tidak normal
    String lwtPayload = String("{\"device_id\":\"") + DEVICE_UID + "\",\"status\":\"offline\"}";
    String statusPayload = buildStatusPayload(true);

    if (mqttClient.connect(MQTT_CLIENT_ID, MQTT_USER, MQTT_PASS,
                           TOPIC_STATUS.c_str(), 1, true, lwtPayload.c_str())) {
        Serial.println("[MQTT] Terhubung.");

        // Subscribe perintah dari backend
        mqttClient.subscribe(TOPIC_SWITCH.c_str(), 1);
        mqttClient.subscribe(TOPIC_THRESHOLD.c_str(), 1);
        mqttClient.subscribe(TOPIC_RECONNECT.c_str(), 1);

        // Publish status online (retain)
        mqttClient.publish(TOPIC_STATUS.c_str(), statusPayload.c_str(), true);
    } else {
        Serial.print("[MQTT] Gagal. RC=");
        Serial.println(mqttClient.state());
    }
}

void mqttCallback(char* topic, byte* payload, unsigned int length) {
    String topicStr = String(topic);
    String payloadStr;
    for (unsigned int i = 0; i < length; i++) {
        payloadStr += (char)payload[i];
    }

    Serial.println("[MQTT] Pesan masuk: " + topicStr + " => " + payloadStr);

    StaticJsonDocument<512> doc;
    DeserializationError err = deserializeJson(doc, payloadStr);
    if (err) {
        Serial.println("[MQTT] JSON tidak valid, diabaikan.");
        return;
    }

    if (topicStr == TOPIC_SWITCH) {
        handleSwitchCommand(doc);
    } else if (topicStr == TOPIC_THRESHOLD) {
        handleThresholdCommand(doc);
    } else if (topicStr == TOPIC_RECONNECT) {
        handleReconnectCommand(doc);
    }
}

// ============================================================================
//  HANDLER PERINTAH MQTT
// ============================================================================
void handleSwitchCommand(JsonDocument& doc) {
    int socketNumber = doc["socket_number"] | 0;
    String state = doc["state"] | "OFF";
    state.toUpperCase();

    bool turnOn = (state == "ON");

    if (socketNumber == 1) {
        relay1State = turnOn;
        digitalWrite(RELAY1_PIN, turnOn ? HIGH : LOW);
        Serial.printf("[Relay] Socket 1 => %s\n", turnOn ? "ON" : "OFF");
    } else if (socketNumber == 2) {
        relay2State = turnOn;
        digitalWrite(RELAY2_PIN, turnOn ? HIGH : LOW);
        Serial.printf("[Relay] Socket 2 => %s\n", turnOn ? "ON" : "OFF");
    }
}

void handleThresholdCommand(JsonDocument& doc) {
    if (doc.containsKey("max_voltage"))     thresholds.max_voltage     = doc["max_voltage"];
    if (doc.containsKey("max_current"))     thresholds.max_current     = doc["max_current"];
    if (doc.containsKey("max_temperature")) thresholds.max_temperature = doc["max_temperature"];
    if (doc.containsKey("max_smoke_ppm"))   thresholds.max_smoke_ppm   = doc["max_smoke_ppm"];

    Serial.println("[Threshold] Diperbarui:");
    Serial.printf("  max_voltage=%.1f max_current=%.1f max_temperature=%.1f max_smoke_ppm=%.1f\n",
                  thresholds.max_voltage, thresholds.max_current,
                  thresholds.max_temperature, thresholds.max_smoke_ppm);
}

void handleReconnectCommand(JsonDocument& doc) {
    Serial.println("[WiFi] Perintah rekoneksi diterima.");
    publishStatus(false);           // beri tahu offline sebentar
    WiFi.disconnect();
    delay(500);
    connectWiFi();
}

// ============================================================================
//  PEMBACAAN SENSOR
// ============================================================================
float readTemperature() {
    sensors.requestTemperatures();
    float t = sensors.getTempCByIndex(0);
    // DallasTemperature mengembalikan -127 jika sensor tidak terdeteksi
    if (t == DEVICE_DISCONNECTED_C || t < -55.0f || t > 125.0f) {
        return 0.0f;
    }
    return t;
}

float readSmokePPM() {
    int raw = analogRead(MQ2_PIN);
    // Konversi kasar ADC -> PPM (perlu kalibrasi sesuai MQ-2 + beban)
    // Nilai ini adalah contoh; sesuaikan dengan kurva kalibrasi sensor Anda.
    float ppm = map(raw, 0, 4095, 0, 1000);
    return ppm;
}

// ============================================================================
//  PUBLISH TELEMETRI
// ============================================================================
void publishTelemetry() {
    // Baca sensor PZEM (gunakan -1 jika gagal)
    float v1 = pzem1.voltage();
    float c1 = pzem1.current();
    float p1 = pzem1.power();
    float e1 = pzem1.energy();
    float f1 = pzem1.frequency();
    float pf1 = pzem1.pf();

    float v2 = pzem2.voltage();
    float c2 = pzem2.current();
    float p2 = pzem2.power();
    float e2 = pzem2.energy();
    float f2 = pzem2.frequency();
    float pf2 = pzem2.pf();

    float temp = readTemperature();
    float smoke = readSmokePPM();

    // Buat payload JSON sesuai format backend
    StaticJsonDocument<1024> doc;
    doc["device_id"] = DEVICE_UID;
    doc["timestamp"] = (uint32_t)(millis() / 1000); // atau pakai epoch via NTP

    JsonObject env = doc.createNestedObject("environmental");
    env["temperature"] = temp;
    env["smoke_ppm"] = smoke;

    JsonObject sockets = doc.createNestedObject("sockets");

    JsonObject s1 = sockets.createNestedObject("socket_1");
    s1["relay_state"] = relay1State ? "ON" : "OFF";
    s1["voltage"] = v1;
    s1["current"] = c1;
    s1["power"] = p1;
    s1["energy"] = e1;
    s1["frequency"] = f1;
    s1["power_factor"] = pf1;

    JsonObject s2 = sockets.createNestedObject("socket_2");
    s2["relay_state"] = relay2State ? "ON" : "OFF";
    s2["voltage"] = v2;
    s2["current"] = c2;
    s2["power"] = p2;
    s2["energy"] = e2;
    s2["frequency"] = f2;
    s2["power_factor"] = pf2;

    char buffer[1024];
    size_t n = serializeJson(doc, buffer);

    if (mqttClient.connected()) {
        mqttClient.publish(TOPIC_TELEMETRY.c_str(), buffer, n);
        Serial.print("[Telemetri] ");
        Serial.println(buffer);
    }

    // Evaluasi threshold -> kirim alert + auto cutoff
    evaluateThresholds(v1, v2, c1, c2, temp, smoke);
}

// ============================================================================
//  EVALUASI THRESHOLD & ALERT
// ============================================================================
void evaluateThresholds(float v1, float v2, float c1, float c2, float temp, float smoke) {
    bool cutoff = false;

    // Over voltage
    if (thresholds.max_voltage > 0 && (v1 >= thresholds.max_voltage || v2 >= thresholds.max_voltage)) {
        float worst = max(v1, v2);
        sendAlert("OVER_VOLTAGE", worst, thresholds.max_voltage, "AUTO_CUTOFF_SOCKET_1_AND_2");
        cutoff = true;
    }

    // Over current
    if (thresholds.max_current > 0 && (c1 >= thresholds.max_current || c2 >= thresholds.max_current)) {
        float worst = max(c1, c2);
        sendAlert("OVER_CURRENT", worst, thresholds.max_current, "AUTO_CUTOFF_SOCKET_1_AND_2");
        cutoff = true;
    }

    // Over temperature
    if (thresholds.max_temperature > 0 && temp >= thresholds.max_temperature) {
        sendAlert("OVER_TEMPERATURE", temp, thresholds.max_temperature, "WARNING_LOGGED");
    }

    // Smoke detected
    if (thresholds.max_smoke_ppm > 0 && smoke >= thresholds.max_smoke_ppm) {
        sendAlert("SMOKE_DETECTED", smoke, thresholds.max_smoke_ppm, "EMERGENCY_ALERT");
        cutoff = true;
    }

    // Auto cutoff jika kondisi bahaya (voltase/arus/asap)
    if (cutoff) {
        relay1State = false;
        relay2State = false;
        digitalWrite(RELAY1_PIN, LOW);
        digitalWrite(RELAY2_PIN, LOW);
        Serial.println("[Safety] Relay 1 & 2 dimatikan otomatis.");
    }
}

void sendAlert(const char* type, float value, float threshold, const char* action) {
    StaticJsonDocument<256> doc;
    doc["device_id"] = DEVICE_UID;
    doc["alert_type"] = type;
    doc["value"] = value;
    doc["threshold"] = threshold;
    doc["action_taken"] = action;
    doc["timestamp"] = (uint32_t)(millis() / 1000);

    char buffer[256];
    size_t n = serializeJson(doc, buffer);

    if (mqttClient.connected()) {
        mqttClient.publish(TOPIC_ALERT.c_str(), buffer, n);
        Serial.print("[Alert] ");
        Serial.println(buffer);
    }
}

// ============================================================================
//  STATUS (LWT)
// ============================================================================
String buildStatusPayload(bool online) {
    StaticJsonDocument<512> doc;
    doc["device_id"] = DEVICE_UID;
    doc["status"] = online ? "online" : "offline";
    doc["ip_address"] = WiFi.localIP().toString();
    doc["mac_address"] = WiFi.macAddress();
    doc["wifi_rssi"] = WiFi.RSSI();
    doc["firmware_version"] = FIRMWARE_VERSION;
    doc["timestamp"] = (uint32_t)(millis() / 1000);

    char buffer[512];
    serializeJson(doc, buffer);
    return String(buffer);
}

void publishStatus(bool online) {
    String payload = buildStatusPayload(online);
    if (mqttClient.connected()) {
        mqttClient.publish(TOPIC_STATUS.c_str(), payload.c_str(), true);
        Serial.print("[Status] ");
        Serial.println(payload);
    }
}
