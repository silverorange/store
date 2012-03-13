create table ItemProvStateExclusionBinding (
	item int not null references Item(id) on delete cascade,
	provstate int not null references ProvState(id) on delete cascade,
	primary key (item, provstate)
);
