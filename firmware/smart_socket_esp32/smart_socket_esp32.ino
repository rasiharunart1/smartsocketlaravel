/*
 * ============================================================================
 *  Smart Socket ESP32 Firmware
 *  ----------------------------------------------------------------------------
 *  Dual PZEM-004T v3.0 + 2-Channel Relay + DS18B20 + MQ-2 + LCD 16x2 I2C
 *  Target Hardware : ESP32 (Arduino Core 2.x & 3.x)
 *  Backend Platform: Laravel 13 (smartsocket) via HiveMQ Cloud MQTT (TLS 8883)
 *
 *  Pin Mapping:
 *    #define RELAY1_PIN   18   // Relay Soket 1 (Output)
 *    #define RELAY2_PIN   19   // Relay Soket 2 (Output)
 *    #define DS18B20_PIN  5    // Sensor Suhu Enclosure (OneWire)
 *    #define MQ2_PIN      34   // Sensor Gas & Asap Enclosure (ADC1 CH6)
 *    PZEM1 (RX 16, TX 17)      // PZEM-004T v3.0 Soket 1 (HardwareSerial 1)
 *    PZEM2 (RX 26, TX 27)      // PZEM-004T v3.0 Soket 2 (HardwareSerial 2)
 *    I2C LCD (SDA 21, SCL 22)  // LCD 16x2 I2C Address 0x27 / 0x3F
 * ============================================================================
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <PZEM004Tv30.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <Preferences.h>

// ----------------------------- VERSI FIRMWARE & IDENTITAS -----------------------------
#define FIRMWARE_VERSION "2.5.0"

// Identitas perangkat: Samakan dengan "Device UID" pada web menu Settings
#define DEVICE_UID "SS-EPJPJV0Q8DOM"

// ----------------------------- KONFIGURASI WIFI -----------------------------
// Masukkan SSID dan Password WiFi jaringan Anda di sini
const char* WIFI_SSID = "MEGADATA";
const char* WIFI_PASS = "MEGADATA";

// ----------------------------- MQTT (HiveMQ Cloud TLS 8883) -----------------------------
const char* MQTT_HOST      = "ab11f67ab13c48b5937d15d0439112f4.s1.eu.hivemq.cloud";
const uint16_t MQTT_PORT   = 8883;
const char* MQTT_USER      = "wilda";
const char* MQTT_PASS      = "wildajuwita321";
const char* MQTT_CLIENT_ID = "ESP32_SmartSocket";

// ----------------------------- TOPIK MQTT DINAMIS -----------------------------
String TOPIC_TELEMETRY;   // smartsocket/{uid}/telemetry
String TOPIC_STATUS;      // smartsocket/{uid}/status
String TOPIC_ALERT;       // smartsocket/{uid}/alert
String TOPIC_SWITCH;      // smartsocket/{uid}/command/switch
String TOPIC_SWITCH_SYNC; // smartsocket/{uid}/command/switch/sync (Retained Relay State)
String TOPIC_THRESHOLD;   // smartsocket/{uid}/command/threshold
String TOPIC_RECONNECT;   // smartsocket/{uid}/command/reconnect

// ----------------------------- PIN MAPPING -----------------------------
#define RELAY1_PIN   18   // Relay Soket 1
#define RELAY2_PIN   19   // Relay Soket 2

#define DS18B20_PIN  5    // Sensor Suhu Enclosure DS18B20 (OneWire)
#define MQ2_PIN      34   // Sensor Gas & Asap MQ-2 (Input Analog ADC1 CH6)

// PZEM 1 (Soket 1) -> HardwareSerial(1)
#define PZEM1_RX     16
#define PZEM1_TX     17
HardwareSerial PZEMSerial1(1);
PZEM004Tv30 pzem1(PZEMSerial1, PZEM1_RX, PZEM1_TX);

// PZEM 2 (Soket 2) -> HardwareSerial(2)
#define PZEM2_RX     26
#define PZEM2_TX     27
HardwareSerial PZEMSerial2(2);
PZEM004Tv30 pzem2(PZEMSerial2, PZEM2_RX, PZEM2_TX);

// OneWire & Dallas Temperature
OneWire oneWire(DS18B20_PIN);
DallasTemperature sensors(&oneWire);

// LCD 16x2 I2C
#define LCD_I2C_ADDR 0x27  // Alamat standar PCF8574 (0x27) atau PCF8574A (0x3F)
#define LCD_I2C_SDA  21
#define LCD_I2C_SCL  22
#define LCD_COLUMNS  16
#define LCD_ROWS     2
LiquidCrystal_I2C lcd(LCD_I2C_ADDR, LCD_COLUMNS, LCD_ROWS);

// Konfigurasi Level Logika Relay:
// Sebagian besar modul relay 2-channel optocoupler adalah ACTIVE-LOW (LOW = ON, HIGH = OFF).
// Jika modul relay Anda tipe ACTIVE-HIGH (HIGH = ON), ubah menjadi false.
#define RELAY_ACTIVE_LOW  true

// Custom Icon untuk LCD 16x2 (Karakter Derajat °)
const byte customCharDegree[8] = {
    0b00111,
    0b00101,
    0b00111,
    0b00000,
    0b00000,
    0b00000,
    0b00000,
    0b00000
};

// ----------------------------- WAKTU & INTERVAL (NON-BLOCKING) -----------------------------
#define SENSOR_READ_INTERVAL_MS 1000   // Pembacaan sensor & evaluasi keselamatan tiap 1 detik
unsigned long telemetryIntervalMs = 5000;  // Interval kirim telemetri ke server (dinamis via server, default 5s)
#define STATUS_INTERVAL_MS      30000  // Kirim heartbeat online berkala tiap 30 detik
#define LCD_PAGE_INTERVAL_MS    2500   // Rotasi halaman info LCD tiap 2,5 detik
#define ALERT_COOLDOWN_MS       30000  // Batasi kirim alarm serupa maks 1x per 30 detik
#define WIFI_RETRY_INTERVAL_MS  10000  // Jeda rekoneksi WiFi jika terputus
#define MQTT_RECONNECT_MS       5000   // Jeda rekoneksi MQTT jika terputus

unsigned long lastSensorReadMs  = 0;
unsigned long lastTelemetryMs   = 0;
unsigned long lastStatusMs      = 0;
unsigned long lastLcdPageMs     = 0;
unsigned long lastWifiRetryMs   = 0;
unsigned long lastMqttRetryMs   = 0;
unsigned long lcdAlertUntilMs   = 0;
unsigned long lastAlertSentMs   = 0;
String        lastAlertSentType = "";
uint8_t       lcdPage           = 0;

// ----------------------------- ARDUINOJSON V6 & V7 MACRO -----------------------------
#if defined(ARDUINOJSON_VERSION_MAJOR) && (ARDUINOJSON_VERSION_MAJOR >= 7)
    #define ALLOC_JSON_DOC(doc, size) JsonDocument doc
#else
    #define ALLOC_JSON_DOC(doc, size) StaticJsonDocument<size> doc
#endif

// ----------------------------- STATUS RELAY & SENSOR -----------------------------
bool relay1State = false;  // false = OFF, true = ON
bool relay2State = false;

struct SensorSnapshot {
    float voltage1    = 0.0f;
    float current1    = 0.0f;
    float power1      = 0.0f;
    float energy1     = 0.0f;
    float freq1       = 0.0f;
    float pf1         = 0.0f;

    float voltage2    = 0.0f;
    float current2    = 0.0f;
    float power2      = 0.0f;
    float energy2     = 0.0f;
    float freq2       = 0.0f;
    float pf2         = 0.0f;

    float temperature = 0.0f;
    float smokePpm    = 0.0f;
    bool  isTripped   = false;
} latestSensor;

// ----------------------------- THRESHOLD -----------------------------
struct Thresholds {
    float max_voltage     = 245.0f; // Default 245V
    float max_current     = 15.5f;  // Default 15.5A
    float max_temperature = 65.0f;  // Default 65°C
    float max_smoke_ppm   = 995.0f; // Default 995 ppm
    uint32_t device_interval = 5;   // Default interval telemetri ESP32 ke dashboard 5 detik
} thresholds;

// Objek Penyimpanan Flash Non-Volatile (NVS Preferences)
Preferences preferences;

// ----------------------------- OBJEK MQTT TLS -----------------------------
WiFiClientSecure wifiClient;
PubSubClient mqttClient(wifiClient);

// Helper pengatur relay dengan penanganan active-low aman dan penyimpanan Flash NVS
inline void setRelayOutput(int socket, bool state) {
    uint8_t pin = (socket == 1) ? RELAY1_PIN : RELAY2_PIN;
    bool pinLevel = RELAY_ACTIVE_LOW ? (!state) : state;
    digitalWrite(pin, pinLevel ? HIGH : LOW);
    if (socket == 1) relay1State = state;
    if (socket == 2) relay2State = state;

    preferences.begin("relays", false);
    if (socket == 1) preferences.putBool("r1", state);
    if (socket == 2) preferences.putBool("r2", state);
    preferences.end();
}

// Forward declarations
void printLcdLine(uint8_t row, const char* text);
void showLcdAlert(const char* type);
void updateLcd(unsigned long now);
void checkWifiAndMqtt(unsigned long now);
void connectMqtt();
void mqttCallback(char* topic, byte* payload, unsigned int length);
void processSwitchCommand(int socketNumber, const char* stateStr);
void processThresholdCommand(float v_max, float c_max, float t_max, float s_max, int dev_interval = 0);
void processReconnectCommand();
float readTemperature();
float readSmokePPM();
float sanitizeReading(float value);
void readAllSensors();
void publishTelemetry();
void evaluateThresholds();
void sendAlert(const char* type, float value, float threshold, const char* action, int socketNumber = 0);
String buildStatusPayload(bool online);
void publishStatus(bool online);
void loadSavedThresholds();
void saveThresholds();

// ============================================================================
//  SETUP
// ============================================================================
void setup() {
    Serial.begin(115200);
    delay(250);
    Serial.println("\n=======================================================");
    Serial.println("  Smart Socket ESP32 Firmware v" FIRMWARE_VERSION);
    Serial.println("=======================================================");

    // 1. Inisialisasi Pin Relay dengan status terakhir tersimpan dari Flash NVS
    preferences.begin("relays", true);
    relay1State = preferences.getBool("r1", false);
    relay2State = preferences.getBool("r2", false);
    preferences.end();

    digitalWrite(RELAY1_PIN, RELAY_ACTIVE_LOW ? (!relay1State) : relay1State);
    pinMode(RELAY1_PIN, OUTPUT);
    digitalWrite(RELAY2_PIN, RELAY_ACTIVE_LOW ? (!relay2State) : relay2State);
    pinMode(RELAY2_PIN, OUTPUT);

    // 2. Muat batas proteksi dari memori Flash (NVS)
    loadSavedThresholds();

    // 3. Konfigurasi ADC ESP32 untuk pembacaan analog akurat MQ-2
    analogReadResolution(12);
    analogSetPinAttenuation(MQ2_PIN, ADC_11db);

    // 4. Inisialisasi I2C Wire untuk LCD 16x2 dengan Timeout anti-lockup
    Wire.begin(LCD_I2C_SDA, LCD_I2C_SCL);
#if defined(ESP_ARDUINO_VERSION_MAJOR) && (ESP_ARDUINO_VERSION_MAJOR >= 3)
    Wire.setTimeout(100);
#else
    Wire.setTimeOut(100);
#endif
    Wire.setClock(100000); // 100 kHz standard mode

    lcd.init();
    lcd.backlight();
    lcd.createChar(1, (uint8_t*)customCharDegree);

    printLcdLine(0, " SMART SOCKET ");
    printLcdLine(1, " MEMULAI SISTEM");

    // 5. Build topik MQTT dinamis berbasis UID perangkat
    TOPIC_TELEMETRY   = String("smartsocket/") + DEVICE_UID + "/telemetry";
    TOPIC_STATUS      = String("smartsocket/") + DEVICE_UID + "/status";
    TOPIC_ALERT       = String("smartsocket/") + DEVICE_UID + "/alert";
    TOPIC_SWITCH      = String("smartsocket/") + DEVICE_UID + "/command/switch";
    TOPIC_SWITCH_SYNC = String("smartsocket/") + DEVICE_UID + "/command/switch/sync";
    TOPIC_THRESHOLD   = String("smartsocket/") + DEVICE_UID + "/command/threshold";
    TOPIC_RECONNECT   = String("smartsocket/") + DEVICE_UID + "/command/reconnect";

    // 6. Inisialisasi HardwareSerial PZEM-004T v3.0
    PZEMSerial1.begin(9600, SERIAL_8N1, PZEM1_RX, PZEM1_TX);
    PZEMSerial2.begin(9600, SERIAL_8N1, PZEM2_RX, PZEM2_TX);

    // 7. Inisialisasi Sensor Suhu OneWire DS18B20 secara asinkron (non-blocking)
    sensors.begin();
    sensors.setWaitForConversion(false);
    sensors.requestTemperatures();

    // 8. Inisialisasi WiFi & NTP Time Sync (WIB GMT+7)
    WiFi.mode(WIFI_STA);
    WiFi.setAutoReconnect(true);
    WiFi.begin(WIFI_SSID, WIFI_PASS);
    configTime(7 * 3600, 0, "pool.ntp.org", "time.google.com");
    Serial.print("[WiFi] Menghubungkan ke ");
    Serial.println(WIFI_SSID);
    printLcdLine(0, "WiFi");
    printLcdLine(1, "Menghubungkan...");

    unsigned long start = millis();
    while (WiFi.status() != WL_CONNECTED && millis() - start < 8000) {
        delay(250);
        Serial.print(".");
    }
    Serial.println();

    if (WiFi.status() == WL_CONNECTED) {
        Serial.print("[WiFi] Terhubung. IP: ");
        Serial.println(WiFi.localIP());
        printLcdLine(0, "WiFi Terhubung");
        printLcdLine(1, WiFi.localIP().toString().c_str());
        delay(600);
    } else {
        Serial.println("[WiFi] Belum terhubung. Melanjutkan dalam mode mandiri...");
        printLcdLine(0, "WiFi Belum Konek");
        printLcdLine(1, "Mode Mandiri");
        delay(600);
    }

    // 9. Konfigurasi MQTT TLS HiveMQ Cloud
    wifiClient.setInsecure(); // Mengabaikan validasi root CA untuk efisiensi RAM ESP32
    mqttClient.setServer(MQTT_HOST, MQTT_PORT);
    mqttClient.setBufferSize(1024); // Alokasi buffer paket MQTT aman untuk JSON
    mqttClient.setCallback(mqttCallback);
    mqttClient.setKeepAlive(30);

    Serial.println("[System] Setup selesai. Menjalankan pemantauan background...");
}

// ============================================================================
//  LOOP
// ============================================================================
void loop() {
    unsigned long now = millis();

    // 1. Pemeliharaan Koneksi WiFi & MQTT (Asinkron & Non-blocking)
    checkWifiAndMqtt(now);

    // 2. Pembacaan Sensor & Evaluasi Ambang Batas Keamanan (Setiap 1 detik)
    if (now - lastSensorReadMs >= SENSOR_READ_INTERVAL_MS) {
        lastSensorReadMs = now;
        readAllSensors();
        evaluateThresholds();
    }

    // 3. Publish Telemetri ke Backend Laravel (Sesuai interval yang dikonfigurasi)
    if (now - lastTelemetryMs >= telemetryIntervalMs) {
        lastTelemetryMs = now;
        publishTelemetry();
    }

    // 4. Publish Heartbeat Status (Setiap 30 detik)
    if (now - lastStatusMs >= STATUS_INTERVAL_MS) {
        lastStatusMs = now;
        publishStatus(true);
    }

    // 5. Update Multi-Page Insight Layar LCD 16x2
    updateLcd(now);
}

// ============================================================================
//  PEMELIHARAAN KONEKSI (WIFI & MQTT NON-BLOCKING)
// ============================================================================
void checkWifiAndMqtt(unsigned long now) {
    // A. Periksa WiFi
    if (WiFi.status() != WL_CONNECTED) {
        if (now - lastWifiRetryMs >= WIFI_RETRY_INTERVAL_MS) {
            lastWifiRetryMs = now;
            Serial.println("[WiFi] Mencoba menghubungkan kembali ke jaringan...");
            WiFi.disconnect();
            WiFi.begin(WIFI_SSID, WIFI_PASS);
        }
        return; // Jangan coba MQTT jika WiFi belum tersambung
    }

    // B. Periksa MQTT
    if (!mqttClient.connected()) {
        if (now - lastMqttRetryMs >= MQTT_RECONNECT_MS) {
            lastMqttRetryMs = now;
            connectMqtt();
        }
    } else {
        mqttClient.loop();
    }
}

void connectMqtt() {
    // Generate Client ID unik berbasis MAC Address untuk mencegah disconnect collision di broker
    String uniqueClientId = String(MQTT_CLIENT_ID) + "_" + DEVICE_UID + "_" + String((uint32_t)ESP.getEfuseMac(), HEX);

    Serial.print("[MQTT] Menghubungkan ke ");
    Serial.print(MQTT_HOST);
    Serial.print(":");
    Serial.println(MQTT_PORT);

    // LWT: kirim "offline" jika perangkat putus koneksi secara tiba-tiba
    String lwtPayload = String("{\"device_id\":\"") + DEVICE_UID + "\",\"status\":\"offline\"}";
    String statusPayload = buildStatusPayload(true);

    if (mqttClient.connect(uniqueClientId.c_str(), MQTT_USER, MQTT_PASS,
                           TOPIC_STATUS.c_str(), 1, true, lwtPayload.c_str())) {
        Serial.println("[MQTT] Berhasil Terhubung ke HiveMQ Cloud Broker.");
        printLcdLine(0, "MQTT Terhubung");
        printLcdLine(1, "Sinkronisasi...");

        // Subscribe perintah & sinkronisasi state dari backend Laravel
        mqttClient.subscribe(TOPIC_SWITCH.c_str(), 1);
        mqttClient.subscribe(TOPIC_SWITCH_SYNC.c_str(), 1);
        mqttClient.subscribe(TOPIC_THRESHOLD.c_str(), 1);
        mqttClient.subscribe(TOPIC_RECONNECT.c_str(), 1);

        // Publish status online dengan retain flag
        mqttClient.publish(TOPIC_STATUS.c_str(), statusPayload.c_str(), true);
    } else {
        Serial.print("[MQTT] Gagal terhubung. Kode Error=");
        Serial.println(mqttClient.state());
        char mqttError[17];
        snprintf(mqttError, sizeof(mqttError), "MQTT Err: %d", mqttClient.state());
        printLcdLine(0, "MQTT Putus");
        printLcdLine(1, mqttError);
    }
}

// ============================================================================
//  PENYIMPANAN FLASH NON-VOLATILE (NVS PREFERENCES)
// ============================================================================
void loadSavedThresholds() {
    preferences.begin("smartsocket", false);
    thresholds.max_voltage     = preferences.getFloat("v_max", 245.0f);
    thresholds.max_current     = preferences.getFloat("c_max", 15.5f);
    thresholds.max_temperature = preferences.getFloat("t_max", 65.0f);
    thresholds.max_smoke_ppm   = preferences.getFloat("s_max", 995.0f);
    thresholds.device_interval = preferences.getUInt("d_int", 5);
    preferences.end();

    // Validasi nilai dari flash jika sebelumnya kosong atau data acak
    if (isnan(thresholds.max_voltage) || thresholds.max_voltage <= 50.0f || thresholds.max_voltage > 300.0f) {
        thresholds.max_voltage = 245.0f;
    }
    if (isnan(thresholds.max_current) || thresholds.max_current <= 0.5f || thresholds.max_current > 100.0f) {
        thresholds.max_current = 15.5f;
    }
    if (isnan(thresholds.max_temperature) || thresholds.max_temperature <= 10.0f || thresholds.max_temperature > 120.0f) {
        thresholds.max_temperature = 65.0f;
    }
    if (isnan(thresholds.max_smoke_ppm) || thresholds.max_smoke_ppm <= 50.0f || thresholds.max_smoke_ppm > 5000.0f) {
        thresholds.max_smoke_ppm = 995.0f;
    }
    if (thresholds.device_interval < 1 || thresholds.device_interval > 3600) {
        thresholds.device_interval = 5;
    }
    telemetryIntervalMs = thresholds.device_interval * 1000UL;

    Serial.println("[NVS] Ambang batas keamanan & interval berhasil dimuat dari Flash:");
    Serial.printf("  Voltage=%.1fV, Current=%.1fA, Temp=%.1fC, Smoke=%.0f ppm, Interval Device=%u s\n",
                  thresholds.max_voltage, thresholds.max_current,
                  thresholds.max_temperature, thresholds.max_smoke_ppm,
                  thresholds.device_interval);
}

void saveThresholds() {
    preferences.begin("smartsocket", false);
    preferences.putFloat("v_max", thresholds.max_voltage);
    preferences.putFloat("c_max", thresholds.max_current);
    preferences.putFloat("t_max", thresholds.max_temperature);
    preferences.putFloat("s_max", thresholds.max_smoke_ppm);
    preferences.putUInt("d_int", thresholds.device_interval);
    preferences.end();
    Serial.println("[NVS] Ambang batas keamanan & interval berhasil disimpan ke Flash!");
}

// ============================================================================
//  LCD 16x2 I2C MULTI-PAGE INSIGHT VIEW
// ============================================================================
void printLcdLine(uint8_t row, const char* text) {
    char padded[LCD_COLUMNS + 1];
    snprintf(padded, sizeof(padded), "%-16.16s", text);
    lcd.setCursor(0, row);
    lcd.print(padded);
}

void showLcdAlert(const char* type) {
    lcd.clear();
    printLcdLine(0, "! ALARM TRIP !");
    if (strcmp(type, "OVER_CURRENT") == 0) {
        printLcdLine(1, "OVERLOAD BEBAN");
    } else if (strcmp(type, "OVER_VOLTAGE") == 0) {
        printLcdLine(1, "TEGANGAN TINGGI");
    } else if (strcmp(type, "OVER_TEMPERATURE") == 0) {
        printLcdLine(1, "SUHU TINGGI BOX");
    } else if (strcmp(type, "SMOKE_DETECTED") == 0) {
        printLcdLine(1, "BAHAYA ASAP MQ2");
    } else if (strcmp(type, "TRIP LOCKOUT") == 0) {
        printLcdLine(1, "KUNCI TRIP AKTIF");
    } else {
        printLcdLine(1, type);
    }
    lcdAlertUntilMs = millis() + 4500; // Tahan tampilan darurat selama 4,5 detik
}

void updateLcd(unsigned long now) {
    // Jika sedang dalam masa penahanan alert darurat, jangan ubah tampilan
    if (now < lcdAlertUntilMs) {
        return;
    }

    // Rotasi halaman sesuai interval (2,5 detik per halaman)
    if (now - lastLcdPageMs < LCD_PAGE_INTERVAL_MS) {
        return;
    }

    lastLcdPageMs = now;
    char line1[LCD_COLUMNS + 1];
    char line2[LCD_COLUMNS + 1];

    switch (lcdPage) {
        case 0: // INSIGHT 1: Status Jaringan & Broker MQTT
            snprintf(line1, sizeof(line1), "WiFi:%-3s RSSI:%3d",
                     WiFi.status() == WL_CONNECTED ? "OK" : "OFF",
                     WiFi.status() == WL_CONNECTED ? WiFi.RSSI() : 0);
            snprintf(line2, sizeof(line2), "MQTT:%-11s",
                     mqttClient.connected() ? "TERHUBUNG" : "OFFLINE");
            break;

        case 1: // INSIGHT 2: Realtime Soket 1 (Relay, V, I, P)
            snprintf(line1, sizeof(line1), "S1[%-3s] %5.1fV",
                     relay1State ? "ON " : "OFF", latestSensor.voltage1);
            snprintf(line2, sizeof(line2), "%4.2fA %5.1fW",
                     latestSensor.current1, latestSensor.power1);
            break;

        case 2: // INSIGHT 3: Realtime Soket 2 (Relay, V, I, P)
            snprintf(line1, sizeof(line1), "S2[%-3s] %5.1fV",
                     relay2State ? "ON " : "OFF", latestSensor.voltage2);
            snprintf(line2, sizeof(line2), "%4.2fA %5.1fW",
                     latestSensor.current2, latestSensor.power2);
            break;

        case 3: // INSIGHT 4: Total Beban Daya & Akumulasi Energi
            snprintf(line1, sizeof(line1), "TOT P:%6.1f W",
                     latestSensor.power1 + latestSensor.power2);
            snprintf(line2, sizeof(line2), "TOT E:%6.3fkWh",
                     latestSensor.energy1 + latestSensor.energy2);
            break;

        case 4: // INSIGHT 5: Kualitas Daya Listrik (Frekuensi & Faktor Daya)
            snprintf(line1, sizeof(line1), "FREQ: %4.1f Hz",
                     latestSensor.freq1 > 0 ? latestSensor.freq1 : (latestSensor.freq2 > 0 ? latestSensor.freq2 : 50.0f));
            snprintf(line2, sizeof(line2), "PF1:%.2f PF2:%.2f",
                     latestSensor.pf1, latestSensor.pf2);
            break;

        case 5: // INSIGHT 6: Keselamatan Enclosure (Suhu DS18B20 & Asap MQ-2)
            snprintf(line1, sizeof(line1), "SUHU: %4.1f%cC",
                     latestSensor.temperature, '\x01');
            snprintf(line2, sizeof(line2), "ASAP: %4.0f PPM",
                     latestSensor.smokePpm);
            break;

        case 6: // INSIGHT 7: Status Proteksi & Saklar Sistem
            snprintf(line1, sizeof(line1), "PROTEKSI:%-7s",
                     latestSensor.isTripped ? "TRIP!" : "NORMAL");
            snprintf(line2, sizeof(line2), "R1:%-3s | R2:%-3s",
                     relay1State ? "ON" : "OFF",
                     relay2State ? "ON" : "OFF");
            break;

        default:
            lcdPage = 0;
            return;
    }

    printLcdLine(0, line1);
    printLcdLine(1, line2);

    // Rotasi ke insight berikutnya (0 sampai 6)
    lcdPage = (lcdPage + 1) % 7;
}

// ============================================================================
//  CALLBACK & HANDLER PERINTAH MQTT
// ============================================================================
void mqttCallback(char* topic, byte* payload, unsigned int length) {
    String topicStr = String(topic);
    String payloadStr;
    payloadStr.reserve(length + 1);
    for (unsigned int i = 0; i < length; i++) {
        payloadStr += (char)payload[i];
    }

    Serial.println("[MQTT] Pesan Masuk [" + topicStr + "]: " + payloadStr);

    ALLOC_JSON_DOC(doc, 512);
    DeserializationError err = deserializeJson(doc, payloadStr);
    if (err) {
        Serial.print("[MQTT] JSON parse gagal: ");
        Serial.println(err.c_str());
        return;
    }

    if (topicStr == TOPIC_SWITCH) {
        int socketNumber = doc["socket_number"] | 0;
        const char* stateStr = doc["state"] | "OFF";
        processSwitchCommand(socketNumber, stateStr);
    } else if (topicStr == TOPIC_SWITCH_SYNC) {
        Serial.println("[Sync] Menerima data status saklar relay terbaru dari server (Init/Sync):");
        const char* s1 = doc["socket_1"] | "";
        const char* s2 = doc["socket_2"] | "";
        if (strlen(s1) > 0) {
            bool on1 = (strcasecmp(s1, "ON") == 0);
            setRelayOutput(1, on1);
            Serial.printf("  Socket 1 disinkronkan => %s\n", on1 ? "ON" : "OFF");
        }
        if (strlen(s2) > 0) {
            bool on2 = (strcasecmp(s2, "ON") == 0);
            setRelayOutput(2, on2);
            Serial.printf("  Socket 2 disinkronkan => %s\n", on2 ? "ON" : "OFF");
        }
        publishTelemetry();
        updateLcd(millis());
    } else if (topicStr == TOPIC_THRESHOLD) {
        float v_max = doc["max_voltage"] | 0.0f;
        float c_max = doc["max_current"] | 0.0f;
        float t_max = doc["max_temperature"] | 0.0f;
        float s_max = doc["max_smoke_ppm"] | 0.0f;
        int dev_interval = doc["device_interval"] | doc["telemetry_interval"] | 0;

        if (v_max <= 0.0f && doc.containsKey("voltage")) v_max = doc["voltage"] | 0.0f;
        if (c_max <= 0.0f && doc.containsKey("current")) c_max = doc["current"] | 0.0f;
        if (t_max <= 0.0f && doc.containsKey("temperature")) t_max = doc["temperature"] | 0.0f;
        if (s_max <= 0.0f && doc.containsKey("smoke_ppm")) s_max = doc["smoke_ppm"] | 0.0f;

        processThresholdCommand(v_max, c_max, t_max, s_max, dev_interval);
    } else if (topicStr == TOPIC_RECONNECT) {
        processReconnectCommand();
    }
}

void processSwitchCommand(int socketNumber, const char* stateStr) {
    String state = String(stateStr);
    state.toUpperCase();
    bool turnOn = (state == "ON");

    // Jika sistem masih dalam kondisi bahaya aktif (misal asap tinggi/overvoltage), tolak switch ON
    if (turnOn && latestSensor.isTripped) {
        Serial.println("[Relay] PERINGATAN: Perintah ON ditolak karena sistem proteksi sedang aktif!");
        showLcdAlert("TRIP LOCKOUT");
        publishTelemetry(); // Perbarui web agar toggle switch kembali sinkron ke OFF
        return;
    }

    if (socketNumber == 1) {
        setRelayOutput(1, turnOn);
        Serial.printf("[Relay] Socket 1 => %s\n", turnOn ? "ON" : "OFF");
    } else if (socketNumber == 2) {
        setRelayOutput(2, turnOn);
        Serial.printf("[Relay] Socket 2 => %s\n", turnOn ? "ON" : "OFF");
    }

    // Segera publish telemetri agar dashboard web update instan (< 100ms)
    publishTelemetry();
    updateLcd(millis());
}

void processThresholdCommand(float v_max, float c_max, float t_max, float s_max, int dev_interval) {
    bool updated = false;
    if (v_max > 0) { thresholds.max_voltage = v_max; updated = true; }
    if (c_max > 0) { thresholds.max_current = c_max; updated = true; }
    if (t_max > 0) { thresholds.max_temperature = t_max; updated = true; }
    if (s_max > 0) { thresholds.max_smoke_ppm = s_max; updated = true; }
    if (dev_interval >= 1 && dev_interval <= 3600) {
        thresholds.device_interval = dev_interval;
        telemetryIntervalMs = (unsigned long)dev_interval * 1000UL;
        updated = true;
    }

    Serial.println("[Threshold] Nilai batas proteksi & interval diperbarui dari web:");
    Serial.printf("  Voltage=%.1fV, Current=%.1fA, Temp=%.1fC, Smoke=%.0f ppm, Interval Device=%u s\n",
                  thresholds.max_voltage, thresholds.max_current,
                  thresholds.max_temperature, thresholds.max_smoke_ppm,
                  thresholds.device_interval);

    if (updated) {
        saveThresholds();
    }
}

void processReconnectCommand() {
    Serial.println("[WiFi] Perintah rekoneksi diterima dari dashboard.");
    publishStatus(false);
    WiFi.disconnect();
    lastWifiRetryMs = millis();
    WiFi.begin(WIFI_SSID, WIFI_PASS);
}

// ============================================================================
//  PEMBACAAN SENSOR (PZEM, DS18B20, MQ-2)
// ============================================================================
float readTemperature() {
    float t = sensors.getTempCByIndex(0);
    // Request konversi asinkron untuk pembacaan siklus berikutnya (0ms blocking)
    sensors.requestTemperatures();

    // Validasi nilai sensor DS18B20 (-127 = disconnected)
    if (t == DEVICE_DISCONNECTED_C || t < -55.0f || t > 125.0f || isnan(t)) {
        return 0.0f;
    }
    return t;
}

float readSmokePPM() {
    // Multi-sampling 10 kali untuk meredam noise ADC ESP32
    long sum = 0;
    for (int i = 0; i < 10; i++) {
        sum += analogRead(MQ2_PIN);
        delayMicroseconds(150);
    }
    float raw = (float)sum / 10.0f;

    // Kalibrasi standar MQ-2: Konversi 12-bit ADC (0-4095) ke 0-2000 ppm
    float ppm = (raw / 4095.0f) * 2000.0f;
    if (ppm < 0.0f) ppm = 0.0f;
    return ppm;
}

float sanitizeReading(float value) {
    return (isnan(value) || isinf(value) || value < 0.0f) ? 0.0f : value;
}

void readAllSensors() {
    // 1. Baca sensor PZEM 1 (Soket 1)
    // Optimasi: jika voltage gagal / 0, skip metrik lainnya agar tidak kena serial delay
    float v1 = sanitizeReading(pzem1.voltage());
    float c1 = 0, p1 = 0, e1 = 0, f1 = 0, pf1 = 0;
    if (v1 > 0) {
        c1 = sanitizeReading(pzem1.current());
        p1 = sanitizeReading(pzem1.power());
        e1 = sanitizeReading(pzem1.energy());
        f1 = sanitizeReading(pzem1.frequency());
        pf1 = sanitizeReading(pzem1.pf());
    }

    // 2. Baca sensor PZEM 2 (Soket 2)
    float v2 = sanitizeReading(pzem2.voltage());
    float c2 = 0, p2 = 0, e2 = 0, f2 = 0, pf2 = 0;
    if (v2 > 0) {
        c2 = sanitizeReading(pzem2.current());
        p2 = sanitizeReading(pzem2.power());
        e2 = sanitizeReading(pzem2.energy());
        f2 = sanitizeReading(pzem2.frequency());
        pf2 = sanitizeReading(pzem2.pf());
    }

    // 3. Suhu Enclosure DS18B20 & Asap MQ-2
    float temp  = readTemperature();
    float smoke = readSmokePPM();

    latestSensor.voltage1    = v1;
    latestSensor.current1    = c1;
    latestSensor.power1      = p1;
    latestSensor.energy1     = e1;
    latestSensor.freq1       = f1;
    latestSensor.pf1         = pf1;

    latestSensor.voltage2    = v2;
    latestSensor.current2    = c2;
    latestSensor.power2      = p2;
    latestSensor.energy2     = e2;
    latestSensor.freq2       = f2;
    latestSensor.pf2         = pf2;

    latestSensor.temperature = temp;
    latestSensor.smokePpm    = smoke;
}

// ============================================================================
//  PUBLISH TELEMETRI
// ============================================================================
void publishTelemetry() {
    // Buat payload JSON sesuai format baku backend Laravel (kompatibel ArduinoJson v6 & v7)
    ALLOC_JSON_DOC(doc, 1024);
    doc["device_id"] = DEVICE_UID;
    time_t nowSec = time(nullptr);
    doc["timestamp"] = (nowSec > 1000000000) ? (uint32_t)nowSec : (uint32_t)(millis() / 1000);

    doc["environmental"]["temperature"] = latestSensor.temperature;
    doc["environmental"]["smoke_ppm"]    = latestSensor.smokePpm;

    doc["sockets"]["socket_1"]["relay_state"]  = relay1State ? "ON" : "OFF";
    doc["sockets"]["socket_1"]["voltage"]      = latestSensor.voltage1;
    doc["sockets"]["socket_1"]["current"]      = latestSensor.current1;
    doc["sockets"]["socket_1"]["power"]        = latestSensor.power1;
    doc["sockets"]["socket_1"]["energy"]       = latestSensor.energy1;
    doc["sockets"]["socket_1"]["frequency"]    = latestSensor.freq1;
    doc["sockets"]["socket_1"]["power_factor"] = latestSensor.pf1;

    doc["sockets"]["socket_2"]["relay_state"]  = relay2State ? "ON" : "OFF";
    doc["sockets"]["socket_2"]["voltage"]      = latestSensor.voltage2;
    doc["sockets"]["socket_2"]["current"]      = latestSensor.current2;
    doc["sockets"]["socket_2"]["power"]        = latestSensor.power2;
    doc["sockets"]["socket_2"]["energy"]       = latestSensor.energy2;
    doc["sockets"]["socket_2"]["frequency"]    = latestSensor.freq2;
    doc["sockets"]["socket_2"]["power_factor"] = latestSensor.pf2;

    char buffer[1024];
    serializeJson(doc, buffer);

    if (mqttClient.connected()) {
        // Publish telemetri TANPA retain flag
        mqttClient.publish(TOPIC_TELEMETRY.c_str(), buffer, false);
        Serial.print("[Telemetri] ");
        Serial.println(buffer);
    }
}

// ============================================================================
//  EVALUASI THRESHOLD & ALERT (DENGAN FLOOD PROTECTION & TRIP GRANULAR)
// ============================================================================
void evaluateThresholds() {
    float v1    = latestSensor.voltage1;
    float v2    = latestSensor.voltage2;
    float c1    = latestSensor.current1;
    float c2    = latestSensor.current2;
    float temp  = latestSensor.temperature;
    float smoke = latestSensor.smokePpm;

    unsigned long now = millis();
    bool canSendAlert = (now - lastAlertSentMs >= ALERT_COOLDOWN_MS);

    bool tripSocket1 = false;
    bool tripSocket2 = false;

    // 1. Over Voltage (Tegangan Berlebih PLN) -> Matikan kedua soket
    if (thresholds.max_voltage > 0) {
        if (v1 >= thresholds.max_voltage || v2 >= thresholds.max_voltage) {
            float worstV = (v1 >= thresholds.max_voltage) ? v1 : v2;
            int tripSocket = (v1 >= thresholds.max_voltage && v2 >= thresholds.max_voltage) ? 0 : ((v1 >= thresholds.max_voltage) ? 1 : 2);
            tripSocket1 = true;
            tripSocket2 = true;
            if (canSendAlert || lastAlertSentType != "OVER_VOLTAGE") {
                sendAlert("OVER_VOLTAGE", worstV, thresholds.max_voltage, "AUTO_CUTOFF_ALL", tripSocket);
                lastAlertSentMs = now;
                lastAlertSentType = "OVER_VOLTAGE";
            }
        }
    }

    // 2. Over Current (Arus Beban Berlebih) -> Matikan soket yang overload
    if (thresholds.max_current > 0) {
        if (c1 >= thresholds.max_current) {
            tripSocket1 = true;
            if (canSendAlert || lastAlertSentType != "OVER_CURRENT_1") {
                sendAlert("OVER_CURRENT", c1, thresholds.max_current, "AUTO_CUTOFF_SOCKET_1", 1);
                lastAlertSentMs = now;
                lastAlertSentType = "OVER_CURRENT_1";
            }
        }
        if (c2 >= thresholds.max_current) {
            tripSocket2 = true;
            if (canSendAlert || lastAlertSentType != "OVER_CURRENT_2") {
                sendAlert("OVER_CURRENT", c2, thresholds.max_current, "AUTO_CUTOFF_SOCKET_2", 2);
                lastAlertSentMs = now;
                lastAlertSentType = "OVER_CURRENT_2";
            }
        }
    }

    // 3. Smoke Detected (Asap / Gas MQ-2) -> Bahaya Kebakaran, Matikan SEMUA soket
    if (thresholds.max_smoke_ppm > 0 && smoke >= thresholds.max_smoke_ppm) {
        tripSocket1 = true;
        tripSocket2 = true;
        if (canSendAlert || lastAlertSentType != "SMOKE_DETECTED") {
            sendAlert("SMOKE_DETECTED", smoke, thresholds.max_smoke_ppm, "EMERGENCY_ALERT", 0);
            lastAlertSentMs = now;
            lastAlertSentType = "SMOKE_DETECTED";
        }
    }

    // 4. Over Temperature (Suhu Box Enclosure DS18B20)
    if (thresholds.max_temperature > 0 && temp >= thresholds.max_temperature) {
        if (temp >= thresholds.max_temperature + 10.0f) {
            tripSocket1 = true;
            tripSocket2 = true;
        }
        if (canSendAlert || lastAlertSentType != "OVER_TEMPERATURE") {
            sendAlert("OVER_TEMPERATURE", temp, thresholds.max_temperature, "WARNING_LOGGED", 0);
            lastAlertSentMs = now;
            lastAlertSentType = "OVER_TEMPERATURE";
        }
    }

    // Eksekusi pemutusan proteksi jika ada kondisi trip
    if (tripSocket1) {
        setRelayOutput(1, false);
    }
    if (tripSocket2) {
        setRelayOutput(2, false);
    }

    if (tripSocket1 || tripSocket2) {
        latestSensor.isTripped = true;
        Serial.println("[Safety] Trip proteksi terpicu! Relay dimatikan.");
        publishTelemetry();
    } else {
        // Reset trip lockout jika kondisi lingkungan telah normal
        if (latestSensor.isTripped && temp < thresholds.max_temperature && smoke < thresholds.max_smoke_ppm) {
            latestSensor.isTripped = false;
            lastAlertSentType = "";
        }
    }
}

void sendAlert(const char* type, float value, float threshold, const char* action, int socketNumber) {
    showLcdAlert(type);

    ALLOC_JSON_DOC(doc, 256);
    doc["device_id"]    = DEVICE_UID;
    doc["alert_type"]   = type;
    doc["value"]        = value;
    doc["threshold"]    = threshold;
    doc["action_taken"] = action;
    if (socketNumber > 0) {
        doc["socket_number"] = socketNumber;
    }
    time_t nowSec = time(nullptr);
    doc["timestamp"]    = (nowSec > 1000000000) ? (uint32_t)nowSec : (uint32_t)(millis() / 1000);

    char buffer[256];
    serializeJson(doc, buffer);

    if (mqttClient.connected()) {
        // Publish alert tanpa retain flag agar tidak berulang saat subscriber baru join
        mqttClient.publish(TOPIC_ALERT.c_str(), buffer, false);
        Serial.print("[Alert] ");
        Serial.println(buffer);
    }
}

// ============================================================================
//  STATUS (LWT & HEARTBEAT)
// ============================================================================
String buildStatusPayload(bool online) {
    ALLOC_JSON_DOC(doc, 512);
    doc["device_id"]        = DEVICE_UID;
    doc["status"]           = online ? "online" : "offline";
    doc["ip_address"]       = WiFi.status() == WL_CONNECTED ? WiFi.localIP().toString() : "0.0.0.0";
    doc["mac_address"]      = WiFi.macAddress();
    doc["wifi_rssi"]        = WiFi.status() == WL_CONNECTED ? WiFi.RSSI() : 0;
    doc["firmware_version"] = FIRMWARE_VERSION;
    time_t nowSec2 = time(nullptr);
    doc["timestamp"]        = (nowSec2 > 1000000000) ? (uint32_t)nowSec2 : (uint32_t)(millis() / 1000);

    char buffer[512];
    serializeJson(doc, buffer);
    return String(buffer);
}

void publishStatus(bool online) {
    String payload = buildStatusPayload(online);
    if (mqttClient.connected()) {
        // Status dipublish DENGAN retain flag (QoS 1, retain: true)
        mqttClient.publish(TOPIC_STATUS.c_str(), payload.c_str(), true);
        Serial.print("[Status] ");
        Serial.println(payload);
    }
}
