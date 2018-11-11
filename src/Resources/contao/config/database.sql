-- ********************************************************
-- *                                                      *
-- * IMPORTANT NOTE                                       *
-- *                                                      *
-- * Do not import this file manually but use the Contao  *
-- * install tool to create and maintain database tables! *
-- *                                                      *
-- ********************************************************

-- 
-- Table `tl_user`
-- 

CREATE TABLE `tl_user` (
  `clipboard` char(1) NOT NULL default '0',
  `clipboard_context` char(1) NOT NULL default '0',
) ENGINE=MyISAM DEFAULT CHARSET=utf8;