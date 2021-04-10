# Icinga2 Monitoring Plugins

## Table of Contents
1. [check_nas][check_nas]
2. [check_sophos][check_sophos]
3. [check_switch][check_switch]
4. [check_printer][check_printer]
5. [check_ups][check_ups]

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

--mintemp   Minimum operating temperature
--maxtemp   Maximum operating temperature
```

Note: warnlimit and critlimit are given as percentage of the operating temperature range. Example: mintemp=5°C, maxtemp=15°C, critlimit=95 -> CRITICAL above 14.5°C and below 5.5°C.

### Currently supported type-mode-combos:
#### hp
```
hp temp
hp cpu
hp memory
hp interfaces
```

### Tested on
- HP V1910-48G
- HP V1910-24G
- HP 1810G-24 (only interfaces)

## check_printer
Icinga check for different printers (currently: Kyocera)

### Usage
`check_printer.php [options]`

### Options
```
-t  Type (Currently supported: kyocera)
-m  Mode (Currently available: pages, supplies, inputs)
-v  SNMP-Version (Currently supported: 1, 2c)
-s  SNMP-Community
-h  Host
-w  Warnlimit
-c  Critlimit

--ignoreSheetFeedManual   Manual sheet feeds are empty on a lot of devices on a regular base; set this true to ignore empty or low-on-input sheetFeedManual's in the inputs-check and treat them as ok instead
```

### Currently supported type-mode-combos
#### kyocera
```
kyocera pages
kyocera supplies
kyocera inputs
```

### Tested on
- Kyocera FS-4100DN

## check_ups
Icinga check for uninterruptible power supplies (currently: Schneider Electric APC)

### Usage
`check_ups.php [options]`

### Options
```
-t  Type (Currently supported: apc)
-m  Mode (Currently available: battery, input, output)
-v  SNMP-Version (Currently supported: 1, 2c)
-s  SNMP-Community
-h  Host
-w  Warnlimit
-c  Critlimit

--minvoltage    Minimum safe voltage on input line (only for mode "input")
--maxvoltage    Maximum safe voltage on input line (only for mode "input")
```

### Currently supported type-mode-combos
#### apc
```
apc battery
apc input
apc output
```

### Tested on
- Schneider Electric APC Smart-UPS 3000
- Schneider Electric APC Smart-UPS 750


[check_nas]: #check_nas
[check_sophos]: #check_sophos
[check_switch]: #check_switch
[check_printer]: #check_printer
[check_ups]: #check_ups
