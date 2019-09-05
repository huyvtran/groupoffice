-- create tasklist table
DROP TABLE IF EXISTS `task_settings`;
DROP TABLE IF EXISTS `task_portlet_tasklist`;
DROP TABLE IF EXISTS `task_tasks_custom_field`;
DROP TABLE IF EXISTS `task_alert`;
DROP TABLE IF EXISTS `task_task_category`;
DROP TABLE IF EXISTS `task_task`;
DROP TABLE IF EXISTS `task_category`;
DROP TABLE IF EXISTS `task_tasklist`;

CREATE TABLE `task_tasklist` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdBy` int(11) NOT NULL,
  `aclId` int(11) NOT NULL,
  `filesFolderId` int(11) NOT NULL DEFAULT '0',
  `version` int(10) UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `task_tasklist`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `task_tasklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

-- create task category table
CREATE TABLE `task_category` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `createdBy` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `task_category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`createdBy`);

ALTER TABLE `task_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

-- create task table
CREATE TABLE `task_task` (
  `id` int(11) NOT NULL,
  `uid` varchar(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
  `tasklistId` int(11) NOT NULL,
  `createdBy` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `modifiedAt` datetime NOT NULL,
  `modifiedBy` int(11) NOT NULL DEFAULT '0',
  `start` date NOT NULL,
  `due` date NOT NULL,
  `completed` datetime DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recurrenceRule` varchar(280) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `filesFolderId` int(11) NOT NULL DEFAULT '0',
  `priority` int(11) NOT NULL DEFAULT '1',
  `percentageComplete` tinyint(4) NOT NULL DEFAULT '0',
  `projectId` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `task_task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `list_id` (`tasklistId`),
  ADD KEY `rrule` (`recurrenceRule`(191)),
  ADD KEY `uuid` (`uid`);

ALTER TABLE `task_task`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `task_task`
  ADD CONSTRAINT `task_task_ibfk_1` FOREIGN KEY (`tasklistId`) REFERENCES `task_tasklist` (`id`);

-- create category / task lookup table
CREATE TABLE `task_task_category` (
  `taskId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `task_task_category`
  ADD PRIMARY KEY (`taskId`,`categoryId`),
  ADD KEY `task_task_category_ibfk_2` (`categoryId`);

ALTER TABLE `task_task_category`
  ADD CONSTRAINT `task_task_category_ibfk_1` FOREIGN KEY (`taskId`) REFERENCES `task_task` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_task_category_ibfk_2` FOREIGN KEY (`categoryId`) REFERENCES `task_category` (`id`) ON DELETE CASCADE;

-- create task alert table
CREATE TABLE `task_alert` (
  `id` int(11) NOT NULL,
  `taskId` int(11) NOT NULL,
  `remindDate` date NOT NULL,
  `remindTime` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `task_alert`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fkTaskId` (`taskId`);

ALTER TABLE `task_alert`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `task_alert`
  ADD CONSTRAINT `fkTaskId` FOREIGN KEY (`taskId`) REFERENCES `task_task` (`id`) ON DELETE CASCADE;

-- create task portlet table
CREATE TABLE `task_portlet_tasklist` (
  `createdBy` int(11) NOT NULL,
  `tasklistId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `task_portlet_tasklist`
  ADD PRIMARY KEY (`createdBy`,`tasklistId`);

-- create task settings table
CREATE TABLE `task_settings` (
  `createdBy` int(11) NOT NULL,
  `reminderDays` int(11) NOT NULL DEFAULT '0',
  `reminderTime` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `remind` tinyint(1) NOT NULL DEFAULT '0',
  `defaultTasklistId` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `task_settings`
  ADD PRIMARY KEY (`createdBy`);


-- create task custom field table
CREATE TABLE `task_tasks_custom_field` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `task_tasks_custom_field`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `task_tasks_custom_field`
  ADD CONSTRAINT `task_tasks_custom_field_ibfk_1` FOREIGN KEY (`id`) REFERENCES `task_task` (`id`) ON DELETE CASCADE;
