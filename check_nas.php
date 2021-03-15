#!/usr/bin/php
<?php
  $options = getopt("t:m:s:h:w:c:v:");

  $oids = array(
    "qnap" => array(
      "disks" => array(
        "count" => "1.3.6.1.4.1.24681.1.2.10.0",
        "index" => "1.3.6.1.4.1.24681.1.2.11.1.1.",
        "desc" => "1.3.6.1.4.1.24681.1.2.11.1.2.",
        "temp" => "1.3.6.1.4.1.24681.1.2.11.1.3.",
        "status" => "1.3.6.1.4.1.24681.1.2.11.1.4.",
        "model" => "1.3.6.1.4.1.24681.1.2.11.1.5.",
        "capacity" => "1.3.6.1.4.1.24681.1.2.11.1.6.",
        "smart" => "1.3.6.1.4.1.24681.1.2.11.1.7."
      ),
      "volumes" => array(
        "count" => "1.3.6.1.4.1.24681.1.2.16.0",
        "index" => "1.3.6.1.4.1.24681.1.2.17.1.1.",
        "desc" => "1.3.6.1.4.1.24681.1.2.17.1.2.",
        "filesystem" => "1.3.6.1.4.1.24681.1.2.17.1.3.",
        "size" => "1.3.6.1.4.1.24681.1.2.17.1.4.",
        "free" => "1.3.6.1.4.1.24681.1.2.17.1.5.",
        "status" => "1.3.6.1.4.1.24681.1.2.17.1.6."
      ),
      "memory" => array(
        "total" => "1.3.6.1.4.1.24681.1.2.2.0",
        "available" => "1.3.6.1.4.1.24681.1.2.3.0"
      ),
      "cpu" => array(
        "usage" => "1.3.6.1.4.1.24681.1.2.1.0"
      )
    ),
    "synology" => array(
      "disks" => array(
        "index" => "1.3.6.1.4.1.6574.2.1.1.1.",
        "desc" => "1.3.6.1.4.1.6574.2.1.1.4.",
        "temp" => "1.3.6.1.4.1.6574.2.1.1.6.",
        "status" => "1.3.6.1.4.1.6574.2.1.1.5.",
        "model" => "1.3.6.1.4.1.6574.2.1.1.3."
      ),
      "volumes" => array(
        "index" => "1.3.6.1.4.1.6574.3.1.1.1.",
        "desc" => "1.3.6.1.4.1.6574.3.1.1.2.",
        "size" => "1.3.6.1.4.1.6574.3.1.1.5.",
        "free" => "1.3.6.1.4.1.6574.3.1.1.4.",
        "status" => "1.3.6.1.4.1.6574.3.1.1.3."
      ),
      "memory" => array(
        "total" => "1.3.6.1.4.1.2021.4.5.0",
        "available" => "1.3.6.1.4.1.2021.4.6.0"
      ),
      "upgrade" => array(
        "status" => "1.3.6.1.4.1.6574.1.5.4.0"
      ),
      "cpu" => array(
        "idle" => "1.3.6.1.4.1.2021.11.11.0"
      )
    )
  );

  switch($options["m"]) {
    case "disks":
      checkDisks();
      break;
    case "volumes":
      checkVolumes();
      break;
    case "memory":
      checkMemory();
      break;
    case "cpu":
      checkCpu();
      break;
    case "upgrade":
      checkUpgrade();
      break;
    default:
      echo "usage: check_nas.php [options]\n";
      echo "\n";
      echo "options:\n";
      echo "-t NAS-Type/ Vendor (Types: synology, qnap)\n";
      echo "-m Mode (Modes: disks, volumes, memory, upgrade, cpu)\n";
      echo "-s SNMP Community\n";
      echo "-v SNMP Version (Currently supported: 1, 2c)\n";
      echo "-h Host\n";
      echo "-w Warnlimit\n";
      echo "-c Critlimit\n";
      echo "\n";
      echo "Currently supported type-mode-combos:\n";
      echo "synology disks\n";
      echo "synology volumes\n";
      echo "synology memory\n";
      echo "synology upgrade\n";
      echo "synology cpu\n";
      echo "qnap disks\n";
      echo "qnap volumes\n";
      echo "qnap memory\n";
      echo "qnap cpu\n";
      break;
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

    // prepare and execute snmp query and
    // remove leading and trailing whitespaces, quotation marks, line feeds etc.
    // option "-O vq" returns only value (and unit)
    $cmd = "snmpget -v".$version." -c ".$community." -O vq ".$host." ".$oid;
    $result = trim(shell_exec($cmd), " \n\r\t\v\0\"");

    // and return query result
    return $result;
  }

  function sizeToBytes($size) {

    if(substr($size, -1) == "B" || substr($size, -1) == "b") {
      $exp = explode(" ", $size);
      $value = $exp[0];
      $unit = $exp[1];

      switch($unit) {
        case "KB":
          $factor = 1024;
          break;
        case "MB":
          $factor = 1024*1024;
          break;
        case "GB":
          $factor = 1024*1024*1024;
          break;
        case "TB":
          $factor = 1024*1024*1024*1024;
          break;
        default:
          $factor = 1;
          break;
      }
      return $value*$factor;
    } else {
      return $size;
    }
  }

  function checkCpu() {
    // set global variables
    global $options;
    global $oids;

    // set up parameters
    $warn = $options["w"];
    $crit = $options["c"];
    $type = $options["t"];

    if(isset($oids[$options["t"]]["cpu"])) {
      // get cpu usage
      if(isset($oids[$options["t"]]["cpu"]["usage"])) {
        $res = snmp($oids[$options["t"]]["cpu"]["usage"]);
        $usage = substr($res, 0, -2);
      } else if(isset($oids[$options["t"]]["cpu"]["idle"])) {
        $res = snmp($oids[$options["t"]]["cpu"]["idle"]);
        $usage = 100-$res;
      }

      // output information
      echo round($usage, 0)."% ausgelastet|cpu=".$usage.";".$warn.";".$crit.";0;100\n";

      // set exit code
      if($usage >= $crit) {
        exitCode("CRITICAL");
      } else if($usage >= $warn) {
        exitCode("WARNING");
      } else {
        exitCode("OK");
      }
    } else {
      echo "CPU check not implemented for ".$options["t"]."\n";
      exitCode("UNKNOWN");
    }
  }

  function checkMemory() {
    // set global variables
    global $options;
    global $oids;

    // set up parameters
    $warn = $options["w"];
    $crit = $options["c"];
    $type = $options["t"];

    if(isset($oids[$options["t"]]["memory"])) {
      // get total and available memory
      $totalreal = substr(snmp($oids[$options["t"]]["memory"]["total"]), 0, -3);
      $availreal = substr(snmp($oids[$options["t"]]["memory"]["available"]), 0, -3);

      // calculate memory usage in percentage
      $percentagefree = ($availreal/$totalreal)*100;
      $percentageused = 100-$percentagefree;

      // output information
      echo round($percentageused,0)."% used|memory=".$percentageused.";".$warn.";".$crit.";0;100\n";

      // set exit code
      if($percentageused >= $crit) {
        exitCode("CRITICAL");
      } else if($percentageused >= $warn) {
        exitCode("WARNING");
      } else {
        exitCode("OK");
      }
    } else {
      echo "Memory check not implemented for ".$options["t"]."\n";
      exitCode("UNKNOWN");
    }
  }

  function checkDisks() {
    // set global variables
    global $options;
    global $oids;

    // set up parameters
    $warn = $options["w"];
    $crit = $options["c"];
    $type = $options["t"];

    if(isset($oids[$type]["disks"])) {
      // prepare crit and warn counters
      $critcount = 0;
      $warncount = 0;

      // get number of disks
      //$diskcount = snmp($oids[$type]["disks"]["count"]);

      switch($type) {
        case "synology":
          $i = 0;
          break;
        case "qnap":
          $i = 1;
          break;
      }

      // iterate through disks
      while(snmp($oids[$type]["disks"]["index"].$i) != "No Such Instance currently exists at this OID") {
        $disk["index"] = snmp($oids[$type]["disks"]["index"].$i);
        $disk["desc"] = snmp($oids[$type]["disks"]["desc"].$i);
        $disk["temp"] = snmp($oids[$type]["disks"]["temp"].$i);
        $disk["status"] = snmp($oids[$type]["disks"]["status"].$i);
        $disk["model"] = snmp($oids[$type]["disks"]["model"].$i);

        if(isset($oids[$type]["disks"]["capacity"])) {
          $disk["capacity"] = snmp($oids[$type]["disks"]["capacity"].$i);
          $cap = "Capacity: ".$disk["capacity"]."; ";
        } else {
          $cap = "";
        }

        if(isset($oids[$type]["disks"]["smart"])) {
          $disk["smart"] = snmp($oids[$type]["disks"]["smart"].$i);
          $smart = "SMART: ".$disk["smart"]."; ";
        } else {
          $smart = "";
        }

        // get celsius temperature as number
        $tmp = explode("/", $disk["temp"]);
        $tmp = $tmp[0];
        $temp = substr($tmp, 0, -2);

        // check temperature
        if($temp >= $crit) {
          $critcount++;
          echo "CRITICAL: Disk ".$i." temperature ".$temp." C\n";
        } else if($temp >= $warn) {
          $warncount++;
          echo "WARNING: Disk ".$i." temperature ".$temp." C\n";
        }

        if($type == "qnap") {
          // check status
          switch($disk["status"]) {
            case "0":
              $status = "ready";
              break;
            case "-5":
              $critcount++;
              $status = "noDisk";
              echo "CRITICAL: Disk ".$i." ".$status."\n";
              break;
            case "-6":
              $status = "invalid";
              break;
            case "-9":
              $warncount++;
              $status = "rwError";
              echo "WARNING: Disk ".$i." ".$status."\n";
              break;
            case "-4":
              $warncount++;
              $status = "unknown";
              echo "WARNING: Disk ".$i." ".$status."\n";
              break;
          }
        } else if($type == "synology") {
          switch($disk["status"]) {
            case "1":
              $status = "The hard disk functions normally";
              break;
            case "2":
              $status = "The hard disk has system partition but no data";
              echo "CRITICAL: Disk ".$id." ".$status."\n";
              break;
            case "3":
              $status = "The hard disk does not have system in system partition";
              echo "CRITICAL: Disk ".$id." ".$status."\n";
              break;
            case "4":
              $status = "The system partitions on the hard disks are damaged";
              echo "CRITICAL: Disk ".$id." ".$status."\n";
              break;
            case "5":
              $critcount++;
              $status = "The hard disk has damaged";
              echo "CRITICAL: Disk ".$id." ".$status."\n";
              break;
          }
        }

        // output information
        echo "Disk ".$i."; ".$disk["desc"]."; Temp: ".$disk["temp"]."; Status: ".$disk["status"]." (".$status."); Model: ".$disk["model"]."; ".$cap.$smart."\n";
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
    } else {
      echo "Disk check not implemented for ".$options["t"]."\n";
      exitCode("UNKNOWN");
    }
  }

  function checkVolumes() {
    // set global variables
    global $options;
    global $oids;

    // set up parameters
    $warn = $options["w"];
    $crit = $options["c"];
    $type = $options["t"];

    if(isset($oids[$type]["volumes"])) {
      // prepare crit and warn counters
      $critstate = 0;
      $warnstate = 0;

      if(isset($oids[$type]["volumes"]["index"])) {
        switch($type) {
          case "qnap":
            $i = 1;
            break;
          case "synology":
            $i = 0;
            break;
        }
        while(snmp($oids[$type]["volumes"]["index"].$i) != "No Such Instance currently exists at this OID" && snmp($oids[$type]["volumes"]["desc"].$i) != null ) {
          $vol["index"] = snmp($oids[$type]["volumes"]["index"].$i);
          $vol["desc"] = snmp($oids[$type]["volumes"]["desc"].$i);
          if(isset($oids[$type]["volumes"]["filesystem"])) {
            $vol["fs"] = snmp($oids[$type]["volumes"]["filesystem"].$i);
            $fs = "Filesystem: ".$vol["fs"]."; ";
          } else {
            $fs = "";
          }
          $vol["size"] = sizeToBytes(snmp($oids[$type]["volumes"]["size"].$i));
          $vol["free"] = sizeToBytes(snmp($oids[$type]["volumes"]["free"].$i));
          $vol["status"] = snmp($oids[$type]["volumes"]["status"].$i);

          // calculate free and used space in percentages
          $percentagefree = ($vol["free"]/$vol["size"])*100;
          $percentageused = 100-$percentagefree;

          // check space usage
          if($percentageused >= $crit) {
            $critstate++;
          } else if($percentageused >= $warn) {
            $warnstate++;
          }

          echo "Volume ".$i."; ".$vol["desc"]."; ".$fs."Status: ".$vol["status"]."; Storage: ".round($percentageused,0)."% used (".round(($vol["free"]/1024/1024/1024), 0)."GB free)|storage vol".$i."=".$percentageused.";".$warn.";".$crit.";0;100\n";
          $i++;
        }
      }

      // set exit code
      if($critcount > 0) {
        exitCode("CRITICAL");
      } else if($warncount > 0) {
        exitCode("WARNING");
      } else {
        exitCode("OK");
      }

    } else {
      echo "Volume check not implemented for ".$options["t"]."\n";
      exitCode("UNKNOWN");
    }
  }

  function checkUpgrade() {
    // set global variables
    global $options;
    global $oids;

    // set up parameters
    $warn = $options["w"];
    $crit = $options["c"];
    $type = $options["t"];

    if(isset($oids[$type]["upgrade"]["status"])) {
      $status = snmp($oids[$type]["upgrade"]["status"]);

      if($type == "synology") {
        // status codes: 1=update available, 2=up to date, 3=checking for updates, 4=failed to connect, 5=others, upgrading, downloading
        switch($status) {
          case "1":
            echo "An update is available\n";
            exitCode("WARNING");
            break;
          case "2":
            echo "System is up to date\n";
            exitCode("OK");
            break;
          case "3":
            echo "Looking for updates\n";
            exitCode("OK");
            break;
          case "4":
            echo "Server not available\n";
            exitCode("CRITICAL");
            break;
          case "5":
            echo "Either an update is beeing installed or downloaded or the status is unknown\n";
            exitCode("UNKNOWN");
            break;
          default:
            echo "UNKNOWN\n".$status."\n";
            exitCode("UNKNOWN");
            break;
        }
      } else if($type == "qnap") {

      }
    } else {
      echo "Upgrade check not implemented for ".$options["t"]."\n";
      exitCode("UNKNOWN");
    }
  }
?>
