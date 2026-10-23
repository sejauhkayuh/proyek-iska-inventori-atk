#!/usr/bin/env bash
# Menyiapkan kedua layanan: .env, phpunit.xml, composer require, migrasi, test.
# Jalankan dari folder root repositori di terminal Ubuntu (WSL): bash siapkan.sh
set -e
ROOT="$(cd "$(dirname "$0")" && pwd)"

gabung_env() { # $1 = folder layanan, $2 = berkas .env atau .env.example
  local f="$1/$2"
  [ -f "$f" ] || return 0
  while IFS= read -r baris; do
    [ -z "$baris" ] && continue
    local kunci="${baris%%=*}"
    if grep -q "^${kunci}=" "$f"; then
      sed -i "s|^${kunci}=.*|${baris}|" "$f"
    else
      echo "$baris" >> "$f"
    fi
  done < "$1/env-tambahan.txt"
}

atur_phpunit() { # $1 = folder layanan
  local f="$1/phpunit.xml"
  # buka komentar bila baris SQLite dikomentari
  sed -i -E 's#<!-- *(<env name="DB_(CONNECTION|DATABASE)" value="(sqlite|:memory:)"/>) *-->#\1#' "$f"
  if ! grep -q 'value=":memory:"' "$f"; then
    sed -i -E 's#<env name="DB_DATABASE" value="testing"/>#<env name="DB_CONNECTION" value="sqlite"/>\n        <env name="DB_DATABASE" value=":memory:"/>#' "$f"
  fi
  grep -q 'value=":memory:"' "$f" || { echo "PERINGATAN: atur DB_CONNECTION=sqlite dan DB_DATABASE=:memory: di $f secara manual"; }
}

siapkan() { # $1 = nama folder layanan
  local dir="$ROOT/$1"
  [ -d "$dir" ] || { echo "Folder $dir tidak ada. Buat proyek Laravel-nya dulu (lihat README)."; exit 1; }
  echo "=== $1 ==="
  gabung_env "$dir" .env
  gabung_env "$dir" .env.example
  atur_phpunit "$dir"
  ( cd "$dir"
    ./vendor/bin/sail up -d
    grep -q '^APP_KEY=base64' .env || ./vendor/bin/sail artisan key:generate --no-interaction
    ./vendor/bin/sail composer require php-amqplib/php-amqplib --no-interaction
    ./vendor/bin/sail artisan migrate --force
    ./vendor/bin/sail artisan test
  )
}

siapkan inventori-atk
siapkan layanan-notifikasi

echo
echo "Selesai."
echo "  Inventori ATK      : http://localhost/api/v1/barang   (atau port APP_PORT di .env)"
echo "  Layanan Notifikasi : http://localhost:8081"
echo "  RabbitMQ Management: http://localhost:15672  (user atk / password atk)"
