create table ProductRelatedProductBinding (
	source_product integer not null references Product(id) on delete cascade,
	related_product integer not null references Product(id) on delete cascade,
	displayorder integer not null default 0,
	primary key(source_product, related_product)
);

CREATE INDEX ProductRelatedProductBinding_source_product_index ON ProductRelatedProductBinding(source_product);
CREATE INDEX ProductRelatedProductBinding_related_product_index ON ProductRelatedProductBinding(related_product);
