#!/usr/bin/env bash

STUDENT_ID="498113"
WEB_01_IP="54.173.43.181"
WEB_02_IP="100.26.136.82"
LB_IP="54.144.155.174"

# Update package list
apt update

# Install HAProxy
apt install -y haproxy

# Backup default HAProxy configuration
cp /etc/haproxy/haproxy.cfg /etc/haproxy/haproxy.cfg.bak

# Configure HAProxy
cat <<EOF
EOF > /etc/haproxy/haproxy.cfg
global
    log /dev/log    local0
    log /dev/log    local1 notice
    chroot /var/lib/haproxy
    stats socket /run/haproxy/admin.sock mode 660 level admin
    stats timeout 30s
    user haproxy
    group haproxy
    daemon

defaults
    log     global
    mode    http
    option  httplog
    option  dontlognull
    timeout connect 5000
    timeout client  50000
    timeout server  50000

frontend http_front
    bind *:80
    stats uri /haproxy?stats
    default_backend http_back

backend http_back
    balance roundrobin
    server ${STUDENT_ID}-web-01 ${WEB_01_IP}:80 check
    server ${STUDENT_ID}-web-02 ${WEB_02_IP}:80 check
EOF

# Enable HAProxy service
systemctl enable haproxy

# Restart HAProxy service
systemctl restart haproxy

# Confirm HAProxy service status
systemctl status haproxy
