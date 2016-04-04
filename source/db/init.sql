alter database sepiatournament_live default character set utf8mb4;
use sepiatournament_live;

create table object
(
	id varchar(64) not null primary key,
	parent varchar(64),
	owner varchar(64) not null,
	type varchar(32) not null,
	private tinyint default 0 not null,
	json text,
	search text,
	remove tinyint default 0 not null,
	created_at datetime not null
);
create index object_parent on object (parent, type, created_at desc);
create index object_type on object (type, created_at desc);

create table log
(
	target varchar(64) not null,
	at datetime not null,
	category varchar(16) not null,
	operator varchar(64),
	message varchar(1024) not null,
	primary key(target, at)
);
create index log_at on log (at);

create table auth
(
	type varchar(32) not null,
	id varchar(128) not null,
	target varchar(64) not null,
	json text,
	primary key(type, id)
);
create index auth_target on auth (target);

create table twitter_user_cache
(
	id varchar(64) not null,
	screen_name varchar(64) not null,
	at datetime not null,
	json text,
	primary key(id)
);
create index twitter_user_cache_screen_name on twitter_user_cache (screen_name);
create index twitter_user_cache_at on twitter_user_cache (at);

create table queue
(
	target varchar(64) not null,
	type varchar(32) not null,
	item varchar(64) not null,
	at datetime not null,
	primary key(target, type, item)
);
create index queue_at on queue (target, type, at);

create table tag
(
	target varchar(64) not null,
	type varchar(32) not null,
	tag varchar(64) not null,
	primary key(target, tag)
);
create index tag_type on tag (type, tag);

create table config
(
	name varchar(128) not null primary key,
	value varchar(4096)
);

