create table CategoryFeaturedProductBinding (
	category int not null references Category(id) on delete cascade,
	product int not null references Product(id) on delete cascade,
	displayorder int not null default 0,
	primary key (product, category)
);

CREATE INDEX CategoryFeaturedProductBinding_category_index ON CategoryFeaturedProductBinding(category);
CREATE INDEX CategoryFeaturedProductBinding_product_index ON CategoryFeaturedProductBinding(product);
