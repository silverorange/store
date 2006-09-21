create table Region (
	id serial,
	title varchar(255),
	primary key(id)
);

INSERT INTO Region (id, title) VALUES (1, 'Canada');
INSERT INTO Region (id, title) VALUES (2, 'US');

SELECT setval('Region_id_seq', max(id)) FROM Region;

