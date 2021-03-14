#!/usr/bin/php
<?php
  $options = getopt("m:s:h:w:c:v:");

  $oids = array(
    "interfaces" => array(
      "count" => "1.3.6.1.2.1.2.1.0",
      "name" => "1.3.6.1.2.1.2.2.1.2.",
      "mtu" => "1.3.6.1.2.1.2.2.1.4.",
      "linkspeed" => "1.3.6.1.2.1.2.2.1.5.",
      "mac" => "1.3.6.1.2.1.2.2.1.6.",
      "desiredstatus" => "1.3.6.1.2.1.2.2.1.7.",
      "realstatus" => "1.3.6.1.2.1.2.2.1.8.",
      "bytesin" => "1.3.6.1.2.1.2.2.1.10.",
      "unicastin" => "1.3.6.1.2.1.2.2.1.11.",
      "discardedin" => "1.3.6.1.2.1.2.2.1.13.",
      "inerrors" => "1.3.6.1.2.1.2.2.1.14.",
      "bytesout" => "1.3.6.1.2.1.2.2.1.16.",
      "unicastout" => "1.3.6.1.2.1.2.2.1.17.",
      "discardedout" => "1.3.6.1.2.1.2.2.1.19.",
      "outerrors" => "1.3.6.1.2.1.2.2.1.20."
    ),
    "memory" => array(
      "physical" => array(
        "total" => "1.3.6.1.4.1.2021.4.5.0",
        "used" => "1.3.6.1.4.1.2021.4.6.0",
        "avail" => "1.3.6.1.4.1.2021.4.11.0"
      ),
      "swap" => array(
        "total" => "1.3.6.1.4.1.2021.4.3.0",
        "avail" => "1.3.6.1.4.1.2021.4.4.0"
      )
    ),
    "cpu" => array(
      "load1" => "1.3.6.1.4.1.2021.10.1.3.1",
      "load5" => "1.3.6.1.4.1.2021.10.1.3.2",
      "load15" => "1.3.6.1.4.1.2021.10.1.3.3",
      "idle" => "1.3.6.1.4.1.2021.11.11.0"
    )
  );

  switch($options["m"]) {
    case "cpu":
      checkCpu();
      break;
    case "memory":
      checkMemory();
      break;
    case "interfaces":
      checkInterfaces();
      break;
    default:
      echo "usage";
      break;
  }

  function checkMemory() {
    // set up variables
    global $options;
    global $oids;
    $warn = $options["w"];
    $crit = $options["c"];

    // get physical memory data
    $totalphysical = sizeToBytes(snmp($oids["memory"]["physical"]["total"]));
    $usedphysical = sizeToBytes(snmp($oids["memory"]["physical"]["used"]));

    // get swap data
    $totalswap = sizeToBytes(snmp($oids["memory"]["swap"]["total"]));
    $availswap = sizeToBytes(snmp($oids["memory"]["swap"]["avail"]));
    $usedswap = $totalswap-$availswap;

    // calculate usage
    $usagephysical = ($usedphysical/$totalphysical)*100;
    $usageswap = ($usedswap/$totalswap)*100;

    // output data
    echo round($usagephysical, 0)."% memory used|memory=".$usagephysical.";".$warn.";".$crit.";0;100\n";
    echo round($usageswap, 0)."% swap used|swap=".$usageswap.";".$warn.";".$crit.";0;100\n";

    // send exit code
    if($usagephysical >= $crit | $usageswap >= $crit) {
      exitCode("CRITICAL");
    } else if($usagephysical >= $warn | $usageswap >= $warn) {
      exitCode("WARNING");
    } else {
      exitCode("OK");
    }
  }

  function checkCpu() {
    // set up variables
    global $options;
    global $oids;
    $warn = $options["w"];
    $crit = $options["c"];

    // get cpu data
    $load[1] = snmp($oids["cpu"]["load1"]);
    $load[5] = snmp($oids["cpu"]["load5"]);
    $load[15] = snmp($oids["cpu"]["load15"]);
    $idle = snmp($oids["cpu"]["idle"]);

    // calculate usage
    $usage = 100-$idle;

    // output data
    echo $usage."% cpu used|cpu=".$usage.";".$warn.";".$crit.";0;100\n";
    echo $load[1]." load 1min|load1=".$load[1]."\n";
    echo $load[5]." load 1min|load5=".$load[1]."\n";
    echo $load[15]." load 1min|load15=".$load[1]."\n";

    // send exit code
    if($usage >= $crit) {
      exitCode("CRITICAL");
    } else if($usage >= $warn) {
      exitCode("WARNING");
    } else {
      exitCode("OK");
    }
  }

  function checkInterfaces() {
    // set up variables
    global $options;
    global $oids;
    $warn = $options["w"];
    $crit = $options["c"];

    // prepare status counters
    $warncount = 0;
    $critcount = 0;
    $unknowncount = 0;

    // get interface counter
    $ifcnt = snmp($oids["interfaces"]["count"]);

    // interate through interfaces
    for($i=1; $i<=$ifcnt; $i++) {
      // get interface data
      $if["name"] = snmp($oids["interfaces"]["name"].$i);
      $if["desiredstatus"] = snmp($oids["interfaces"]["desiredstatus"].$i);
      $if["realstatus"] = snmp($oids["interfaces"]["realstatus"].$i);

      // output data
      echo "interface: ".$if["name"]."; desired status: ".$if["desiredstatus"]."; real status: ".$if["realstatus"]."\n";

      // compare desired and real status
      if($if["desiredstatus"] != $if["realstatus"]) {
        // check current status and rise respective counter
        switch($if["realstatus"]) {
          case "1":
          case "3":
          case "5":
            $warncount++;
            break;
          case "2":
          case "6":
            $critcount++;
            break;
          case "4":
            $unknowncount++;
            break;
        }
      } else {
        // everything as intended, nothing to do
      }
    }

    // check counters, set exit code
    if($critcount > 0) {
      exitCode("CRITICAL");
    } else if($warncount > 0) {
      exitCode("WARNING");
    } else if($unknowncount > 0) {
      exitCode("UNKNOWN");
    } else {
      exitCode("OK");
    }
  }

  function exitCode($status) {
    switch($status) {
      case "OK":
        exit(0);
        break;
      case "WARNING":
        exit(1);
        break;
      case "CRITICAL":
        exit(2);
        break;
      case "UNKNOWN":
      default:
        exit(3);
        break;
    }
  }

  function snmp($oid) {
    // get host, snmp community and snmp version
    global $options;
    $host = $options["h"];
    $community = $options["s"];
    $version = $options["v"];

    // prepare and execute snmp query
    // option "-O vq" returns only value (and unit)
    $cmd = "snmpget -v".$version." -c ".$community." -O vq ".$host." ".$oid;
    $result = shell_exec($cmd);

    // remove leading and trailing whitespaces etc. and return query result
    return trim($result);
  }

  function sizeToBytes($size) {
    $exp = explode(" ", $size);
    $value = $exp[0];
    $unit = $exp[1];
    switch($unit) {
      case "KB":
      case "kB":
        $factor = 1024;
        break;
      case "MB":
      case "mB":
        $factor = 1024*1024;
        break;
      case "GB":
      case "gB":
        $factor = 1024*1024*1024;
        break;
      case "TB":
      case "tB":
        $factor = 1024*1024*1024*1024;
        break;
      default:
        $factor = 1;
        break;
    }
    return $value*$factor;
  }
?>
