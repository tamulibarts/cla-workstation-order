today=$(date +"%Y-%m-%d-%H%M%S")
install=wsorder
filename=wsorder
domain="ssh.wpengine.net"
ssh $install@$install.$domain "wp db export - " > backup/$filename-$today.sql
