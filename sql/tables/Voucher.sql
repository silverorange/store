create table Voucher (
	id serial,
	voucher_type varchar(50),
	code varchar(100),
	amount numeric(9, 2),
	used_date timestamp,
	instance integer not null references Instance(id),
	primary key (id)
);

