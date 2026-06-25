#!/bin/bash
# EstrateGIA — Deploy a staging con estructura correcta
STAGING="root@200.21.254.11"
SSH_PORT=6612
SSH_PASS="Bhw61977"
LOCAL="/home/emilio/estrategia/workspace/"
REMOTE="/var/www/estrategia/"
RSYNC="sshpass -p $SSH_PASS rsync -avz -e \"ssh -o StrictHostKeyChecking=no -p $SSH_PORT\""
SSH="sshpass -p $SSH_PASS ssh -o StrictHostKeyChecking=no -p $SSH_PORT $STAGING"

echo "=== Deploy EstrateGIA v2.1 ==="
echo "Target: $STAGING:$REMOTE"

echo "[1/5] Syncing public/"
eval $RSYNC ${LOCAL}public/ ${STAGING}:${REMOTE}public/ --delete
echo "[2/5] Syncing lib/"
eval $RSYNC ${LOCAL}lib/ ${STAGING}:${REMOTE}lib/ --delete
echo "[3/5] Syncing src/"
eval $RSYNC ${LOCAL}src/ ${STAGING}:${REMOTE}src/ --delete
echo "[4/5] Syncing templates/"
eval $RSYNC ${LOCAL}templates/ ${STAGING}:${REMOTE}templates/ --delete
echo "[5/5] Syncing tests/"
eval $RSYNC ${LOCAL}tests/ ${STAGING}:${REMOTE}tests/ --delete

echo "Fixing DB credentials and BASE_PATH..."
$SSH "sed -i \"s/'db_user'.*=>.*/'db_user'     => 'app_user',/; s/'db_pass'.*=>.*/'db_pass'     => 'AgroEstrategia2026.',/\" ${REMOTE}public/index.php"
$SSH "sed -i \"s|dirname(__DIR__)|'/var/www/estrategia'|g; s|/home/emilio/estrategia/workspace|/var/www/estrategia|g\" ${REMOTE}public/index.php"

echo "Restarting PHP-FPM..."
$SSH "systemctl restart php-fpm"

echo "Verifying..."
sleep 2
curl -s "http://200.21.254.11:6611/api/health" 2>/dev/null && echo ""
echo "=== Deploy Complete ==="
