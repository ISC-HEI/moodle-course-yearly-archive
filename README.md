
# Moodle Course Archive and Copy From CLI

A little script used to create an archive of a course from the terminal just like you'd do from the GUI. It is used yearly to create an archive of the course, keeping the students in it. A copy is created with a new year and all the students removed.

## Usage

Copy the two scripts inside the moodle root directory.

> [!CAUTION]
> Ownership must be `moodle:www-data`.
> The Moodle root directory must be writable by `www-data`.

## Usage

- `script_get_courses.php` shows you all courses that aren't part of Archive category. It allows you to save the IDs to `course_ids.txt`.  
- `script_update_courses.php` Allows you to copy all courses contained in the previous .txt file OR to update one with a given ID.

Must be run by user `www-data` as in 

```bash
sudo -u www-data php script_get_courses.php
```

or 

```bash
sudo -u www-data php script_update_courses.php 81 82 84 51
```
