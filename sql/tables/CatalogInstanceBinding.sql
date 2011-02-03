create table CatalogInstanceBinding (
	instance int not null references Instance(id) on delete cascade,
	catalog int not null references Catalog(id) on delete cascade,
	primary key (instance, catalog)
);
