#!/usr/bin/env bash
# Pasang jadwal sinkron data siswa TSM Superapp (sekali saja).
# Jalankan:  sudo bash /home/ngemis/cms-tsm/scripts/pasang-jadwal-sinkron.sh
#
# Server ini belum punya paket cron, jadi jadwal dibuat dengan systemd timer.
set -euo pipefail

APP=/home/ngemis/cms-tsm
USER_APP=ngemis
PHP=$(command -v php || echo /usr/bin/php)

cat > /etc/systemd/system/tsm-siswa-sync.service <<EOF
[Unit]
Description=Sinkron data siswa TSM Superapp dari aplikasi Data Induk

[Service]
Type=oneshot
User=$USER_APP
WorkingDirectory=$APP
ExecStart=$PHP artisan siswa:sync
EOF

cat > /etc/systemd/system/tsm-siswa-sync.timer <<'EOF'
[Unit]
Description=Jadwal harian sinkron data siswa TSM Superapp

[Timer]
OnCalendar=*-*-* 02:30:00
Persistent=true

[Install]
WantedBy=timers.target
EOF

systemctl daemon-reload
systemctl enable --now tsm-siswa-sync.timer
echo
echo "Terpasang. Uji sekarang:  sudo systemctl start tsm-siswa-sync.service"
echo "Lihat jadwal:             systemctl list-timers tsm-siswa-sync.timer"
echo "Lihat log:                journalctl -u tsm-siswa-sync.service -n 30"
