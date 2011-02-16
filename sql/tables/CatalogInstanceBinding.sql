create table CatalogInstanceBinding (
	catalog int not null references Catalog(id) on delete cascade,
	instance int not null references Instance(id) on delete cascade,
	primary key (instance, catalog)
);
