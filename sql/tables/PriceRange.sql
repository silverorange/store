create table PriceRange (
	id serial,
	start_price int,
	end_price int,
	original_price boolean not null default false,
	primary key (id)
);

