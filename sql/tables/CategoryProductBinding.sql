create table CategoryProductBinding (
	category int not null references Category(id) on delete cascade,
	product int not null references Product(id) on delete cascade,
	minor boolean not null default false,
	displayorder int not null default 0,
	primary key (product, category)
);
