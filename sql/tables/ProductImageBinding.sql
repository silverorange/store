create table ProductImageBinding (
	product integer not null references Product(id),
	image integer not null references Image(id),
	primary key (product, image)
);
