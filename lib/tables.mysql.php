<?
import('db');

$tables = array(
	'shows' => 'CREATE TABLE IF NOT EXISTS shows (showid int not null primary key auto_increment, title varchar(100) not null, name varchar(100) not null, episodes int(4) not null, path text not null, site varchar(20) not null, network varchar(20), quality varchar(10), startdate date not null, enddate date not null, changedate date not null, download int not null, scan int not null, aid int not null, acid int not null, unique(name)) CHARACTER SET utf8 COLLATE utf8_unicode_ci engine=InnoDB;',
	'episodes' => 'CREATE TABLE IF NOT EXISTS episodes (id int not null primary key auto_increment, showid int not null, name text, episode int, site varchar(22), file text, path text, want int, quality varchar(10), airdate int, unique(showid,episode)) CHARACTER SET utf8 COLLATE utf8_unicode_ci engine=InnoDB;',
	'sites' => 'CREATE TABLE IF NOT EXISTS sites (id int not null primary key auto_increment, site char(20) not null, unique(site)) CHARACTER SET utf8 COLLATE utf8_unicode_ci engine=InnoDB;',
	'anidb' => 'CREATE TABLE IF NOT EXISTS anidb (aid int, type int, language varchar(6), title char(100), cdate int, unique(aid,language,title)) CHARACTER SET utf8 COLLATE utf8_unicode_ci engine=InnoDB;',
	'acdb' => 'CREATE TABLE IF NOT EXISTS acdb (id int primary key not null, title text, cdate int, unique(id) ) CHARACTER SET utf8 COLLATE utf8_unicode_ci engine=InnoDB;',
	'config' => 'CREATE TABLE IF NOT EXISTS config ( name varchar(20), var varchar(20), value varchar(200), unique(name,var) ) CHARACTER SET utf8 COLLATE utf8_unicode_ci engine=InnoDB;',
);

$db = DB::getInstance();
foreach ( $tables as $table => $tableInfo ) {
	$exists = $db->fsqlr('SELECT count(TABLE_NAME) FROM information_schema.TABLES where TABLE_NAME = "%s" AND TABLE_SCHEMA = "%s"', $table, $db->getDatabase());
	if ( !$exists ) $db->sql( $tableInfo );
}
