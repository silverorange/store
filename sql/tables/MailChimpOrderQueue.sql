create table MailChimpOrderQueue (
	id serial,

	email_id    varchar(255) not null,
	campaign_id varchar(255),

	send_attempts integer not null default 0,
	ordernum      integer not null references Orders(id) on delete cascade,

	error_date    timestamp,

	primary key(id)
);
