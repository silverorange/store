create table CartEntry (
	id serial,
	account integer references Account(id) on delete cascade, -- nullable
	sessionid varchar(255),
	item integer not null references Item(id) on delete cascade,
	quantity integer default 0,
	quick_order boolean not null default false,
	saved boolean not null default false,
	primary key(id)
);

create index CartEntry_sessionid on CartEntry(sessionid);
create index CartEntry_account on CartEntry(account);
create index CartEntry_item on CartEntry(item);
