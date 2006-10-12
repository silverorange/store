create table CatalogRegionBinding (
	region int not null references Region(id) on delete cascade,
	catalog int not null references Catalog(id) on delete cascade,
	available boolean not null default 't',
	primary key (region, catalog)
);
