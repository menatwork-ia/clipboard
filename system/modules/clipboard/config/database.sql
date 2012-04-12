-- ********************************************************
-- *                                                      *
-- * IMPORTANT NOTE                                       *
-- *                                                      *
-- * Do not import this file manually but use the Contao  *
-- * install tool to create and maintain database tables! *
-- *                                                      *
-- ********************************************************

-- 
-- Table `tl_clipboard`
-- 

CREATE TABLE `tl_clipboard` (
    `id` int(10) unsigned NOT NULL auto_increment,
    `user_id` int(10) unsigned NOT NULL default '0',
    `title` varchar(128) NOT NULL default '',
    `str_table` varchar(32) NOT NULL default '',
    `favorite` char(1) NOT NULL default '1',
    `childs` char(1) NOT NULL default '0',
    `elem_id` int(10) unsigned NOT NULL default '0',
    `filename` varchar(128) NOT NULL default '',
    PRIMARY KEY  (`id`),
    UNIQUE KEY `key` (`user_id`, `elem_id`, `str_table`, `filename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table `tl_user`
-- 

CREATE TABLE `tl_user` (
  `clipboard` char(1) NOT NULL default '0',
) ENGINE=MyISAM DEFAULT CHARSET=utf8;