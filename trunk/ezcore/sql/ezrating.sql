DROP TABLE IF EXISTS `ezcontentobject_rating`;
CREATE TABLE `ezcontentobject_rating` (
  `contentobject_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `rating` float NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`contentobject_id`,`user_id`)
);
