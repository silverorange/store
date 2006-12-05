create table Image (
	id serial,
	border boolean not null default true,
	title varchar(255),
	description text,

	thumb_width integer,
	thumb_height integer,
	
	small_width integer,
	small_height integer,

	large_width integer,
	large_height integer,

	primary key(id)
);  
