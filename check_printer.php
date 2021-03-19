#!/usr/bin/php
<?php
  // oids from http://www.oidview.com/mibs/0/Printer-MIB.html
  // input types from https://tools.ietf.org/html/rfc1759#page-35


  $options = getopt("t:m:s:h:w:c:v:");

  $oids = array(
    "kyocera" => array(
      "system" => array(
        "totalpages" => "1.3.6.1.2.1.43.10.2.1.4.1.1"
      ),
      "supply" => array(
        "index" => "1.3.6.1.2.1.43.11.1.1.2.1.",
        "desc" => "1.3.6.1.2.1.43.11.1.1.6.1.",
        "type" => "1.3.6.1.2.1.43.11.1.1.5.1.",
        "capacity" => "1.3.6.1.2.1.43.11.1.1.8.1.",
        "level" => "1.3.6.1.2.1.43.11.1.1.9.1."
      ),
      "input" => array(
        "type" => "1.3.6.1.2.1.43.8.2.1.2.1.",
        "capacity" => "1.3.6.1.2.1.43.8.2.1.9.1.",
        "level" => "1.3.6.1.2.1.43.8.2.1.10.1.",
        "status" => "1.3.6.1.2.1.43.8.2.1.11.1.",
      )
    )
  );

  $inputstatuses = array(
    0 => "default - available and idle",
    6 => "paper motion - available and busy",
    9 => "paper out in this tray"
  );

  $inputtypes = array(
    1 => "other",
    2 => "unknown",
    3 => "sheetFeedAutoRemovableTray",
    4 => "sheetFeedAutoNonRemovableTray",
    5 => "sheetFeedManual",
    6 => "continuousRoll",
    7 => "continuousFanFold",
    8 => "sheetFeedPull"
  );

  $supplytypes = array(
    1 => "other",
    2 => "unknown",
    3 => "toner",
    4 => "wasteToner",
    5 => "ink",
    6 => "inkCartridge",
    7 => "inkRibbon",
    8 => "wasteInk",
    9 => "opc",
    10 => "developer",
    11 => "fuserOil",
    12 => "solidWax",
    13 => "ribbonWax",
    14 => "wasteWax",
    15 => "fuser",
    16 => "coronaWire",
    17 => "fuserOilWick",
    18 => "cleanerUnit",
    19 => "fuserCleaningPad",
    20 => "transferUnit",
    21 => "tonerCartridge",
    22 => "fuserOiler",
    23 => "water",
    24 => "wasteWater",
    25 => "glueWaterAdditive",
    26 => "wastePaper",
    27 => "bindingSupply",
    28 => "bandingSupply",
    29 => "stitchingWire",
    30 => "shrinkWrap",
    31 => "paperWrap",
    32 => "staples",
    33 => "inserts",
    34 => "covers"
  );

  switch($options["m"]) {
    case "supplies":
      checkSupplies();
      break;
    case "inputs":
      checkInputs();
      break;
    case "pages":
      checkPageCounter();
      break;
    default:
      echo "usage: check_printer.php [options].\n";
      echo "options:\n";
      echo "-t  Type\n";
      echo "-m  Mode\n";
      echo "-s  SNMP Community\n";
      echo "-h  Host\n";
      echo "-w  Warnlimit\n";
      echo "-c  Critlimit\n";
      echo "-v  SNMP Version\n";
      break;
  }

  function checkPageCounter() {
    // set up variables
    global $options;
    global $oids;
    $warn = $options["w"];
    $crit = $options["c"];
    $type = $options["t"];

    // get counter data
    $pages["total"] = snmp($oids[$type]["system"]["totalpages"]);
    echo "Lifetime total printed pages: ".$pages["total"]."\n";

    // set exit code
    if($pages["total"] >= $crit) {
      exitCode("CRITICAL");
    } else if($pages["total"] >= $warn) {
      exitCode("WARNING");
    } else if($pages["total"] == "No Such Instance currently exists at this OID") {
      echo "pups";
      exitCode("UNKNOWN");
    } else {
      exitCode("OK");
    }
  }

  function checkInputs() {
    // set up variables
    global $options;
    global $oids;
    global $inputstatuses;
    global $inputtypes;
    $warn = $options["w"];
    $crit = $options["c"];
    $type = $options["t"];
    $critcount = 0;
    $warncount = 0;

    // iterate through inputs
    $i = 1;
    while(snmp($oids[$type]["input"]["type"].$i) != "No Such Instance currently exists at this OID") {
      // get input data
      $input["type"] = snmp($oids[$type]["input"]["type"].$i);
      $input["capacity"] = snmp($oids[$type]["input"]["capacity"].$i);
      $input["level"] = snmp($oids[$type]["input"]["level"].$i);
      $input["status"] = snmp($oids[$type]["input"]["status"].$i);

      // set short variables
      $intype = $inputtypes[$input["type"]];

      // calculate input usage
      $pctlevel = ($input["level"]/$input["capacity"])*100;

      if(isset($inputstatuses[$input["status"]])) {
        $statustext = $inputstatuses[$input["status"]];
      } else {
        $statustext = $input["status"];
      }

      // output info
      echo $intype.": ".$pctlevel."% full (".$input["level"]." of ".$input["capacity"]."); status: ".$statustext."\n";

      // set counters
      if(($pctlevel <= $crit) && ($intype != "sheetFeedManual")) {
        $critcount++;
      } else if(($pctlevel <= $warn) && ($intype != "sheetFeedManual")) {
        $warncount++;
      }

      $i++;
    }

    // set exit code
    if($critcount > 0) {
      exitCode("CRITICAL");
    } else if($warncount > 0) {
      exitCode("WARNING");
    } else {
      exitCode("OK");
    }
  }

  function checkSupplies() {
    // set up variables
    global $options;
    global $oids;
    global $supplytypes;
    $warn = $options["w"];
    $crit = $options["c"];
    $type = $options["t"];
    $critcount = 0;
    $warncount = 0;

    // iterate through supplies
    $i = 1;
    while(snmp($oids[$type]["supply"]["index"].$i) != "No Such Instance currently exists at this OID") {
      // get supply data
      $supply["desc"] = snmp($oids[$type]["supply"]["desc"].$i);
      $supply["type"] = snmp($oids[$type]["supply"]["type"].$i);
      $supply["capacity"] = snmp($oids[$type]["supply"]["capacity"].$i);
      $supply["level"] = snmp($oids[$type]["supply"]["level"].$i);

      // calculate supply usage
      $pctlevel = ($supply["level"]/$supply["capacity"])*100;

      // output info
      echo $supply["desc"]." (".$supplytypes[$supply["type"]]."): ".$supply["level"]." of ".$supply["capacity"]." (".$pctlevel."%)\n";

      // set counters
      if($pctlevel <= $crit) {
        $critcount++;
      } else if($pctlevel <= $warn) {
        $warncount++;
      }

      $i++;
    }

    // set exit code
    if($critcount > 0) {
      exitCode("CRITICAL");
    } else if($warncount > 0) {
      exitCode("WARNING");
    } else {
      exitCode("OK");
    }
  }

  function snmp($oid) {
    // get host, snmp community and snmp version
    global $options;
    $host = $options["h"];
    $community = $options["s"];
    $version = $options["v"];

    // prepare and execute snmp query and
    // remove leading and trailing whitespaces, quotation marks, line feeds etc.
    // option "-O vq" returns only value (and unit)
    $cmd = "snmpget -v".$version." -c ".$community." -O vq ".$host." ".$oid;
    $result = trim(shell_exec($cmd), " \n\r\t\v\0\"");

    // and return query result
    return $result;
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
