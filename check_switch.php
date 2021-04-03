#!/usr/bin/php
<?php
  $options = getopt(
    "t:m:s:h:w:c:v:",
    array(
      "mintemp:",
      "maxtemp:",
    )
  );

  // make type/vendor all lowercase
  $options["t"] = strtolower($options["t"]);

  $oids = array(
    "hp" => array(
      "system" => array(
        "uptime" => "1.3.6.1.2.1.1.3.0",
        "temp" => "1.3.6.1.4.1.25506.2.6.1.1.1.1.12.8"
      ),
      "cpu" => array(
        "usage" => "1.3.6.1.4.1.25506.2.6.1.1.1.1.6.8"
      ),
      "memory" => array(
        "usage" => "1.3.6.1.4.1.25506.2.6.1.1.1.1.8.8"
      ),
      "interfaces" => array(
        "count" => "1.3.6.1.2.1.2.1.0",
        "desc" => "1.3.6.1.2.1.2.2.1.2.",
        "linkspeed" => "1.3.6.1.2.1.2.2.1.5.",
        "desiredstatus" => "1.3.6.1.2.1.2.2.1.7.",
        "realstatus" => "1.3.6.1.2.1.2.2.1.8."
      )
    )
  );

  switch($options["m"]) {
    case "interfaces":
      checkInterfaces();
      break;
    case "cpu":
      checkCpu();
      break;
    case "temp":
      checkTemp();
      break;
    case "memory":
      checkMemory();
      break;
  }

  function checkMemory() {
    // set up variables
    global $options;
    global $oids;
    $warn = $options["w"];
    $crit = $options["c"];
    $type = $options["t"];

    if(isset($oids[$type])) {
      // get usage data
      $usage = snmp($oids[$type]["memory"]["usage"]);

      // output info
      echo $usage."% used|memory=".$usage.";".$warn.";".$crit.";0;100\n";

      // set exit code
      if($usage >= $crit) {
        exitCode("CRITICAL");
      } else if($usage >= $warn) {
        exitCode("WARNING");
      } else {
        exitCode("OK");
      }

    } else {
      echo "Memory check not implemented for ".$type."\n";
      exitCode("UNKNOWN");
    }
  }

  function checkTemp() {
    // set up variables
    global $options;
    global $oids;
    $warn = 0.01*$options["w"];
    $crit = 0.01*$options["c"];
    $type = $options["t"];
    $min = $options["mintemp"];
    $max = $options["maxtemp"];

    if(isset($oids[$type])) {
      //get temp data
      $temp = snmp($oids[$type]["system"]["temp"]);

      // check temp data
      if(is_numeric($temp)) {
        // set thresholds
        $range = $max - $min;
        $crithigh = $min + ($range * $crit);
        $critlow = $max - ($range * $crit);
        $warnhigh = $min + ($range * $warn);
        $warnlow = $max - ($range * $warn);

        // output info
        echo $temp." C system temperature|temp=".$temp.";".$warnhigh.";".$crithigh.";".$min.";".$max."\n";

        // set exit code
        if($temp >= $crithigh || $temp <= $critlow) {
          exitCode("CRITICAL");
        } else if($temp >= $warnhigh || $temp <= $warnlow) {
          exitCode("WARNING");
        } else if($temp > $warnlow && $temp < $warnhigh){
          exitCode("OK");
        } else {
          exitCode("UNKNOWN");
        }
        
      } else {
        echo "Invalid temperatur value: ".$temp."\n";
        exitCode("UNKNOWN");
      }
    } else {
      echo "Temperature check not implemented for ".$type."\n";
      exitCode("UNKNOWN");
    }
  }

  function checkCpu() {
    // set up variables
    global $options;
    global $oids;
    $warn = $options["w"];
    $crit = $options["c"];
    $type = $options["t"];

    if(isset($oids[$type])) {
      // get cpu data
      $usage = snmp($oids[$type]["cpu"]["usage"]);

      // output info
      echo $usage."% used|cpu=".$usage.";".$warn.";".$crit.";0;100\n";

      // set exit code
      if($usage >= $crit) {
        exitCode("CRITICAL");
      } else if($usage >= $warn) {
        exitCode("WARNING");
      } else {
        exitCode("OK");
      }

    } else {
      echo "CPU check not implemented for ".$type."\n";
      exitCode("UNKNOWN");
    }
  }

  function checkInterfaces() {
    // set up variables
    global $options;
    global $oids;
    $warn = $options["w"];
    $crit = $options["c"];
    $type = $options["t"];

    if(isset($oids[$type])) {

      // prepare status counters
      $warncount = 0;
      $critcount = 0;
      $unknowncount = 0;

      // get interface counter
      $ifcnt = snmp($oids[$type]["interfaces"]["count"]);

      // interate through interfaces
      for($i=1; $i<=$ifcnt; $i++) {
        // get interface data
        $if["name"] = snmp($oids[$type]["interfaces"]["desc"].$i);
        $if["desiredstatus"] = snmp($oids[$type]["interfaces"]["desiredstatus"].$i);
        $if["realstatus"] = snmp($oids[$type]["interfaces"]["realstatus"].$i);
        $if["speed"] = snmp($oids[$type]["interfaces"]["linkspeed"].$i);

        // output data
        echo "interface: ".$if["name"]."; desired status: ".$if["desiredstatus"]."; real status: ".$if["realstatus"]."; speed: ".($if["speed"]/1000/1000)." Mbit/s\n";

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
    } else {
      echo "Interface check not implemented for ".$type."\n";
      exitCode("UNKNOWN");
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
?>
