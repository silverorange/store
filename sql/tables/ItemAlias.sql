create table ItemAlias (
	id serial,
	item int not null references Item(id) on delete cascade,
	sku varchar(20) not null,
	primary key (id)
);

CREATE INDEX ItemAlias_item ON ItemAlias(item);
CREATE INDEX ItemAlias_sku ON ItemAlias(sku);
