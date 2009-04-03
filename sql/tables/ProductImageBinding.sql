create table ProductImageBinding (
	product integer not null references Product(id) on delete cascade,
	image integer not null references Image(id) on delete cascade,
	displayorder integer not null default 0,
	primary key (product, image)
);

CREATE INDEX ProductImageBinding_product_index ON ProductImageBinding(product);
CREATE INDEX ProductImageBinding_image_index ON ProductImageBinding(image);
