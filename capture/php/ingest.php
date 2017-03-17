<?php
$dest_path = "/data/live" . $_SERVER['PATH_INFO'];
$tmp_path = "/data/live/tmp" . $_SERVER['PATH_INFO'];
$archive_path = "/data/archive" . $_SERVER['PATH_INFO'];

$ENABLE_DEBUG = false;

openlog("hlsingest", LOG_PID | LOG_PERROR, LOG_LOCAL0);

if ($_SERVER['REQUEST_METHOD'] === "POST" || $_SERVER['REQUEST_METHOD'] === 'PUT') {
  $dir = dirname($dest_path);
  if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
  }

  $tmpdir = dirname($tmp_path);
  if (!file_exists($tmpdir)) {
    mkdir($tmpdir, 0777, true);
  }

  if (!file_exists(dirname($archive_path))) {
    mkdir(dirname($archive_path), 0777, true);
  }

  if (($stream = fopen('php://input', "r")) !== FALSE) {
    // Write to a temp location to avoid origin to read incomplete files
    $dest_fp = fopen($tmp_path, "w");
    if ($ENABLE_ARCHIVE) {
      $archive_fp = fopen($archive_path, "w");
      if ($ENABLE_DEBUG) {
        syslog(LOG_DEBUG, "Archiving $archive_path");
      }
    }
    while ($buf = fread($stream, 1024)) {
      fwrite($dest_fp, $buf);
      fwrite($archive_fp, $buf);
    }
    fclose($dest_fp);
    // Move file to right location
    rename($tmp_path, $dest_path);
    fclose($archive_fp);
  }

  if (preg_match('/master([0-9]+)\.m3u8/', $archive_path, $matches)) {
    $ts = time();
    $archcmd = "cp " . $archive_path . " " . $archive_path . "-" . $ts;
    exec($archcmd);

    if ($ENABLE_DEBUG) {
      syslog(LOG_DEBUG, "Running $archcmd");
    }

    // Archive segments in sub directories and rewrite manifests
    $fp = fopen($archive_path . "-" . $ts, "r");
    if ($fp) {
      $tmpfp = fopen($archive_path . "-" . $ts . "-tmp", "w");
      while (($line = fgets($fp)) !== false) {
        if (preg_match('/^master(\d+)_(\d+).ts/', $line, $m)) {
          $oldfile = trim($line);
          $newfile = $m[1] . "-" . date("md") . "/master" . $m[1] . "_" . $m[2] . ".ts";
          if (!file_exists($newfile)) {
            $newdir = dirname(dirname($archive_path) . "/" . $newfile);
            if (!file_exists($newdir)) {
              mkdir($newdir, 0777, true);
            }
            if (file_exists(dirname($archive_path) . "/" . $oldfile)) {
              if ($ENABLE_DEBUG) {
                syslog(LOG_DEBUG, "Moving " . dirname($archive_path). "/" . $oldfile . " to " . dirname($archive_path) . "/" . $newfile);
              }
              rename(dirname($archive_path) . "/" . $oldfile, dirname($archive_path) . "/" . $newfile);
            }
          }
          $line = $newfile . "\n";
        }
        fputs($tmpfp, $line);
      }
      fclose($tmpfp);
      fclose($fp);
      if ($ENABLE_DEBUG) {
        syslog(LOG_DEBUG, "Moving " . $archive_path . "-" . $ts . "-tmp to " . $archive_path . "-" . $ts);
      }
      rename($archive_path . "-" . $ts . "-tmp", $archive_path . "-" . $ts);
    }
      // Store all manifests in an LST file for faster lookup for startover and capture
    $lstfile = dirname($archive_path) . "-" . $matches[1] . ".lst";
    if(file_exists($lstfile)) {
      $lines = file($lstfile, FILE_IGNORE_NEW_LINES);
    } else {
      $lines = [];
    }
    $txt = $archive_path . "-" . $ts;
    array_push($lines, $txt);
    if(count($lines) > 12000) {
      $lines = array_slice($lines, -12000);
    }
    file_put_contents($lstfile, implode(PHP_EOL, $lines), LOCK_EX);
  }
} else if ($_SERVER['REQUEST_METHOD'] === "DELETE") {
  unlink($dest_path);
}
?>