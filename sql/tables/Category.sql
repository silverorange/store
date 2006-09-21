create table Category (
	id serial,
	parent int,
	shortname varchar(255),
	title varchar(255),
	bodytext text,
	createdate timestamp,
	displayorder int not null default 0,
	always_visible boolean not null default false,
	image int references Image(id) on delete set null,
	primary key (id)
);

ALTER TABLE Category ADD CONSTRAINT Categoryfk FOREIGN KEY (parent) REFERENCES Category(id) MATCH FULL on delete cascade;

CREATE INDEX Category_parent_index ON Category(parent);
CREATE INDEX Category_shortname_index ON Category(shortname);
CREATE INDEX Category_displayorder_index ON Category(displayorder);
CREATE INDEX Category_always_visible_index ON Category(always_visible);
CREATE INDEX Category_image_index ON Category(image);
