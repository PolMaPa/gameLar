#!/usr/bin/env bash
#
# GameHub · Aprovisionamiento de un VPS Ubuntu (Fase 7)
#
# Uso, en el VPS, como root o con sudo:
#
#   sudo bash deploy/provision.sh
#   sudo bash deploy/provision.sh ubuntu      # usuario que desplegará
#
# Qué hace (idempotente, se puede repetir sin efectos raros):
#   1. Instala Docker Engine y el plugin Docker Compose desde el repositorio oficial.
#   2. Añade el usuario de despliegue al grupo "docker".
#   3. Configura UFW: solo SSH (22), HTTP (80) y HTTPS (443).
#   4. Crea /opt/gamehub, donde vivirá el repositorio y el .env.production.
#
# Qué NO hace, a propósito:
#   - No modifica sshd ni las claves autorizadas: así no puede dejarte fuera
#     del servidor. Añade tu clave pública a mano (ver docs/DEPLOY.md).
#   - No instala certificados TLS: eso requiere un dominio (Fase 8).
#   - No escribe credenciales: el .env.production se crea a mano una sola vez.

set -euo pipefail

APP_DIR="${APP_DIR:-/opt/gamehub}"
DEPLOY_USER="${1:-${SUDO_USER:-root}}"

log() {
    printf '\n==> %s\n' "$*"
}

if [ "$(id -u)" -ne 0 ]; then
    echo "ERROR: ejecuta este script como root o con sudo." >&2
    exit 1
fi

if ! id "$DEPLOY_USER" >/dev/null 2>&1; then
    echo "ERROR: el usuario '$DEPLOY_USER' no existe en este servidor." >&2
    echo "Crea uno con: adduser $DEPLOY_USER && usermod -aG sudo $DEPLOY_USER" >&2
    exit 1
fi

log "Comprobando la versión de Ubuntu"
if [ -r /etc/os-release ]; then
    # shellcheck disable=SC1091
    . /etc/os-release
    echo "    ${PRETTY_NAME:-desconocido}"
fi

log "Actualizando índices de paquetes"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y --no-install-recommends ca-certificates curl git ufw

if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    log "Docker ya está instalado: $(docker --version)"
else
    log "Instalando Docker Engine y Docker Compose (repositorio oficial)"
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
    chmod a+r /etc/apt/keyrings/docker.asc

    # shellcheck disable=SC1091
    . /etc/os-release
    printf 'deb [arch=%s signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu %s stable\n' \
        "$(dpkg --print-architecture)" "${VERSION_CODENAME}" > /etc/apt/sources.list.d/docker.list

    apt-get update -y
    apt-get install -y --no-install-recommends \
        docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi

systemctl enable --now docker

log "Añadiendo '$DEPLOY_USER' al grupo docker"
usermod -aG docker "$DEPLOY_USER"

log "Creando $APP_DIR"
mkdir -p "$APP_DIR"
chown -R "$DEPLOY_USER":"$DEPLOY_USER" "$APP_DIR"
chmod 750 "$APP_DIR"

log "Configurando el cortafuegos (UFW)"
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
ufw status verbose

log "Resumen"
echo "    Docker:  $(docker --version)"
echo "    Compose: $(docker compose version --short)"
echo "    Carpeta: $APP_DIR (propietario: $DEPLOY_USER)"
echo
echo "Siguientes pasos manuales:"
echo "  1. Añade tu clave pública a ~$DEPLOY_USER/.ssh/authorized_keys en el VPS."
echo "  2. Copia el repositorio en $APP_DIR (git clone) o deja que lo haga deploy.sh."
echo "  3. Crea $APP_DIR/.env.production a partir de .env.production.example."
echo "  4. Vuelve a iniciar sesión (o ejecuta 'newgrp docker') para usar Docker sin sudo."
