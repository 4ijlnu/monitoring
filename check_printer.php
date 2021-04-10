#!/usr/bin/php
<?php
  // oids from http://www.oidview.com/mibs/0/Printer-MIB.html
  // input types from https://tools.ietf.org/html/rfc1759#page-36
  // supply types from https://tools.ietf.org/html/rfc1759#page-60
  // supply classes from https://tools.ietf.org/html/rfc1759#page-60
  // supply capacities from https://tools.ietf.org/html/rfc1759#page-61


  $options = getopt(
    "t:m:s:h:w:c:v:",
    array(
      "ignoreSheetFeedManual"
    )
  );

  // make type/vendor all lowercase
  $options["t"] = strtolower($options["t"]);

  $oids = array(
    "kyocera" => array(
      "system" => array(
        "totalpages" => "1.3.6.1.2.1.43.10.2.1.4.1.1"
      ),
      "supply" => array(
        "index" => "1.3.6.1.2.1.43.11.1.1.2.1.",
        "desc" => "1.3.6.1.2.1.43.11.1.1.6.1.",
        "type" => "1.3.6.1.2.1.43.11.1.1.5.1.",
        "class" => "1.3.6.1.2.1.43.11.1.1.4.1.",
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

  // "Indicates whether this supply entity represents a supply
  // container that is consumed or a receptacle that is filled."
  $supplyclasses = array(
    1 => "other",
    3 => "supplyThatIsConsumed",
    4 => "receptacleThatIsFilled"
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

  $supplycapacities = array(
    "-1" => "other",
    "-2" => "unknown"
  );

  $supplylevels = array(
    "-1" => "other",
    "-2" => "unknown",
    "-3" => "ok"
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
      // more pages than $crit processed
      exitCode("CRITICAL");
    } else if($pages["total"] >= $warn) {
      // less pages than $crit but more than $warn processed
      exitCode("WARNING");
    } else if(($pages < $warn) && ($pages >= 0) && (is_numeric($pages))){
      // less pages than $warn but no negative amounts processed an $pages is a number
      exitCode("OK");
    } else {
      // negative amount of pages processed or $pages is not numeric
      exitCode("UNKNOWN");
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
    $okcount = 0;

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

      // sheetFeedManual is empty almost all of the time on almost every device
      // so --ignoreSheetFeedManual is an option to ignore that in the returned status

      // set counters
      if(($pctlevel <= $crit) && ($intype != "sheetFeedManual")) {
        // less than $crit percent of input medium left
        if(($intype == "sheetFeedManual") && ($options["ignoreSheetFeedManual"])) {
          $okcount++;
        } else {
          $critcount++;
        }
      } else if(($pctlevel <= $warn) && ($intype != "sheetFeedManual")) {
        // more than $crit but less than $warn percent of input medium left
        if(($intype == "sheetFeedManual") && ($options["ignoreSheetFeedManual"])) {
          $okcount++;
        } else {
          $critcount++;
        }
      } else if(($pctlevel > $warn) && ($pctlevel <= 100)) {
        // more than $warn percent of input medium left (but not more than 100%)
        $okcount++;
      }

      $i++;
    }

    // set exit code
    if($critcount > 0) {
      // at least one input critical
      exitCode("CRITICAL");
    } else if($warncount > 0) {
      // no input critical but at least one had a warning
      exitCode("WARNING");
    } else if($okcount == $i){
      // every input ok
      exitCode("OK");
    } else {
      // no criticals, no warnings but also not everything ok
      exitCode("UNKNOWN");
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
    $okcount = 0;

    // iterate through supplies
    $i = 1;
    while(snmp($oids[$type]["supply"]["index"].$i) != "No Such Instance currently exists at this OID") {
      // get supply data
      $supply["desc"] = snmp($oids[$type]["supply"]["desc"].$i);
      $supply["class"] = snmp($oids[$type]["supply"]["class"].$i);
      $supply["type"] = snmp($oids[$type]["supply"]["type"].$i);
      $supply["capacity"] = snmp($oids[$type]["supply"]["capacity"].$i);
      $supply["level"] = snmp($oids[$type]["supply"]["level"].$i);

      // intercept negative capacity
      if($supply["capacity"] < 0) {
        // -1=other, -2=unknown
        // -> do nothing - so $okcount wont be full and status will be unknown
      }

      // intercept negative level
      if($supply["level"] < 0) {
        // -1=other, -2=unknown, -3=ok
        switch($supply["level"]) {
          case "-3":
            echo $supply["desc"]." (".$supplytypes[$supply["type"]]."): OK\n";
            $okcount++;
            break;
          case "-1":
          case "-2":
          default:
            // do nothing - so $okcount wont be full and status will be unknown
            break;
        }
      }

      // if capacity and level both are not negative...
      if(($supply["capacity"] >= 0) && ($supply["level"] >= 0)) {
        // calculate the percentage of supply left
        $pctlevel = ($supply["level"]/$supply["capacity"])*100;

        // output info according to supply class
        switch($supply["class"]) {
          case 1:
            // other
            echo $supply["desc"]." (".$supplytypes[$supply["type"]]."): ".$pctlevel."%\n";
          case 3:
            // 3 = supplyThatIsConsumed
            echo $supply["desc"]." (".$supplytypes[$supply["type"]]."): ".$pctlevel."% left\n";
            break;
          case 4:
            // 4 = receptacleThatIsFilled
            // for receptacles the level is the remaining amount
            // -> so invert it to show the used amount
            $pctlevel = 100-$pctlevel;
            echo $supply["desc"]." (".$supplytypes[$supply["type"]]."): ".$pctlevel."% full\n";
        }
      }

      // set counters
      if($pctlevel <= $crit) {
        // less than $critical percent of this supply left
        $critcount++;
      } else if($pctlevel <= $warn) {
        // more than $critical but less than $warning percent of this supply left
        $warncount++;
      } else if(($pctlevel > $warn) && ($pctlevel <= 100)) {
        // more than $warning percent of this supply left (but not more than 100%)
        $okcount++;
      }

      $i++;
    }

    // set exit code
    if($critcount > 0) {
      // at least one supply was critical
      exitCode("CRITICAL");
    } else if($warncount > 0) {
      // no supply was critical but at least one had a warning
      exitCode("WARNING");
    } else if($okcount == $i){
      // every supply was ok
      exitCode("OK");
    } else {
      // no critical, no warning but also not everything ok
      exitCode("UNKNOWN");
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
