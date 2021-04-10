#!/usr/bin/php
<?php
  $options = getopt("t:m:s:h:w:c:v:");

  // make type/vendor all lowercase
  $options["t"] = strtolower($options["t"]);

  $oids = array(
    "apc" => array(
      "battery" => array(
        "status" => "1.3.6.1.4.1.318.1.1.1.2.1.1.0",
        "timeonbattery" => "1.3.6.1.4.1.318.1.1.1.2.1.2.0",
        "capacity" => "1.3.6.1.4.1.318.1.1.1.2.2.1.0",
        "temperature" => "1.3.6.1.4.1.318.1.1.1.2.2.2.0",
        "runtimeremaining" => "1.3.6.1.4.1.318.1.1.1.2.2.3.0",
        "replaceindicator" => "1.3.6.1.4.1.318.1.1.1.2.2.4.0"
      ),
      "input" => array(
        "lineVoltage" => "1.3.6.1.4.1.318.1.1.1.3.2.1.0",
        "lineFailCause" => "1.3.6.1.4.1.318.1.1.1.3.2.5.0"
      ),
      "output" => array(
        "status" => "1.3.6.1.4.1.318.1.1.1.4.1.1.0"
      )
    )
  );

  $batteryStatuses = array(
    1 => array(
      "desc" => "unknown",
      "exit" => "UNKNOWN"
    ),
    2 => array(
      "desc" => "batteryNormal",
      "exit" => "OK"
    ),
    3 => array(
      "desc" => "batteryLow",
      "exit" => "WARNING"
    ),
    4 => array(
      "desc" => "batteryInFaultCondition",
      "exit" => "CRITICAL"
    )
  );

  $batteryReplacing = array(
    1 => array(
      "desc" => "noBatteryNeedsReplacing",
      "exit" => "OK"
    ),
    2 => array(
      "desc" => "batteryNeedsReplacing",
      "exit" => "CRITICAL"
    )
  );

  $inputLineFailCauses = array(
    1 => "noTransfer",
    2 => "highLineVoltage",
    3 => "brownout",
    4 => "blackout",
    5 => "smallMomentarySag",
    6 => "deepMomentarySag",
    7 => "smallMomentarySpike",
    8 => "largeMomentarySpike",
    9 => "selfTest",
    10 => "rateOfVoltageChange"
  );

  $outputStatuses = array(
    1 => array(
      "desc" => "unknown",
      "exit" => "UNKNOWN"
    ),
    2 => array(
      "desc" => "onLine",
      "exit" => "OK"
    ),
    3 => array(
      "desc" => "onBattery",
      "exit" => "CRITICAL"
    ),
    4 => array(
      "desc" => "onSmartBoost",
      "exit" => "OK"
    ),
    5 => array(
      "desc" => "timedSleeping",
      "exit" => "OK"
    ),
    6 => array(
      "desc" => "softwareBypass",
      "exit" => "WARNING"
    ),
    7 => array(
      "desc" => "off",
      "exit" => "CRITICAL"
    ),
    8 => array(
      "desc" => "rebooting",
      "exit" => "CRITICAL"
    ),
    9 => array(
      "desc" => "switchedBypass",
      "exit" => "WARNING"
    ),
    10 => array(
      "desc" => "hardwareFailureBypass",
      "exit" => "CRITICAL"
    ),
    11 => array(
      "desc" => "sleepingUntilPowerReturn",
      "exit" => "CRITICAL"
    ),
    12 => array(
      "desc" => "onSmartTrim",
      "exit" => "WARNING"
    ),
    13 => array(
      "desc" => "ecoMode",
      "exit" => "OK"
    ),
    14 => array(
      "desc" => "hotStandby",
      "exit" => "OK"
    ),
    15 => array(
      "desc" => "onBatteryTest",
      "exit" => "WARNING"
    ),
    16 => array(
      "desc" => "emergencyStaticBypass",
      "exit" => "CRITICAL"
    ),
    17 => array(
      "desc" => "staticBypassStandby",
      "exit" => "WARNING"
    ),
    18 => array(
      "desc" => "powerSavingMode",
      "exit" => "OK"
    ),
    19 => array(
      "desc" => "spotMode",
      "exit" => "WARNING"
    ),
    20 => array(
      "desc" => "eConversion",
      "exit" => "OK"
    ),
    21 => array(
      "desc" => "chargerSpotmode",
      "exit" => "WARNING"
    ),
    22 => array(
      "desc" => "inverterSpotmode",
      "exit" => "WARNING"
    ),
    23 => array(
      "desc" => "activeLoad",
      "exit" => "OK"
    ),
    24 => array(
      "desc" => "batteryDischargeSpotmode",
      "exit" => "WARNING"
    ),
    25 => array(
      "desc" => "inverterStandby",
      "exit" => "OK"
    ),
    26 => array(
      "desc" => "chargerOnly",
      "exit" => "WARNING"
    )
  );

  switch($options["m"]) {
    case "battery":
      checkBattery();
      break;
    case "input":
      checkInput();
      break;
    case "output":
      checkOutput();
      break;
    default:
      echo "See README.md for usage information\n";
      break;
  }

  function checkBattery() {
    //set up variables
    global $options;
    global $oids;
    global $batteryStatuses;
    global $batteryReplacing;
    $warn = $options["w"];
    $crit = $options["c"];
    $type = $options["t"];

    // get battery data
    $battery["status"] = $batteryStatuses[snmp($oids[$type]["battery"]["status"])];
    $battery["timeonbattery"] = snmp($oids[$type]["battery"]["timeonbattery"]);
    $battery["capacity"] = snmp($oids[$type]["battery"]["capacity"]);
    $battery["temperature"] = snmp($oids[$type]["battery"]["temperature"]);
    $battery["runtimeremaining"] = snmp($oids[$type]["battery"]["runtimeremaining"]);
    $battery["replaceindicator"] = $batteryReplacing[snmp($oids[$type]["battery"]["replaceindicator"])];

    // output info
    echo "Status: ".$battery["status"]["desc"]."\n";
    echo "Time on battery: ".$battery["timeonbattery"]."\n";
    echo "Capacity: ".$battery["capacity"]."%\n";
    echo "Temperature: ".$battery["temperature"]."°C\n";
    echo "Runtime remaining: ".$battery["runtimeremaining"]."\n";
    echo "Replace indicator: ".$battery["replaceindicator"]["desc"]."\n";

    // set exit code
    if(($battery["status"]["exit"] == "CRITICAL") || ($battery["capacity"] <= (100-$crit)) || ($battery["replaceindicator"]["exit"] == "CRITICAL")) {
      exitCode("CRITICAL");
    } else if(($battery["status"]["exit"] == "WARNING") || ($battery["capacity"] <= (100-$warn)) || ($battery["replaceindicator"]["exit"] == "WARNING")) {
      exitCode("WARNING");
    } else if(($battery["status"]["exit"] == "OK") || ($battery["capacity"] > (100-$warn)) || ($battery["replaceindicator"]["exit"] == "OK")) {
      exitCode("OK");
    } else {
      exitCode("UNKNOWN");
    }
  }

  function checkInput() {

  }

  function checkOutput() {

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
