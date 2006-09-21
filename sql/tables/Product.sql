create table Product (
	id serial,
	catalog int not null references Catalog(id) on delete cascade,
	title varchar(255) not null,
	bodytext text,
	createdate timestamp,
	shortname varchar(255),
	image int references Image(id) on delete set null,
	primary key (id)
);

CREATE INDEX Product_catalog_index ON Product(catalog);
CREATE INDEX Product_image_index ON Product(image);
