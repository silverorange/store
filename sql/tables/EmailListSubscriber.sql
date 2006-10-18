create table EmailListSubscriber (
	id serial,
	email varchar(255),
	locale char(5) not null references Locale(id),
	primary key (id)
);

CREATE INDEX EmailListSubscriber_index ON EmailListSubscriber(email);
