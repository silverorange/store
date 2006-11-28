create table ProductImageBinding (
	product integer not null references Product(id),
	image integer not null references Image(id),
	displayorder integer not null default 0,
	primary key (product, image)
);
