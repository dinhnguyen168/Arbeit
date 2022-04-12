eventually, a table for archive_file should be added.
this is a temp place to save table structure while developing

```sql
DROP TABLE IF EXISTS `archive_file`;
CREATE TABLE IF NOT EXISTS `archive_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'auto incremented id',
  `combined_id` varchar(18) DEFAULT NULL,
  `number` int(10) DEFAULT NULL,
  `type` varchar(2) DEFAULT NULL COMMENT 'File type',
  `mime_type` varchar(100) DEFAULT NULL COMMENT 'File mime type',
  `filename` varchar(250) DEFAULT NULL COMMENT 'Name of the file',
  `original_filename` varchar(250) DEFAULT NULL COMMENT 'Name of the originally uploaded file',
  `filesize` int(10) DEFAULT NULL,
  `checksum` varchar(32) DEFAULT NULL,
  `calendar_date` datetime DEFAULT NULL COMMENT 'Date the file will get assigned to',
  `expedition_id` int(11) DEFAULT NULL COMMENT 'Expedition',
  `site_id` int(11) DEFAULT NULL COMMENT 'Site',
  `hole_id` int(11) DEFAULT NULL COMMENT 'Hole',
  `core_id` int(11) DEFAULT NULL COMMENT 'Core',
  `section_id` int(11) DEFAULT NULL COMMENT 'Section',
  `remarks` text,
  PRIMARY KEY (`id`),
  KEY `expedition_id` (`expedition_id`),
  KEY `site_id` (`site_id`),
  KEY `hole_id` (`hole_id`),
  KEY `core_id` (`core_id`),
  KEY `section_id` (`section_id`),
  CONSTRAINT `File_Core` FOREIGN KEY (`core_id`) REFERENCES `core_core` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `File_Expedition` FOREIGN KEY (`expedition_id`) REFERENCES `project_expedition` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `File_Hole` FOREIGN KEY (`hole_id`) REFERENCES `project_hole` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `File_Section` FOREIGN KEY (`section_id`) REFERENCES `core_section` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `File_Site` FOREIGN KEY (`site_id`) REFERENCES `project_site` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COMMENT='GENERATED:2020-03-11'
```