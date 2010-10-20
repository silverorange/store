create table ProductAttributeBindingQueue (
	id serial,
	product int not null references Product(id) on delete cascade,
	attribute int not null references Attribute(id) on delete cascade,
	queue_action varchar(10),
	action_date timestamp not null,
	primary key(id)
);
