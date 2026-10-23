# Kontrak Event — Inventori ATK PT Ina Nata Boga

Semua event diterbitkan oleh **Layanan Inventori ATK** (`inventori-atk`) ke exchange
`atk.events` (tipe **topic**, durable) dan diterima oleh **Layanan Notifikasi**
(`layanan-notifikasi`). Pesan berformat JSON, `delivery_mode` persistent.

Setiap pesan membawa properti AMQP: `message_id` (UUID, kunci idempotensi), `type` (nama event),
dan `timestamp`. Nama event berbentuk lampau karena menyatakan fakta yang sudah terjadi.
Nama dan tipe field tidak boleh diubah tanpa menaikkan versi kontrak.

## Event

| Event | Exchange | Routing key | Penerbit | Penerima |
| --- | --- | --- | --- | --- |
| PermintaanAtkDiajukan | atk.events | `permintaan.diajukan` | inventori-atk | layanan-notifikasi (queue `notifikasi.permintaan`) |
| PermintaanAtkDisetujui | atk.events | `permintaan.disetujui` | inventori-atk | layanan-notifikasi (queue `notifikasi.permintaan`) |
| StokDisesuaikan | atk.events | `stok.barang_masuk`, `stok.barang_keluar`, `stok.koreksi` | inventori-atk | layanan-notifikasi (queue `notifikasi.stok`) |

## Payload

**PermintaanAtkDiajukan**

| Field | Tipe | Keterangan |
| --- | --- | --- |
| nomor | string | Nomor permintaan, mis. `PRM-0001` |
| kode_barang | string | Kode barang, mis. `ATK-001` |
| jumlah | integer | Jumlah diminta (>= 1) |
| pemohon | string | Nama pemohon |
| bagian | string | Bagian/departemen pemohon |
| terjadi_pada | string (date-time) | Waktu kejadian, ISO 8601 |

**PermintaanAtkDisetujui**

| Field | Tipe | Keterangan |
| --- | --- | --- |
| nomor | string | Nomor permintaan |
| kode_barang | string | Kode barang |
| jumlah | integer | Jumlah yang disetujui |
| pemohon | string | Nama pemohon |
| disetujui_oleh | string | Petugas yang menyetujui |
| terjadi_pada | string (date-time) | Waktu kejadian, ISO 8601 |

**StokDisesuaikan**

| Field | Tipe | Keterangan |
| --- | --- | --- |
| kode | string | Kode barang |
| perubahan | integer | Positif menambah, negatif mengurangi |
| alasan | string | `barang_masuk`, `barang_keluar`, atau `koreksi` |
| stok_sesudah | integer | Stok setelah penyesuaian |
| stok_minimum | integer | Batas stok menipis barang tersebut |
| terjadi_pada | string (date-time) | Waktu kejadian, ISO 8601 |

## Topologi

| Queue | Binding | Dead letter | Consumer |
| --- | --- | --- | --- |
| `notifikasi.permintaan` | `permintaan.*` | `atk.dlx` (fanout) -> `notifikasi.permintaan.dlq` | `php artisan notifikasi:permintaan` |
| `notifikasi.stok` | `stok.*` | tidak ada (pesan boleh dibuang) | `php artisan notifikasi:stok` |

## Jaminan pengiriman

- **At-least-once.** Penerbit memakai *transactional outbox*: pesan dicatat di tabel `outbox` pada transaksi
  basis data yang sama dengan perubahan data, lalu diterbitkan oleh `php artisan outbox:relay`.
  Bila RabbitMQ mati, pesan menunggu di outbox dan dikirim setelah broker pulih. Pesan bisa terkirim lebih dari sekali.
- **Idempoten.** Consumer mencatat `message_id` di tabel `log_notifikasi` (unik); pesan duplikat dilewati.
- **Urutan.** Relay mengirim urut `id` dan berhenti di pesan pertama yang gagal.
- **Pesan tidak valid** (JSON rusak, field wajib hilang, tanpa `message_id`) ditolak tanpa requeue dan masuk DLQ.
