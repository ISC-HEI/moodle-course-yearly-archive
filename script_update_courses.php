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

function shortYears()
{
  $date = new DateTime('now');
  $dateNext = new DateTime('now');
  $dateNext->modify('+1 year');

  $year = $date->format('y');
  $nextYear = $dateNext->format('y');

  return "{$year}-{$nextYear} / ";
}

/* -- Cli -- */

$datePrefix = shortYears();

unset($argv[0]);
$ids = $argv;

foreach ($ids as $id)
{
  $course = $DB->get_record('course', ['id' => $id]);

  echo "Copie de {$course->fullname}...\n";

  /* -- Move original course -- */
  $baseCategoryId = $course->category;
  $baseFullname = $course->fullname;
  $baseShortname = $course->shortname;
  $course->category = $archiveCategoryId;
  $course->fullname = $datePrefix . $course->fullname;
  $course->shortname = $datePrefix . $course->shortname;
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
  $formData->enddate = $course->enddate != 0 ? nextYear($course->enddate) : 0;
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
