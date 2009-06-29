create table Feedback (
	id            serial,

	instance      integer references Instance(id) on delete cascade,

	fullname      varchar(255),
	link          varchar(255),
	email         varchar(255),
	bodytext      text not null,
	status        integer not null default 0,
	spam          boolean not null default false,
	ip_address    varchar(15),
	user_agent    varchar(255),
	createdate    timestamp not null,
	http_referrer varchar(255),

	primary key (id)
);

create index Feedback_spam_index   on Feedback(spam);
create index Feedback_status_index on Feedback(status);
