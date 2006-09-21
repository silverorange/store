create table QuantityDiscount (
	id serial,
	item int not null references Item(id) on delete cascade,
	quantity int not null default 0,
	primary key (id)
);

CREATE INDEX QuantityDiscount_item_index ON QuantityDiscount(item);
