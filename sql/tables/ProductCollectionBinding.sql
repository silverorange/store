create table ProductCollectionBinding (
	source_product integer not null references Product(id) on delete cascade,
	member_product integer not null references Product(id) on delete cascade,
	primary key(source_product, member_product)
);
