<?php

use core\di;
use core\hook\manager;

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/lib/accesslib.php');
require_once($CFG->dirroot . '/lib/classes/session/manager.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;


$admin = get_admin();

/* -- Login admin -- */
$context = context_system::instance();
\core\session\manager::set_user($admin);
require_capability('moodle/restore:restorecourse', $context);
require_capability('moodle/restore:restoretargetimport', $context);
require_capability('moodle/backup:backupcourse', $context);


/* -- Constants -- */
$adminId = $admin->id; // TODO remplacer les IDs par les vrais
$archiveCategoryId = 8;

/* -- Functions -- */

function nextYear($timestamp) 
{
  $date = new DateTime();
  $date->setTimestamp($timestamp);
  $date->modify('+1 year');

  return $date->getTimestamp();
}

function shortYears($timestampStart, $timestampEnd) 
{
  $start = new DateTime();
  $start->setTimestamp($timestampStart);
  
  $startYear = intval($start->format('y'));
  $startMonth = intval($start->format('n'));

  if ($startMonth < 9) 
  { // janvier -> août = prec - actu
    $schoolYearStart = $startYear - 1;
  }
  else 
  { // septembre -> décembre = actu - proc
    $schoolYearStart = $startYear;
  }

  if ($timestampEnd > 0) 
  {
    $end = new DateTime();
    $end->setTimestamp($timestampEnd);
    $endYear = intval($end->format('y'));
    $endMonth = intval($end->format('n'));

    if ($endMonth >= 9)
    {
      $schoolYearEnd = $endYear + 1;
    }
    else
    {
      $schoolYearEnd = $endYear;
    }
  }
  else
  {
    $schoolYearEnd = $schoolYearStart + 1;
  }

  // Siècles
  //$schoolYearStart = printf('%02d', $schoolYearStart % 100);
  //$schoolYearEnd = printf('%02d', $schoolYearEnd % 100);

  return "{$schoolYearStart}-{$schoolYearEnd} / ";
}

function parseDate(string $dateString) 
{
  $date = DateTime::createFromFormat("!d.m.Y", $dateString);
  return $date ? $date->getTimestamp() : time();
}

/* -- Cli -- */

$choice = "";

while ($choice != "1" && $choice != "2")
{
  $choice_tmp = readline("Voulez vous update tous les cours de la liste (1) ou un seul (2) ? [1 par défaut] : ");
  $choice = $choice_tmp != "" ? $choice_tmp : "1";
}

if ($choice == "2")
{
  /* -- Inputs -- */
  $courseId = readline("Id du cours : ");
  // $newFullName = readline("Nouveau nom du cours (fullname) : ");
  // $newShortName = readline("Nouveau shortname du cours : ");
  // $startDateInput = readline("Date de début (DD.MM.YYYY) : ");
  // $endDateInput = readline("Date de fin (DD.MM.YYYY) : ");


  // $startDate = parseDate($startDateInput);
  // $endDate = parseDate($endDateInput);

  /* -- Lookup -- */
  $course = $DB->get_record('course', ['id' => $courseId], '*', MUST_EXIST);

  echo "Copie de {$course->fullname}...\n";


  /* -- Move original course -- */
  $baseCategoryId = $course->category;
  $baseFullname = $course->fullname;
  $baseShortname = $course->shortname;
  $course->category = $archiveCategoryId;
  $course->fullname = shortYears($course->startdate, $course->enddate) . $course->fullname;
  $course->shortname = shortYears($course->startdate, $course->enddate) . $course->shortname;
  update_course($course);
  echo "Cours déplacé dans la catégorie 'Archive'\n";

  /* -- Copy -- */
  $formData = new stdClass;
  $formData->courseid = $course->id;
  $formData->fullname = $baseFullname;
  $formData->shortname = $baseShortname;
  $formData->category = $baseCategoryId;
  $formData->visible = 1;
  $formData->startdate = nextYear($course->startdate);
  $formData->enddate = nextYear($course->enddate);
  $formData->idnumber = '';
  $formData->userdata = 0;
  $formData->role_1 = 1;
  $formData->role_3 = 3;
  $formData->role_5 = 0;

  $copyData = copy_helper::process_formdata($formData);
  $copyids = copy_helper::create_copy($copyData);

  // $course = $DB->get_record('course', ['shortname' => $baseShortname . "_1"]);
  // $course->fullname = $baseFullname;
  // $course->shortname = $baseShortname;

  // update_course($course);

  echo "Cours créé avec succès.\n";
  exit();
}

$file = fopen("course_ids.txt", "r");
$content = fread($file, filesize("course_ids.txt"));
fclose($file);

$ids = explode(",", $content);

foreach ($ids as $id)
{
  $course = $DB->get_record('course', ['id' => $id]);

  echo "Copie de {$course->fullname}...\n";

  /* -- Move original course -- */
  $baseCategoryId = $course->category;
  $baseFullname = $course->fullname;
  $baseShortname = $course->shortname;
  $course->category = $archiveCategoryId;
  $course->fullname = shortYears($course->startdate, $course->enddate) . $course->fullname;
  $course->shortname = shortYears($course->startdate, $course->enddate) . $course->shortname;
  update_course($course);
  echo "Cours déplacé dans la catégorie 'Archive'\n";
 
  /* -- Copy -- */
  $formData = new stdClass;
  $formData->courseid = $course->id;
  $formData->fullname = $baseFullname;
  $formData->shortname = $baseShortname;
  $formData->category = $baseCategoryId;
  $formData->visible = 1;
  $formData->startdate = nextYear($course->startdate);
  $formData->enddate = nextYear($course->enddate);
  $formData->idnumber = '';
  $formData->userdata = 0;
  $formData->role_1 = 1;
  $formData->role_3 = 3;
  $formData->role_5 = 0;

  $copyData = copy_helper::process_formdata($formData);
  $copyids = copy_helper::create_copy($copyData);

  // $course = $DB->get_record('course', ['shortname' => $baseShortname . "_1"]);
  // $course->fullname = $baseFullname;
  // $course->shortname = $baseShortname;

  // update_course($course);

  echo "Cours créé avec succès.\n";
}
