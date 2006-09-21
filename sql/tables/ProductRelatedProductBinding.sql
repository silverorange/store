create table ProductRelatedProductBinding (
	source_product integer not null references Product(id),
	related_product integer not null references Product(id),
	displayorder integer not null default 0,
	primary key(source_product, related_product)
);
