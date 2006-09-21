/*
Used to map shortnames from the old site
that have new paths on the new site
*/

create table ArticleMap (
	id serial,
	old_shortname varchar(255),
	new_path varchar(255),
	primary key (id)
);
