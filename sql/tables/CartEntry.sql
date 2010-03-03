create table CartEntry (
	id serial,
	account integer references Account(id) on delete cascade, -- nullable
	instance integer default null references Instance(id),
	sessionid varchar(255),
	item integer not null references Item(id) on delete cascade,
	alias integer references ItemAlias(id) on delete set null,
	quantity integer default 0,
	source integer,
	source_category integer,
	saved boolean not null default false,
	custom_price numeric(11, 2), -- used by items like gift certificates to over-ride price
	primary key(id)
);

create index CartEntry_sessionid on CartEntry(sessionid);
create index CartEntry_account on CartEntry(account);
create index CartEntry_item on CartEntry(item);
create index CartEntry_alias on CartEntry(alias);
create index CartEntry_instance_index on CartEntry(instance);
