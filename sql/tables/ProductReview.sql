create table ProductReview (
	id serial,
	product integer not null references Product(id) on delete cascade,
	fullname varchar(255),
	email varchar(255),
	description varchar(255) not null,
	bodytext text,
	createdate timestamp,
	enabled boolean not null default true,
	site_response boolean not null default false,
	primary key (id)
);

CREATE INDEX ProductReview_product_index ON ProductReview(product);
CREATE INDEX ProductReview_enabled_index ON ProductReview(enabled);

