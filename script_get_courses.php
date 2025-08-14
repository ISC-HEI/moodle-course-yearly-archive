
<?php

define("CLI_SCRIPT", 1);

require_once(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/lib/accesslib.php');
require_once($CFG->dirroot . '/lib/classes/session/manager.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/lib.php');

$categoryIds = [
  6,
  7,
  9,
  10,
  11,
  12
];

$courseIds = [];

foreach ($categoryIds as $categoryId)
{
  $category = $DB->get_record('course_categories', ['id' => $categoryId]);

  echo "====Cours dans la catégorie {$category->name}====\n\n";

  foreach ($DB->get_records('course', ['category' => $categoryId]) as $course)
  {
    $courseIds[] = $course->id;
    echo "fullname : '{$course->fullname}' shortname : '{$course->shortname}' - ID : {$course->id}\n";
  }
}

$choice = "";

while ($choice != "y" && $choice != "n")
{
  $choice_tmp = strtolower(readline("Souhaitez vous enregistrer l'ID des cours dans un fichier? [Y/n] : "));
  $choice = $choice_tmp != '' ? $choice_tmp : 'y';
}

$file = fopen("course_ids.txt", "w");

fwrite($file, implode(",", $courseIds) . "\n");

fclose($file);
