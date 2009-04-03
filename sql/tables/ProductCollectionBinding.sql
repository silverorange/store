create table ProductCollectionBinding (
	source_product integer not null references Product(id) on delete cascade,
	member_product integer not null references Product(id) on delete cascade,
	primary key(source_product, member_product)
);

CREATE INDEX ProductCollectionBinding_source_product_index ON ProductCollectionBinding(source_product);
CREATE INDEX ProductCollectionBinding_member_product_index ON ProductCollectionBinding(member_product);
