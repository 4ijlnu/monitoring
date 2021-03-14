# Icinga2 Monitoring Plugins

## Table of Contents
1. [check_nas][check_nas]
2. [check_sophos][check_sophos]
3. [check_switch][check_switch]

## check_nas
Icinga check for different NAS (currently: Synology, QNAP)

### Usage:
`check_nas.php [options]`

### Options:
```
-t  NAS-Type/ Vendor (Currently supported: synology, qnap)
-m  Mode (Currently supported: disks, volumes, memory, upgrade, cpu)
-s  SNMP-Community
-v  SNMP-Version (Currently supported: 1, 2c)
-h  Host
-w  Warnlimit
-c  Critlimit
```

### Currently supported type-mode-combos:
#### synology
```
synology disks
synology volumes
synology memory
synology upgrade
synology cpu
```

#### qnap
```
qnap disks
qnap volumes
qnap memory
qnap cpu
```

### Tested on:
- Synology DS414
- Synology DS115j
- QNAP TS-419P II
- QNAP TS131K

## check_sophos
Icinga check for Sophos UTM

### Usage
`check_sophos.php [options]`

### Options
```
-m  Mode (Currently available: cpu, memory, interfaces)
-v  SNMP-Version (Currently supported: 1, 2c)
-s  SNMP-Community
-h  Host
-w  Warnlimit
-c  Critlimit
```

### Tested on
- SG230

## check_switch
Icinga check for different switches (currently: HP)

### Usage
`check_switch.php [options]`

### Options
```
-m  Mode (Currently available: cpu, memory, interfaces, temp)
-v  SNMP-Version (Currently supported: 1, 2c)
-s  SNMP-Community
-h  Host
-w  Warnlimit
-c  Critlimit
```

### Currently supported type-mode-combos:
#### synology
```
hp temp
hp cpu
hp memory
hp interfaces
```

### Tested on
- HP V1910-48G
- HP V1910-24G


[check_nas]: #check_nas
[check_sophos]: #check_sophos
[check_switch]: #check_switch
