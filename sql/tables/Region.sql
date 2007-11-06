create table Region (
	id serial,
	title varchar(255),
	primary key(id)
);

SELECT setval('Region_id_seq', max(id)) FROM Region;

