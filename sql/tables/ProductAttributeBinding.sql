create table ProductAttributeBinding (
	product int not null references Product(id) on delete cascade,
	attribute int not null references Attribute(id) on delete cascade,
	primary key (product, attribute)
);
