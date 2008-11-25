create table ProductReview (
	id serial,
	product integer not null references Product(id) on delete cascade,
	fullname varchar(255),
	link varchar(255),
	email varchar(255),
	bodytext text not null,
	status integer not null default 0,
	spam boolean not null default false,
	ip_address varchar(15),
	user_agent varchar(255),
	createdate timestamp not null,
	primary key (id)
);

create index ProductReview_product_index on ProductReview(product);
create index ProductReview_spam_index on ProductReview(spam);
create index ProductReview_status_index on ProductReview(status);
