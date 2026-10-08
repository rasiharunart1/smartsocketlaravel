# Tanya Jawab Sidang

1. **Mengapa memakai ESP32?** ESP32 dipakai sebagai pengendali perangkat karena firmware menyediakan WiFi, GPIO relay, ADC MQ-2, UART untuk dua PZEM, I2C LCD, dan NVS.
2. **Mengapa MQTT?** Kode memakai topik publish/subscribe sehingga telemetri dan perintah dapat berjalan dua arah tanpa polling perangkat oleh Laravel.
3. **Mengapa TLS port 8883?** Konfigurasi firmware dan Laravel menunjukkan koneksi broker TLS pada port 8883 untuk melindungi komunikasi transport.
4. **QoS apa yang dipakai?** Laravel subscribe telemetry QoS 0, status/alarm QoS 1, dan publish perintah QoS 1. API publish firmware tidak menentukan QoS untuk telemetry/alert; TODO: perlu konfirmasi QoS efektif PubSubClient.
5. **Mengapa telemetri tidak selalu ditulis ke DB?** Daemon memakai `log_interval` minimum 3 detik untuk mengurangi penulisan, tetapi tetap menyimpan ketika relay berubah atau ambang dilanggar.
6. **Berapa interval baca sensor?** 1 detik. Interval telemetri awal 5 detik dan dapat diubah dari server (1–3600 detik).
7. **Bagaimana sistem menangani WiFi putus?** Firmware mencoba kembali tiap 10 detik dan menunda MQTT sampai WiFi tersambung.
8. **Bagaimana menangani MQTT putus?** Firmware mencoba reconnect tiap 5 detik; daemon Laravel juga menangkap exception dan menunggu 5 detik sebelum mengulang.
9. **Bagaimana status offline dideteksi?** ESP32 memasang LWT retained pada topik status; daemon memperbarui data perangkat dari pesan status.
10. **Bagaimana proteksi beban?** Firmware membandingkan tegangan, arus, suhu, dan asap terhadap ambang, lalu dapat memutus relay dan menerbitkan alert.
11. **Mengapa perintah ON bisa ditolak?** Saat `isTripped` aktif, `processSwitchCommand` menolak ON untuk mencegah penyalaan ketika bahaya masih ada.
12. **Bagaimana state relay bertahan setelah reboot?** State relay dan ambang disimpan dengan `Preferences` di NVS lalu dimuat dalam `setup()`.
13. **Bagaimana kredensial MQTT di backend dilindungi?** Accessor/mutator `Device` mengenkripsi username dan password memakai helper Laravel; atribut itu juga disembunyikan dari serialisasi model.
14. **Apa keterbatasan keamanan yang terlihat?** Firmware memanggil `wifiClient.setInsecure()` dan Laravel menonaktifkan verifikasi peer/name TLS. Ini perlu diperbaiki untuk produksi dengan CA yang tervalidasi.
15. **Apa keterbatasan pengukuran?** MQ-2 dikonversi linear dari ADC ke 0–2000 ppm dalam kode; tanpa prosedur kalibrasi yang terdokumentasi, nilai harus dianggap indikatif. TODO: perlu konfirmasi kalibrasi fisik dan akurasi sensor.
