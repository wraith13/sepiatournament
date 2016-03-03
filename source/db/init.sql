alter database sepiatournament_live default character set utf8mb4;
use sepiatournament_live;

create table object
(
	uuid varchar(64) not null primary key,
	parent varchar(64),
	owner varchar(64) not null,
	type varchar(32) not null,
	private tinyint default 0 not null,
	json text,
	search text,
	remove tinyint default 0 not null
);
create index object_parent on object (parent);
create index object_type on object (type);

create table log
(
	target varchar(64) not null,
	at timestamp not null,
	category tinyint not null,
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

create table queue
(
	target varchar(64) not null,
	type varchar(32) not null,
	item varchar(64) not null,
	at timestamp not null,
	primary key(target, type, item)
);
create index queue_at on auth (target, type, at);

create table config
(
	name varchar(128) not null primary key,
	value varchar(4096)
);

