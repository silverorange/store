create table CacheFlag (
	shortname varchar(255),
	dirty boolean not null default false
);

create index CacheFlag_shortname_index on CacheFlag(shortname);
