# Tanya Jawab Sidang

1. **Mengapa memakai ESP32?** ESP32 dipakai sebagai pengendali karena firmware memakai WiFi, GPIO relay, ADC MQ-2, UART untuk dua PZEM, I2C LCD, NVS, dan portal web lokal.
2. **Mengapa MQTT?** Kode memakai publish/subscribe sehingga telemetri dan perintah berjalan dua arah tanpa polling perangkat oleh Laravel.
3. **Mengapa TLS port 8883?** Konfigurasi MQTT mendukung koneksi broker TLS pada port 8883 untuk melindungi komunikasi transport.
4. **QoS apa yang dipakai?** Laravel subscribe telemetry QoS 0, status/alarm QoS 1, dan publish perintah QoS 1. API publish firmware tidak menentukan QoS telemetry/alert; TODO: perlu konfirmasi QoS efektif PubSubClient.
5. **Mengapa telemetri tidak selalu ditulis ke DB?** Daemon memakai `log_interval` minimum 3 detik untuk mengurangi penulisan, tetapi tetap menyimpan ketika relay berubah atau ambang dilanggar.
6. **Berapa interval baca sensor?** 1 detik. Interval telemetri awal 5 detik dan dapat diubah dari server (1 hingga 3600 detik).
7. **Bagaimana sistem menangani WiFi putus?** Firmware mencoba kembali tiap 10 detik. Jika WiFi putus lebih dari dua menit, portal membuka AP setup.
8. **Bagaimana menangani MQTT putus?** Firmware mencoba reconnect tiap 5 detik setelah WiFi dan host MQTT tersedia; daemon Laravel juga menunggu 5 detik setelah exception sebelum mengulang.
9. **Bagaimana status offline dideteksi?** ESP32 memasang LWT retained pada topik status; daemon memperbarui data perangkat dari pesan status.
10. **Bagaimana proteksi beban?** Firmware membandingkan tegangan, arus, suhu, dan asap terhadap ambang, lalu dapat memutus relay dan menerbitkan alert.
11. **Mengapa perintah ON bisa ditolak?** Saat `isTripped` aktif, `processSwitchCommand` menolak ON agar perangkat tidak menyala ketika bahaya masih ada.
12. **Bagaimana state relay bertahan setelah reboot?** State relay dan ambang disimpan dengan `Preferences` di NVS lalu dimuat dalam `setup()`.
13. **Bagaimana konfigurasi awal ESP32 dilakukan?** Firmware v2.6.0 menyediakan portal HTTP lokal. WiFi, MQTT, UID, NTP, dan konfigurasi lain disimpan di NVS, bukan ditanam langsung di sketch utama.
14. **Kapan mode AP setup dibuka?** Saat WiFi belum dikonfigurasi/gagal tersambung saat boot, BOOT GPIO0 ditahan tiga detik saat power-on, atau WiFi putus lebih dari dua menit.
15. **Apa keterbatasan keamanan yang terlihat?** Portal memakai Basic Authentication dan firmware memanggil `wifiClient.setInsecure()`; Laravel juga menonaktifkan verifikasi peer/name TLS. Ganti password portal bawaan dan gunakan CA tervalidasi sebelum produksi. TODO: perlu konfirmasi kalibrasi fisik dan akurasi MQ-2.
